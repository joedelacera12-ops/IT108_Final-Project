<?php
// Run this script once to create default test accounts: admin, buyer, seller (pending)
require_once __DIR__ . '/../includes/db.php';

$pdo = get_db();

$accounts = [
    [
        'first_name' => 'Admin',
        'last_name' => 'User',
        'email' => 'admin@agrisea.local',
        'password' => '@Abcde12345',
        'role' => 'admin',
        'status' => 'approved'
    ],
    [
        'first_name' => 'Test',
        'last_name' => 'Buyer',
        'email' => 'buyer@gmail.com',
        'password' => '@Abcde12345',
        'role' => 'buyer',
        'status' => 'approved'
    ],
    [
        'first_name' => 'Test',
        'last_name' => 'Farmer',
        'email' => 'farmer@gmail.com',
        'password' => '@Abcde12345',
        'role' => 'seller',
        'status' => 'approved'
    ]
];

foreach ($accounts as $a) {
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$a['email']]);
    $exists = $stmt->fetchColumn();

    if ($exists) {
        // Update existing account with new password
        $hash = password_hash($a['password'], PASSWORD_DEFAULT);
        $update = $pdo->prepare('UPDATE users SET password_hash = ?, first_name = ?, last_name = ?, role_id = (SELECT id FROM user_roles WHERE name = ? LIMIT 1), status = ? WHERE email = ?');
        $update->execute([
            $hash,
            $a['first_name'],
            $a['last_name'],
            $a['role'],
            $a['status'],
            $a['email']
        ]);
        echo "Updated user {$a['email']} (id={$exists}) role={$a['role']} status={$a['status']} password={$a['password']}\n";
        continue;
    }

    // Resolve role_id from user_roles by role name
    $roleStmt = $pdo->prepare('SELECT id FROM user_roles WHERE name = ? LIMIT 1');
    $roleStmt->execute([$a['role']]);
    $roleId = $roleStmt->fetchColumn();
    if (!$roleId) {
        throw new RuntimeException("Role '{$a['role']}' not found. Run migrations to seed user_roles.");
    }

    $hash = password_hash($a['password'], PASSWORD_DEFAULT);
    $insert = $pdo->prepare('INSERT INTO users (first_name, last_name, email, password_hash, role_id, status) VALUES (?, ?, ?, ?, ?, ?)');
    $insert->execute([
        $a['first_name'],
        $a['last_name'],
        $a['email'],
        $hash,
        $roleId,
        $a['status']
    ]);

    $id = $pdo->lastInsertId();
    echo "Created user {$a['email']} (id={$id}) role={$a['role']} status={$a['status']} password={$a['password']}\n";
}

echo "Done.\n";
?>