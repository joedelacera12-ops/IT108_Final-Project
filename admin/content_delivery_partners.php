<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';

require_role('admin');
$pdo = get_db();

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['partner_id'], $_POST['status'])) {
    try {
        $stmt = $pdo->prepare('UPDATE delivery_partners SET status = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$_POST['status'], $_POST['partner_id']]);
        // Redirect to avoid form resubmission
        header('Location: dashboard.php?page=delivery_partners');
        exit;
    } catch (Exception $e) {
        // Log error or handle it gracefully
        error_log("Failed to update delivery partner status: " . $e->getMessage());
    }
}

// Fetch all delivery partners
$partners = [];
try {
    $stmt = $pdo->query('SELECT * FROM delivery_partners ORDER BY created_at DESC');
    $partners = $stmt->fetchAll();
} catch (Exception $e) {
    // Log error or handle it gracefully
    error_log("Failed to fetch delivery partners: " . $e->getMessage());
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Manage Delivery Partners</h1>
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Delivery Partner Accounts</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="partnersTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Registered On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($partners)): ?>
                            <tr>
                                <td colspan="7" class="text-center">No delivery partners found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($partners as $partner): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($partner['id']); ?></td>
                                    <td><?php echo htmlspecialchars($partner['name']); ?></td>
                                    <td><?php echo htmlspecialchars($partner['email']); ?></td>
                                    <td><?php echo htmlspecialchars($partner['phone']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $partner['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                            <?php echo ucfirst(htmlspecialchars($partner['status'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($partner['created_at'])); ?></td>
                                    <td>
                                        <form method="POST" style="display: inline-block;">
                                            <input type="hidden" name="partner_id" value="<?php echo $partner['id']; ?>">
                                            <?php if ($partner['status'] === 'active'): ?>
                                                <input type="hidden" name="status" value="inactive">
                                                <button type="submit" class="btn btn-sm btn-warning">Deactivate</button>
                                            <?php else: ?>
                                                <input type="hidden" name="status" value="active">
                                                <button type="submit" class="btn btn-sm btn-success">Activate</button>
                                            <?php endif; ?>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>