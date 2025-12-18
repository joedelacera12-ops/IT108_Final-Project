<?php
// generate_data.php

// Include the database connection
require_once 'c:/xamppp/htdocs/ecommerce_farmers_fishers/includes/db.php';

// Get the PDO connection
$pdo = get_db();

// Function to generate random dates within the last 12 months
function random_date_last_12_months() {
    $start = new DateTime('-12 months');
    $end = new DateTime();
    $random_timestamp = mt_rand($start->getTimestamp(), $end->getTimestamp());
    return date("Y-m-d H:i:s", $random_timestamp);
}

// Function to generate a random string
function generate_random_string($length = 10) {
    return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
}

// Get existing user IDs for buyers and sellers
$buyer_ids = [];
$seller_ids = [];
$stmt = $pdo->query("SELECT id, seller_type FROM users WHERE role_id IN (2, 3)");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if ($row['seller_type'] !== null) {
        $seller_ids[] = $row['id'];
    } else {
        $buyer_ids[] = $row['id'];
    }
}

// Get existing category IDs
$category_ids = [];
$stmt = $pdo->query("SELECT id FROM categories");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $category_ids[] = $row['id'];
}

// Get existing product IDs and their sellers
$product_data = [];
$stmt = $pdo->query("SELECT id, seller_id, price FROM products");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $product_data[] = $row;
}

// Generate Delivery Partners
$delivery_partners_to_generate = 5;
for ($i = 0; $i < $delivery_partners_to_generate; $i++) {
    $name = "Delivery Partner " . ($i + 1);
    $phone = "09" . rand(100000000, 999999999);
    $email = "partner" . ($i + 1) . "@example.com";
    $address = "123 Partner St, City, Country";
    $created_at = random_date_last_12_months();

    $sql = "INSERT INTO delivery_partners (name, phone, email, address, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, 1, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$name, $phone, $email, $address, $created_at, $created_at]);
}

// Get existing delivery partner IDs
$delivery_partner_ids = [];
$stmt = $pdo->query("SELECT id FROM delivery_partners");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $delivery_partner_ids[] = $row['id'];
}

// Generate Users
for ($i = 0; $i < 1000; $i++) {
    $first_name = "GeneratedUser" . $i;
    $last_name = "Test";
    $email = "generated.user." . uniqid() . $i . "@example.com";
    $phone = "09" . rand(100000000, 999999999);
    $password = password_hash("password", PASSWORD_DEFAULT);
    $role_id = (rand(0, 1) == 0) ? 2 : 3; // 2 for seller, 3 for buyer
    $seller_type = ($role_id == 2) ? ((rand(0, 1) == 0) ? 'farmer' : 'fisher') : null;
    $created_at = random_date_last_12_months();

    $sql = "INSERT INTO users (first_name, last_name, email, phone, password_hash, role_id, seller_type, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$first_name, $last_name, $email, $phone, $password, $role_id, $seller_type, $created_at, $created_at]);
    $new_user_id = $pdo->lastInsertId();
    if ($role_id == 2) {
        $seller_ids[] = $new_user_id;
    } else {
        $buyer_ids[] = $new_user_id;
    }
}

// Generate Products
for ($i = 0; $i < 2000; $i++) {
    $seller_id = $seller_ids[array_rand($seller_ids)];
    $category_id = $category_ids[array_rand($category_ids)];
    $name = "Generated Product " . uniqid() . $i;
    $slug = "generated-product-" . uniqid() . $i;
    $description = "This is a generated test product.";
    $price = rand(100, 5000);
    $stock = rand(10, 1000);
    $created_at = random_date_last_12_months();

    $sql = "INSERT INTO products (seller_id, category_id, name, slug, description, price, stock, created_at, updated_at, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'published')";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$seller_id, $category_id, $name, $slug, $description, $price, $stock, $created_at, $created_at]);
    $new_product_id = $pdo->lastInsertId();
    $product_data[] = ['id' => $new_product_id, 'seller_id' => $seller_id, 'price' => $price];
}

