<?php
/**
 * AgriSea Marketplace - Password Reset Processing
 * This script processes password reset requests using security questions
 */

// Include required files
require_once __DIR__ . '/includes/db.php';

// Set JSON header for AJAX responses
header('Content-Type: application/json');

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $pdo = get_db();
    
    // Get the action from the request
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'get_security_question':
            $email = trim($_POST['email'] ?? '');
            
            if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'error' => 'Please enter a valid email address.']);
                exit;
            }
            
            // Get user and their security question
            $stmt = $pdo->prepare('SELECT u.id, u.email, sq.question_1 FROM users u JOIN user_security_questions sq ON u.id = sq.user_id WHERE u.email = ? LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if (!$user) {
                echo json_encode(['success' => false, 'error' => 'No account found with that email address.']);
                exit;
            }
            
            echo json_encode([
                'success' => true,
                'user_id' => $user['id'],
                'question' => $user['question_1']
            ]);
            exit;
            
        case 'validate_security_answer':
            $user_id = (int)($_POST['user_id'] ?? 0);
            $answer = trim($_POST['answer'] ?? '');
            
            if (!$user_id || !$answer) {
                echo json_encode(['success' => false, 'error' => 'Invalid request.']);
                exit;
            }
            
            // Get the hashed answer from the database
            $stmt = $pdo->prepare('SELECT answer_1 FROM user_security_questions WHERE user_id = ? LIMIT 1');
            $stmt->execute([$user_id]);
            $hashed_answer = $stmt->fetchColumn();
            
            if (!$hashed_answer) {
                echo json_encode(['success' => false, 'error' => 'Security question not found.']);
                exit;
            }
            
            // Verify the answer
            if (!password_verify($answer, $hashed_answer)) {
                echo json_encode(['success' => false, 'error' => 'Incorrect answer. Please try again.']);
                exit;
            }
            
            echo json_encode(['success' => true]);
            exit;
            
        case 'reset_password':
            $user_id = (int)($_POST['user_id'] ?? 0);
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            if (!$user_id || !$new_password || !$confirm_password) {
                echo json_encode(['success' => false, 'error' => 'Please fill in all fields.']);
                exit;
            }
            
            if ($new_password !== $confirm_password) {
                echo json_encode(['success' => false, 'error' => 'Passwords do not match.']);
                exit;
            }
            
            // Enhanced password validation
            if (strlen($new_password) < 8) {
                echo json_encode(['success' => false, 'error' => 'Password must be at least 8 characters long.']);
                exit;
            }
            
            if (!preg_match('/[A-Z]/', $new_password)) {
                echo json_encode(['success' => false, 'error' => 'Password must contain at least one uppercase letter.']);
                exit;
            }
            
            if (!preg_match('/[a-z]/', $new_password)) {
                echo json_encode(['success' => false, 'error' => 'Password must contain at least one lowercase letter.']);
                exit;
            }
            
            if (!preg_match('/\d/', $new_password)) {
                echo json_encode(['success' => false, 'error' => 'Password must contain at least one number.']);
                exit;
            }
            
            if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $new_password)) {
                echo json_encode(['success' => false, 'error' => 'Password must contain at least one special character.']);
                exit;
            }
            
            // Hash the new password
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update the user's password
            $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            $stmt->execute([$password_hash, $user_id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Password successfully reset! You can now log in with your new password.'
            ]);
            exit;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action.']);
            exit;
    }
    
} catch (Exception $e) {
    // In production log error. For now, show a basic error.
    error_log('Password reset error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error. Please try again later.']);
    exit;
}
?>