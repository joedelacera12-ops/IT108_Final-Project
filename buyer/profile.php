<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Only buyers may access
require_role('buyer');
$user = current_user();
$pdo = get_db();

// Get buyer information
try {
    $stmt = $pdo->prepare(
        "SELECT u.*, ua.street_address as address_line1, ua.barangay as address_line2, ua.city, ua.province as state, ua.country, ua.postal_code
        FROM users u
        LEFT JOIN user_addresses ua ON u.id = ua.user_id AND ua.is_default = 1
        WHERE u.id = ?
    ");
    $stmt->execute([$user['id']]);
    $buyer = $stmt->fetch();
} catch (Exception $e) {
    $buyer = null;
}

$message = '';
$messageType = '';

// Handle profile picture upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_picture'])) {
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($_FILES['profile_picture']['type'], $allowedTypes)) {
            $message = 'Invalid file type. Please upload JPEG, PNG, or GIF images only.';
            $messageType = 'danger';
        } elseif ($_FILES['profile_picture']['size'] > $maxSize) {
            $message = 'File size too large. Maximum file size is 5MB.';
            $messageType = 'danger';
        } else {
            // Generate unique filename
            $extension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
            $filename = 'profile_' . $user['id'] . '_' . time() . '.' . $extension;
            $uploadPath = __DIR__ . '/../uploads/profile_pictures/' . $filename;
            
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $uploadPath)) {
                try {
                    // Update database with new profile image path
                    $stmt = $pdo->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
                    $stmt->execute(['/uploads/profile_pictures/' . $filename, $user['id']]);
                    
                    $message = 'Profile picture updated successfully.';
                    $messageType = 'success';
                    
                    // Refresh buyer data
                    $stmt = $pdo->prepare(
                        "SELECT u.*, ua.street_address as address_line1, ua.barangay as address_line2, ua.city, ua.province as state, ua.country, ua.postal_code
                        FROM users u
                        LEFT JOIN user_addresses ua ON u.id = ua.user_id AND ua.is_default = 1
                        WHERE u.id = ?
                    ");
                    $stmt->execute([$user['id']]);
                    $buyer = $stmt->fetch();
                } catch (Exception $e) {
                    $message = 'Error updating profile picture: ' . $e->getMessage();
                    $messageType = 'danger';
                    
                    // Delete uploaded file if database update failed
                    if (file_exists($uploadPath)) {
                        unlink($uploadPath);
                    }
                }
            } else {
                $message = 'Error uploading file. Please try again.';
                $messageType = 'danger';
            }
        }
    } elseif (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] !== UPLOAD_ERR_NO_FILE) {
        $message = 'Error uploading file. Please try again.';
        $messageType = 'danger';
    }
}

