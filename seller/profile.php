<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Only sellers may access
require_role('seller');
$user = current_user();
$pdo = get_db();

// Get seller information
try {
    $stmt = $pdo->prepare("
        SELECT u.*, ua.street_address as address_line1, ua.barangay as address_line2, ua.city, ua.province as state, ua.country, ua.postal_code, bp.business_name, bp.description as business_description
        FROM users u
        LEFT JOIN user_addresses ua ON u.id = ua.user_id AND ua.is_default = 1
        LEFT JOIN business_profiles bp ON u.id = bp.user_id
        WHERE u.id = ?
    ");
    $stmt->execute([$user['id']]);
    $seller = $stmt->fetch();
} catch (Exception $e) {
    $seller = null;
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
                    
                    // Refresh seller data
                    $stmt = $pdo->prepare("
                        SELECT u.*, ua.address_line1, ua.address_line2, ua.city, ua.state, ua.postal_code, ua.country, bp.business_name, bp.description as business_description
                        FROM users u
                        LEFT JOIN user_addresses ua ON u.id = ua.user_id AND ua.is_default = 1
                        LEFT JOIN business_profiles bp ON u.id = bp.user_id
                        WHERE u.id = ?
                    ");
                    $stmt->execute([$user['id']]);
                    $seller = $stmt->fetch();
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
            
            // Refresh seller data
            $stmt = $pdo->prepare("
                SELECT u.*, ua.street_address as address_line1, ua.barangay as address_line2, ua.city, ua.province as state, ua.country, ua.postal_code, bp.business_name, bp.description as business_description
                FROM users u
                LEFT JOIN user_addresses ua ON u.id = ua.user_id AND ua.is_default = 1
                LEFT JOIN business_profiles bp ON u.id = bp.user_id
                WHERE u.id = ?
            ");
            $stmt->execute([$user['id']]);
            $seller = $stmt->fetch();
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
    $business_name = trim($_POST['business_name'] ?? '');
    $business_description = trim($_POST['business_description'] ?? '');
    $address_line1 = trim($_POST['address_line1'] ?? '');
    $address_line2 = trim($_POST['address_line2'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $postal_code = trim($_POST['postal_code'] ?? '');
    $country = trim($_POST['country'] ?? '');
    
    try {
        // Update user info
        $stmt = $pdo->prepare("
            UPDATE users 
            SET first_name = ?, last_name = ?, phone = ?, email = ?
            WHERE id = ?
        ");
        $stmt->execute([$first_name, $last_name, $phone, $email, $user['id']]);
        
        // Update or insert business profile
        $stmt = $pdo->prepare("
            INSERT INTO business_profiles (user_id, business_name, description)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE
            business_name = VALUES(business_name),
            description = VALUES(description)
        ");
        $stmt->execute([$user['id'], $business_name, $business_description]);
        
        // Update or insert address
        $stmt = $pdo->prepare("
            INSERT INTO user_addresses (user_id, street_address, barangay, city, province, country, postal_code, phone, is_default)
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
        $stmt = $pdo->prepare("
            SELECT u.*, ua.street_address as address_line1, ua.barangay as address_line2, ua.city, ua.province as state, ua.country, ua.postal_code, bp.business_name, bp.description as business_description
            FROM users u
            LEFT JOIN user_addresses ua ON u.id = ua.user_id AND ua.is_default = 1
            LEFT JOIN business_profiles bp ON u.id = bp.user_id
            WHERE u.id = ?
        ");
        $stmt->execute([$user['id']]);
        $seller = $stmt->fetch();
    } catch (Exception $e) {
        $message = 'Error updating profile: ' . $e->getMessage();
        $messageType = 'danger';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Profile - AgriSea Marketplace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="/ecommerce_farmers_fishers/assets/css/unified_light_theme.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid p-0">
        <!-- Navigation -->
        <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm" style="padding: 0.75rem 1rem;">
            <div class="container-fluid">
                <a class="navbar-brand" href="/ecommerce_farmers_fishers/seller/dashboard.php">
                    <strong class="text-success">AgriSea</strong> <span class="text-primary">Marketplace</span>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a href="/ecommerce_farmers_fishers/seller/dashboard.php" class="nav-link">
                                <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle me-1"></i> <?php echo htmlspecialchars($user['first_name'] . ' ' . ($user['last_name'] ?? '')); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="/ecommerce_farmers_fishers/seller/profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="/ecommerce_farmers_fishers/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <div class="container py-4">
            <div class="row">
                <div class="col-12">
                    <h3 class="mb-4"><i class="fas fa-user-edit me-2"></i>Seller Profile</h3>
                    
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

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
                                    $profileImage = $seller['profile_image'] ?? null;
                                    if ($profileImage && file_exists(__DIR__ . '/..' . $profileImage)) {
                                        $imageUrl = '/ecommerce_farmers_fishers' . $profileImage;
                                    } else {
                                        // Use gender-based default avatars
                                        $gender = $seller['gender'] ?? '';
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
                                        <?php if ($seller && !empty($seller['profile_image'])): ?>
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
                                               value="<?php echo $seller ? htmlspecialchars($seller['first_name'] ?? '') : ''; ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="lastName" class="form-label">Last Name</label>
                                        <input type="text" class="form-control" id="lastName" name="last_name" 
                                               value="<?php echo $seller ? htmlspecialchars($seller['last_name'] ?? '') : ''; ?>" required>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email Address</label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo $seller ? htmlspecialchars($seller['email'] ?? '') : ''; ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="phone" class="form-label">Phone Number</label>
                                        <input type="text" class="form-control" id="phone" name="phone" 
                                               value="<?php echo $seller ? htmlspecialchars($seller['phone'] ?? '') : ''; ?>">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="businessName" class="form-label">Business Name</label>
                                    <input type="text" class="form-control" id="businessName" name="business_name" 
                                           value="<?php echo $seller ? htmlspecialchars($seller['business_name'] ?? '') : ''; ?>">
                                </div>

                                <div class="mb-3">
                                    <label for="businessDescription" class="form-label">Business Description</label>
                                    <textarea class="form-control" id="businessDescription" name="business_description" rows="3"><?php 
                                        echo $seller ? htmlspecialchars($seller['business_description'] ?? '') : ''; 
                                    ?></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="addressLine1" class="form-label">Address Line 1</label>
                                    <input type="text" class="form-control" id="addressLine1" name="address_line1" 
                                           value="<?php echo $seller ? htmlspecialchars($seller['address_line1'] ?? '') : ''; ?>">
                                </div>

                                <div class="mb-3">
                                    <label for="addressLine2" class="form-label">Address Line 2</label>
                                    <input type="text" class="form-control" id="addressLine2" name="address_line2" 
                                           value="<?php echo $seller ? htmlspecialchars($seller['address_line2'] ?? '') : ''; ?>">
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label for="city" class="form-label">City</label>
                                        <input type="text" class="form-control" id="city" name="city" 
                                               value="<?php echo $seller ? htmlspecialchars($seller['city'] ?? '') : ''; ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="state" class="form-label">State/Province</label>
                                        <input type="text" class="form-control" id="state" name="state" 
                                               value="<?php echo $seller ? htmlspecialchars($seller['state'] ?? '') : ''; ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="postalCode" class="form-label">Postal Code</label>
                                        <input type="text" class="form-control" id="postalCode" name="postal_code" 
                                               value="<?php echo $seller ? htmlspecialchars($seller['postal_code'] ?? '') : ''; ?>">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="country" class="form-label">Country</label>
                                    <input type="text" class="form-control" id="country" name="country" 
                                           value="<?php echo $seller ? htmlspecialchars($seller['country'] ?? '') : ''; ?>">
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
                            <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Sales Summary</h5>
                        </div>
                        <div class="card-body">
                            <?php
                            // Get sales stats
                            try {
                                $stmt = $pdo->prepare("
                                    SELECT 
                                        COUNT(DISTINCT o.id) as total_orders,
                                        SUM(o.total) as total_revenue,
                                        COUNT(p.id) as total_products
                                    FROM users u
                                    LEFT JOIN products p ON u.id = p.seller_id
                                    LEFT JOIN order_items oi ON p.id = oi.product_id
                                    LEFT JOIN orders o ON oi.order_id = o.id AND o.status = 'delivered'
                                    WHERE u.id = ?
                                ");
                                $stmt->execute([$user['id']]);
                                $stats = $stmt->fetch();
                            } catch (Exception $e) {
                                $stats = ['total_orders' => 0, 'total_revenue' => 0, 'total_products' => 0];
                            }
                            ?>
                            
                            <div class="text-center mb-3">
                                <div class="display-6 text-primary">₱<?php echo number_format($stats['total_revenue'] ?? 0, 2); ?></div>
                                <div class="text-muted">Total Revenue</div>
                            </div>
                            
                            <div class="d-flex justify-content-between mb-2">
                                <span>Total Products</span>
                                <span class="fw-bold"><?php echo $stats['total_products'] ?? 0; ?></span>
                            </div>
                            
                            <div class="d-flex justify-content-between mb-2">
                                <span>Completed Orders</span>
                                <span class="fw-bold"><?php echo $stats['total_orders'] ?? 0; ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0"><i class="fas fa-cog me-2"></i>Account Settings</h5>
                        </div>
                        <div class="card-body">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>