// Generate Orders, Order Items, Payments, Deliveries, and Notifications
for ($i = 0; $i < 7000; $i++) {
    $user_id = $buyer_ids[array_rand($buyer_ids)];
    $order_date = random_date_last_12_months();
    $order_number = 'ORD-' . date('Ymd') . '-' . strtoupper(generate_random_string(10));
    $payment_method = ['gcash', 'paymaya', 'card', 'bank_transfer'][array_rand(['gcash', 'paymaya', 'card', 'bank_transfer'])];
    
    // Insert Order
    $sql = "INSERT INTO orders (user_id, order_number, status, payment_method, payment_status, created_at, updated_at) VALUES (?, ?, 'pending', ?, 'pending', ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id, $order_number, $payment_method, $order_date, $order_date]);
    $order_id = $pdo->lastInsertId();

    // Insert Order Items
    $total_order_price = 0;
    $num_items = rand(1, 5);
    for ($j = 0; $j < $num_items; $j++) {
        $product = $product_data[array_rand($product_data)];
        $product_id = $product['id'];
        $seller_id = $product['seller_id'];
        $quantity = rand(1, 3);
        $unit_price = $product['price'];
        $total_price = $quantity * $unit_price;
        $total_order_price += $total_price;

        $sql = "INSERT INTO order_items (order_id, product_id, seller_id, quantity, unit_price, total, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$order_id, $product_id, $seller_id, $quantity, $unit_price, $total_price, $order_date, $order_date]);
    }

    // Update Order Total
    $sql = "UPDATE orders SET total = ?, subtotal = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$total_order_price, $total_order_price, $order_id]);

    // Simulate Order Flow: Placed -> Shipped -> Delivered
    $shipped_date = date('Y-m-d H:i:s', strtotime($order_date . ' +1 day'));
    $delivered_date = date('Y-m-d H:i:s', strtotime($shipped_date . ' +2 days'));

    // Update Order Status to Shipped
    $sql = "UPDATE orders SET status = 'shipped', updated_at = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$shipped_date, $order_id]);

    // Insert Delivery
    $delivery_partner_id = $delivery_partner_ids[array_rand($delivery_partner_ids)];
    $sql = "INSERT INTO order_deliveries (order_id, delivery_partner_id, status, assigned_at, picked_up_at, out_for_delivery_at, delivered_at, created_at, updated_at) VALUES (?, ?, 'delivered', ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$order_id, $delivery_partner_id, $order_date, $shipped_date, $shipped_date, $delivered_date, $order_date, $delivered_date]);
    $delivery_id = $pdo->lastInsertId();

    // Update Order with Delivery ID and Delivered Status
    $sql = "UPDATE orders SET status = 'delivered', delivery_id = ?, updated_at = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$delivery_id, $delivered_date, $order_id]);

    // Insert Payment History
    $transaction_id = 'TXN-' . strtoupper(generate_random_string(12));
    $sql = "INSERT INTO payment_history (user_id, order_id, amount, payment_method, transaction_id, status, processed_at, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 'completed', ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id, $order_id, $total_order_price, $payment_method, $transaction_id, $order_date, $order_date, $order_date]);

    // Update order payment_status to paid
    $sql = "UPDATE orders SET payment_status = 'paid' WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$order_id]);

    // Insert Notifications
    $sql = "INSERT INTO notifications (user_id, type, title, message, related_id, related_type, created_at) VALUES (?, 'order', 'Order Delivered', ?, ?, 'order', ?)";
    $stmt = $pdo->prepare($sql);
    $message = "Your order #" . $order_id . " has been delivered.";
    $stmt->execute([$user_id, $message, $order_id, $delivered_date]);

    // Insert Product and Seller Reviews
    $product_review_rating = rand(3, 5);
    $seller_review_rating = rand(3, 5);
    $review_text = "This is a great product!";
    
    $sql = "INSERT INTO product_reviews (product_id, user_id, order_id, rating, review, created_at) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$product_id, $user_id, $order_id, $product_review_rating, $review_text, $delivered_date]);

    $sql = "INSERT INTO seller_ratings (seller_id, buyer_id, order_id, rating, review, created_at) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$seller_id, $user_id, $order_id, $seller_review_rating, $review_text, $delivered_date]);
}

echo "Successfully generated and inserted 10,000 data points.";

?>