// Handle profile picture removal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_picture'])) {
    try {
        // Get current profile image path
        $stmt = $pdo->prepare("SELECT profile_image FROM users WHERE id = ?");
        $stmt->execute([$user['id']]);
        $userData = $stmt->fetch();
        
        if ($userData && !empty($userData['profile_image'])) {
            // Delete the file if it exists
            $filePath = __DIR__ . '/..' . $userData['profile_image'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            
            // Update database to remove profile image reference
            $stmt = $pdo->prepare("UPDATE users SET profile_image = NULL WHERE id = ?");
            $stmt->execute([$user['id']]);
            
            $message = 'Profile picture removed successfully.';
            $messageType = 'success';
            
            // Refresh buyer data
            $stmt = $pdo->prepare(
                "SELECT u.*, ua.street_address as address_line1, ua.barangay as address_line2, ua.city, ua.province as state, ua.country, ua.postal_code
                FROM users u
                LEFT JOIN user_addresses ua ON u.id = ua.user_id AND ua.is_default = 1
                WHERE u.id = ?
            ");
            $stmt->execute([$user['id']]);
            $buyer = $stmt->fetch();
        } else {
            $message = 'No profile picture to remove.';
            $messageType = 'warning';
        }
    } catch (Exception $e) {
        $message = 'Error removing profile picture: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address_line1 = trim($_POST['address_line1'] ?? '');
    $address_line2 = trim($_POST['address_line2'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $postal_code = trim($_POST['postal_code'] ?? '');
    $country = trim($_POST['country'] ?? '');
    
    try {
        // Update user info
        $stmt = $pdo->prepare(
            "UPDATE users 
            SET first_name = ?, last_name = ?, phone = ?, email = ?
            WHERE id = ?
        ");
        $stmt->execute([$first_name, $last_name, $phone, $email, $user['id']]);
        
        // Update or insert address
        $stmt = $pdo->prepare(
            "INSERT INTO user_addresses (user_id, street_address, barangay, city, province, country, postal_code, phone, is_default)
            VALUES (?, ?, ?, ?, ?, ?, '', '', 1)
            ON DUPLICATE KEY UPDATE
            street_address = VALUES(street_address),
            barangay = VALUES(barangay),
            city = VALUES(city),
            province = VALUES(province),
            country = VALUES(country),
            postal_code = VALUES(postal_code)
        ");
        $stmt->execute([$user['id'], $address_line1, $address_line2, $city, $state, $country, $postal_code]);
        
        $message = 'Profile updated successfully';
        $messageType = 'success';
        
        // Refresh data
        $stmt = $pdo->prepare(
            "SELECT u.*, ua.street_address as address_line1, ua.barangay as address_line2, ua.city, ua.province as state, ua.country, ua.postal_code
            FROM users u
            LEFT JOIN user_addresses ua ON u.id = ua.user_id AND ua.is_default = 1
            WHERE u.id = ?
        ");
        $stmt->execute([$user['id']]);
        $buyer = $stmt->fetch();
    } catch (Exception $e) {
        $message = 'Error updating profile: ' . $e->getMessage();
        $messageType = 'danger';
    }
}
?>

<div class="row">
    <div class="col-12">
        <h3 class="section-title mb-4"><i class="fas fa-user-edit me-2"></i>Buyer Profile</h3>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

            <div class="row">
                <div class="col-lg-8">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="fas fa-camera me-2"></i>Profile Picture</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 text-center">
                                    <?php 
                                    $profileImage = $buyer['profile_image'] ?? null;
                                    if ($profileImage && file_exists(__DIR__ . '/..' . $profileImage)) {
                                        $imageUrl = '/ecommerce_farmers_fishers' . $profileImage;
                                    } else {
                                        // Use gender-based default avatars
                                        $gender = $buyer['gender'] ?? '';
                                        if ($gender === 'male') {
                                            $imageUrl = '/ecommerce_farmers_fishers/assets/images/avatar-male.png';
                                        } elseif ($gender === 'female') {
                                            $imageUrl = '/ecommerce_farmers_fishers/assets/images/avatar-female.png';
                                        } else {
                                            $imageUrl = '/ecommerce_farmers_fishers/assets/images/avatar-placeholder.png';
                                        }
                                    }
                                    ?>
                                    <img src="<?php echo htmlspecialchars($imageUrl); ?>" alt="Profile Picture" class="img-fluid rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                                </div>
                                <div class="col-md-8">
                                    <form method="POST" enctype="multipart/form-data">
                                        <div class="mb-3">
                                            <label for="profilePicture" class="form-label">Upload New Picture</label>
                                            <input type="file" class="form-control" id="profilePicture" name="profile_picture" accept="image/*">
                                            <div class="form-text">Maximum file size: 5MB. Supported formats: JPG, PNG, GIF</div>
                                        </div>
                                        <button type="submit" name="upload_picture" class="btn btn-outline-primary">
                                            <i class="fas fa-upload me-1"></i>Upload Picture
                                        </button>
                                        <?php if ($buyer && !empty($buyer['profile_image'])): ?>
                                        <button type="submit" name="remove_picture" class="btn btn-outline-danger mt-2">
                                            <i class="fas fa-trash me-1"></i>Remove Picture
                                        </button>
                                        <?php endif; ?>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Profile Information</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="firstName" class="form-label">First Name</label>
                                        <input type="text" class="form-control" id="firstName" name="first_name" 
                                               value="<?php echo $buyer ? htmlspecialchars($buyer['first_name'] ?? '') : ''; ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="lastName" class="form-label">Last Name</label>
                                        <input type="text" class="form-control" id="lastName" name="last_name" 
                                               value="<?php echo $buyer ? htmlspecialchars($buyer['last_name'] ?? '') : ''; ?>" required>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email Address</label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo $buyer ? htmlspecialchars($buyer['email'] ?? '') : ''; ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="phone" class="form-label">Phone Number</label>
                                        <input type="text" class="form-control" id="phone" name="phone" 
                                               value="<?php echo $buyer ? htmlspecialchars($buyer['phone'] ?? '') : ''; ?>">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="addressLine1" class="form-label">Address Line 1</label>
                                    <input type="text" class="form-control" id="addressLine1" name="address_line1" 
                                           value="<?php echo $buyer ? htmlspecialchars($buyer['address_line1'] ?? '') : ''; ?>">
                                </div>

                                <div class="mb-3">
                                    <label for="addressLine2" class="form-label">Address Line 2</label>
                                    <input type="text" class="form-control" id="addressLine2" name="address_line2" 
                                           value="<?php echo $buyer ? htmlspecialchars($buyer['address_line2'] ?? '') : ''; ?>">
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label for="city" class="form-label">City</label>
                                        <input type="text" class="form-control" id="city" name="city" 
                                               value="<?php echo $buyer ? htmlspecialchars($buyer['city'] ?? '') : ''; ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="state" class="form-label">State/Province</label>
                                        <input type="text" class="form-control" id="state" name="state" 
                                               value="<?php echo $buyer ? htmlspecialchars($buyer['state'] ?? '') : ''; ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="postalCode" class="form-label">Postal Code</label>
                                        <input type="text" class="form-control" id="postalCode" name="postal_code" 
                                               value="<?php echo $buyer ? htmlspecialchars($buyer['postal_code'] ?? '') : ''; ?>">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="country" class="form-label">Country</label>
                                    <input type="text" class="form-control" id="country" name="country" 
                                           value="<?php echo $buyer ? htmlspecialchars($buyer['country'] ?? '') : ''; ?>">
                                </div>

                                <button type="submit" name="update_profile" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>Save Changes
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Purchase Summary</h5>
                        </div>
                        <div class="card-body">
                            <?php
                            // Get purchase stats
                            try {
                                $stmt = $pdo->prepare("
                                    SELECT 
                                        COUNT(*) as total_orders,
                                        SUM(total) as total_spent,
                                        COUNT(CASE WHEN status = 'delivered' THEN 1 END) as delivered_orders
                                    FROM orders 
                                    WHERE user_id = ?
                                ");
                                $stmt->execute([$user['id']]);
                                $stats = $stmt->fetch();
                                
                                $completionRate = $stats['total_orders'] > 0 ? 
                                    round(($stats['delivered_orders'] / $stats['total_orders']) * 100, 1) : 0;
                            } catch (Exception $e) {
                                $stats = ['total_orders' => 0, 'total_spent' => 0, 'delivered_orders' => 0];
                                $completionRate = 0;
                            }
                            ?>
                            
                            <div class="text-center mb-3">
                                <div class="display-6 text-primary">₱<?php echo number_format($stats['total_spent'] ?? 0, 2); ?></div>
                                <div class="text-muted">Total Spent</div>
                            </div>
                            
                            <div class="d-flex justify-content-between mb-2">
                                <span>Total Orders</span>
                                <span class="fw-bold"><?php echo $stats['total_orders'] ?? 0; ?></span>
                            </div>
                            
                            <div class="d-flex justify-content-between mb-2">
                                <span>Delivered Orders</span>
                                <span class="fw-bold"><?php echo $stats['delivered_orders'] ?? 0; ?></span>
                            </div>
                            
                            <div class="progress mt-3">
                                <div class="progress-bar bg-success" role="progressbar" 
                                     style="width: <?php echo $completionRate; ?>%" 
                                     aria-valuenow="<?php echo $completionRate; ?>" aria-valuemin="0" aria-valuemax="100">
                                    <?php echo $completionRate; ?>%
                                </div>
                            </div>
                            <div class="text-muted small text-center mt-2">Delivery Completion Rate</div>
                        </div>
                    </div>

                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="fas fa-cog me-2"></i>Account Settings</h5>
                        </div>
                        <div class="card-body">
                            <a href="/ecommerce_farmers_fishers/buyer/orders.php" class="btn btn-outline-primary w-100 mb-2">
                                <i class="fas fa-receipt me-1"></i>My Orders
                            </a>
                            <a href="/ecommerce_farmers_fishers/change_password.php" class="btn btn-outline-secondary w-100 mb-2">
                                <i class="fas fa-key me-1"></i>Change Password
                            </a>
                            <button class="btn btn-outline-danger w-100" disabled>
                                <i class="fas fa-exclamation-triangle me-1"></i>Deactivate Account
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>