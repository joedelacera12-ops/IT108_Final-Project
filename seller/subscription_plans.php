<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

require_role('seller');
$user = current_user();
$pdo = get_db();

// Get current subscription if exists
$currentSubscription = null;
try {
    $stmt = $pdo->prepare('SELECT * FROM subscriptions WHERE user_id = ? ORDER BY created_at DESC LIMIT 1');
    $stmt->execute([$user['id']]);
    $currentSubscription = $stmt->fetch();
} catch (Exception $e) {
    $currentSubscription = null;
}

// Check if user has already made a subscription this month (to show red alert)
$subscriptionThisMonth = false;
try {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM subscriptions WHERE user_id = ? AND YEAR(created_at) = YEAR(NOW()) AND MONTH(created_at) = MONTH(NOW())');
    $stmt->execute([$user['id']]);
    $subscriptionCount = (int)$stmt->fetchColumn();
    
    if ($subscriptionCount > 0) {
        $subscriptionThisMonth = true;
    }
} catch (Exception $e) {
    // Ignore errors
}

// Check if user has an active subscription (to show green alert)
$hasActiveSubscription = false;
try {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM subscriptions WHERE user_id = ? AND status = "active" AND end_date >= NOW()');
    $stmt->execute([$user['id']]);
    $activeSubscriptionCount = (int)$stmt->fetchColumn();
    
    if ($activeSubscriptionCount > 0) {
        $hasActiveSubscription = true;
    }
} catch (Exception $e) {
    // Ignore errors
}

// Check if user has an active subscription (for subscription limit)
$subscriptionLimitReached = false;
try {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM subscriptions WHERE user_id = ? AND status = "active" AND end_date >= NOW()');
    $stmt->execute([$user['id']]);
    $subscriptionCount = (int)$stmt->fetchColumn();
    
    if ($subscriptionCount > 0) {
        $subscriptionLimitReached = true;
    }
} catch (Exception $e) {
    // Ignore errors
}

// Define subscription plans
$plans = [
    'basic' => [
        'name' => 'Basic Plan',
        'price' => 0,
        'description' => 'Free plan with basic features for 3 months',
        'features' => [
            'List up to 10 products',
            'Basic product analytics',
            'Standard support',
            '3 months access'
        ]
    ],
    'premium' => [
        'name' => 'Premium Plan',
        'price' => 299,
        'description' => 'Enhanced visibility and features',
        'features' => [
            'Unlimited product listings',
            'Featured product placement',
            'Advanced analytics',
            'Priority support',
            'Marketing tools'
        ]
    ],
    'pro' => [
        'name' => 'Pro Plan',
        'price' => 599,
        'description' => 'Maximum exposure and professional tools',
        'features' => [
            'Unlimited product listings',
            'Premium featured placement',
            'Advanced analytics & reporting',
            'Dedicated account manager',
            'Marketing & promotional tools',
            'Early access to new features'
        ]
    ]
];
?>
<div class="row mt-4">
    <div class="col-12">
        <h3>Subscription Plans</h3>
        <?php $displayType = get_seller_type_display($pdo); ?>
        <p class="text-muted">Choose a plan that fits your business needs<?php if (!empty($displayType)): ?> (<?= htmlspecialchars($displayType) ?> Account)<?php endif; ?></p>
        <?php if ($subscriptionThisMonth && !$hasActiveSubscription): ?>
            <div class="alert alert-danger subscription-alert">
                You have already subscribed this month.
            </div>
        <?php elseif ($hasActiveSubscription): ?>
            <div class="alert alert-success">
                You are still within the validity of an active plan.
            </div>
        <?php endif; ?>
        
        <div class="row">
            <?php foreach ($plans as $planKey => $plan): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 <?= ($currentSubscription && $currentSubscription['plan_type'] === $planKey) ? 'border-success' : '' ?>">
                        <div class="card-header bg-<?= $planKey === 'premium' ? 'success' : ($planKey === 'pro' ? 'primary' : 'secondary') ?> text-white">
                            <h4 class="my-0"><?= htmlspecialchars($plan['name']) ?></h4>
                        </div>
                        <div class="card-body">
                            <h1 class="card-title pricing-card-title">
                                ₱<?= number_format($plan['price'], 2) ?>
                                <?php if ($plan['price'] > 0): ?>
                                    <small class="text-muted">/ mo</small>
                                <?php else: ?>
                                    <small class="text-muted">/ forever</small>
                                <?php endif; ?>
                            </h1>
                            <p class="card-text"><?= htmlspecialchars($plan['description']) ?></p>
                            <ul class="list-unstyled mt-3 mb-4">
                                <?php foreach ($plan['features'] as $feature): ?>
                                    <li class="mb-1"><i class="fas fa-check text-success me-2"></i><?= htmlspecialchars($feature) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <div class="card-footer">
                            <?php if ($subscriptionLimitReached): ?>
                                <button class="btn btn-secondary w-100" disabled>Subscription Limit Reached</button>
                            <?php elseif ($currentSubscription && $currentSubscription['plan_type'] === $planKey): ?>
                                <?php if ($currentSubscription['status'] === 'active'): ?>
                                    <button class="btn btn-success w-100" disabled>Current Plan</button>
                                <?php else: ?>
                                    <button class="btn btn-<?= $planKey === 'premium' ? 'success' : ($planKey === 'pro' ? 'primary' : 'secondary') ?> w-100" 
                                            onclick="subscribeToPlan('<?= $planKey ?>')">Subscribe</button>
                                <?php endif; ?>
                            <?php else: ?>
                                <?php if ($planKey === 'basic' && $currentSubscription && $currentSubscription['plan_type'] !== 'basic' && $currentSubscription['status'] === 'active'): ?>
                                    <!-- Prevent downgrading from paid plans to free plan -->
                                    <button class="btn btn-secondary w-100" disabled>Active Paid Plan</button>
                                <?php else: ?>
                                    <button class="btn btn-<?= $planKey === 'premium' ? 'success' : ($planKey === 'pro' ? 'primary' : 'secondary') ?> w-100" 
                                        onclick="subscribeToPlan('<?= $planKey ?>')">Subscribe</button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
function subscribeToPlan(planType) {
    if (planType === 'basic') {
        // For basic plan, just update the user's subscription
        fetch('/ecommerce_farmers_fishers/seller/process_subscription.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'plan_type=basic'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Successfully subscribed to Basic plan!', 'success');
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showNotification(data.error || 'Error subscribing to plan', 'danger');
                // If the error is about subscription limit, disable the button temporarily
                if (data.error && data.error.includes('only make one subscription per month')) {
                    const buttons = document.querySelectorAll('button[onclick^="subscribeToPlan"]');
                    buttons.forEach(button => {
                        button.disabled = true;
                        button.classList.add('btn-secondary');
                        button.textContent = 'You have already subscribed this month.';
                    });
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error subscribing to plan', 'danger');
        });
    } else {
        // For paid plans, redirect to payment page
        window.location.href = `/ecommerce_farmers_fishers/seller/payment.php?plan=${planType}`;
    }
}
</script>