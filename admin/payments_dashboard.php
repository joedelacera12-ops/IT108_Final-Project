<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

require_role('admin');
$pdo = get_db();

// Get payment methods distribution
try {
    $paymentMethodsStmt = $pdo->prepare("
        SELECT 
            payment_method,
            COUNT(*) as count,
            SUM(amount) as total_amount
        FROM payment_history 
        WHERE status = 'completed'
        GROUP BY payment_method
        ORDER BY total_amount DESC
    ");
    $paymentMethodsStmt->execute();
    $paymentMethods = $paymentMethodsStmt ? $paymentMethodsStmt->fetchAll() : [];
} catch (Exception $e) {
    error_log("Error fetching payment methods: " . $e->getMessage());
    $paymentMethods = [];
}

// Get recent payments
try {
    $recentPaymentsStmt = $pdo->prepare("
        SELECT 
            ph.id,
            ph.amount,
            ph.payment_method,
            ph.created_at as payment_date,
            ph.status,
            u.first_name,
            u.last_name,
            u.email
        FROM payment_history ph
        JOIN users u ON ph.user_id = u.id
        WHERE ph.created_at IS NOT NULL
        ORDER BY ph.created_at DESC
        LIMIT 10
    ");
    $recentPaymentsStmt->execute();
    $recentPayments = $recentPaymentsStmt ? $recentPaymentsStmt->fetchAll() : [];
} catch (Exception $e) {
    error_log("Error fetching recent payments: " . $e->getMessage());
    $recentPayments = [];
}

// Get subscription stats
try {
    $subscriptionStatsStmt = $pdo->prepare("
        SELECT 
            s.plan_type,
            s.status,
            COUNT(*) as count,
            COALESCE(SUM(ph.amount), 0) as total_amount
        FROM subscriptions s
        LEFT JOIN payment_history ph ON s.id = ph.subscription_id
        GROUP BY s.plan_type, s.status
    ");
    $subscriptionStatsStmt->execute();
    $subscriptionStats = $subscriptionStatsStmt ? $subscriptionStatsStmt->fetchAll() : [];
} catch (Exception $e) {
    error_log("Error fetching subscription stats: " . $e->getMessage());
    $subscriptionStats = [];
}

// Calculate totals
$totalPayments = array_sum(array_column($paymentMethods, 'total_amount'));
$totalTransactions = array_sum(array_column($paymentMethods, 'count'));

// Since this is now included in the dashboard, we don't need the full HTML structure
// Just the content part
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="text-success"><i class="fas fa-credit-card me-2"></i>Payments Dashboard</h2>
            <p class="text-muted">Manage and monitor all payment transactions</p>
        </div>
    </div>

    <!-- Key Metrics -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card stat-card dashboard-card">
                <div class="card-body">
                    <h6 class="text-muted">Total Revenue</h6>
                    <h3 class="text-primary">₱<?php echo number_format($totalPayments ?? 0, 2); ?></h3>
                    <small class="text-muted">All time</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card dashboard-card">
                <div class="card-body">
                    <h6 class="text-muted">Total Transactions</h6>
                    <h3 class="text-success"><?php echo number_format($totalTransactions ?? 0); ?></h3>
                    <small class="text-muted">Completed payments</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card dashboard-card">
                <div class="card-body">
                    <h6 class="text-muted">Active Subscriptions</h6>
                    <h3 class="text-warning">
                        <?php 
                        $activeSubs = array_filter($subscriptionStats, function($sub) { return ($sub['status'] ?? '') === 'active'; });
                        echo number_format(array_sum(array_column($activeSubs, 'count')));
                        ?>
                    </h3>
                    <small class="text-muted">Current period</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card dashboard-card">
                <div class="card-body">
                    <h6 class="text-muted">Top Payment Method</h6>
                    <h3 class="text-danger">
                        <?php echo !empty($paymentMethods) ? htmlspecialchars($paymentMethods[0]['payment_method'] ?? 'N/A') : 'N/A'; ?>
                    </h3>
                    <small class="text-muted">
                        ₱<?php echo !empty($paymentMethods) ? number_format($paymentMethods[0]['total_amount'] ?? 0, 2) : '0.00'; ?>
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="row mb-4">
        <div class="col-lg-8 mb-3">
            <div class="card dashboard-card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Payment Methods Distribution</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="paymentMethodsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 mb-3">
            <div class="card dashboard-card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Subscription Plans</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="subscriptionsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Payments Table -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card dashboard-card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recent Payments</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Payment ID</th>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentPayments as $payment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($payment['id']); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?>
                                            <div class="small text-muted"><?php echo htmlspecialchars($payment['email']); ?></div>
                                        </td>
                                        <td>₱<?php echo number_format($payment['amount'], 2); ?></td>
                                        <td>
                                            <i class="fas fa-<?php 
                                                echo ($payment['payment_method'] === 'gcash') ? 'mobile-alt' : 
                                                     (($payment['payment_method'] === 'paymaya') ? 'wallet' : 
                                                     (($payment['payment_method'] === 'card') ? 'credit-card' : 'university'));
                                            ?> payment-method-icon <?php echo htmlspecialchars($payment['payment_method']); ?> me-1"></i>
                                            <?php echo htmlspecialchars(ucfirst($payment['payment_method'])); ?>
                                        </td>
                                        <td><?php echo date('M j, Y g:i A', strtotime($payment['payment_date'])); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo ($payment['status'] === 'completed') ? 'success' : 
                                                     (($payment['status'] === 'pending') ? 'warning' : 'danger');
                                            ?>">
                                                <?php echo ucfirst(htmlspecialchars($payment['status'])); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($recentPayments)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No recent payments found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Payment Methods Chart
    const paymentCtx = document.getElementById('paymentMethodsChart').getContext('2d');
    const paymentLabels = <?php echo json_encode(array_column($paymentMethods, 'payment_method')); ?>;
    const paymentData = <?php echo json_encode(array_column($paymentMethods, 'total_amount')); ?>;
    
    new Chart(paymentCtx, {
        type: 'doughnut',
        data: {
            labels: paymentLabels,
            datasets: [{
                data: paymentData,
                backgroundColor: [
                    '#0d6efd',
                    '#28a745',
                    '#ffc107',
                    '#dc3545',
                    '#6f42c1'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw || 0;
                            return `${label}: ₱${value.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
                        }
                    }
                }
            }
        }
    });
    
    // Subscription Plans Chart
    const subscriptionCtx = document.getElementById('subscriptionsChart').getContext('2d');
    const subscriptionLabels = <?php echo json_encode(array_column($subscriptionStats, 'plan_type')); ?>;
    const subscriptionData = <?php echo json_encode(array_column($subscriptionStats, 'count')); ?>;
    
    new Chart(subscriptionCtx, {
        type: 'bar',
        data: {
            labels: subscriptionLabels,
            datasets: [{
                label: 'Subscriptions',
                data: subscriptionData,
                backgroundColor: '#0d6efd',
                borderColor: '#0d6efd',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
});
</script>

<style>
.dashboard-card {
    border-radius: 15px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
    border: none;
}
.dashboard-card:hover {
    transform: translateY(-5px);
}
.stat-card {
    border-left: 4px solid #0d6efd;
}
.stat-card:nth-child(2) {
    border-left-color: #28a745;
}
.stat-card:nth-child(3) {
    border-left-color: #ffc107;
}
.stat-card:nth-child(4) {
    border-left-color: #dc3545;
}
.chart-container {
    position: relative;
    height: 300px;
}
.payment-method-icon {
    font-size: 1.5rem;
    margin-right: 10px;
}
.gcash { color: #0064d2; }
.paymaya { color: #0d6efd; }
.card { color: #28a745; }
.bank-transfer { color: #ffc107; }
</style>