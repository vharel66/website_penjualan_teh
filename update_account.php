<?php
require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_account'])) {
    $username = cleanInput($_POST['username']);
    $email = cleanInput($_POST['email']);
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        exit();
    }
    
    $user_id = $_SESSION['user_id'];
    
    // Get current user data for password verification
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $current_user = $result->fetch_assoc();
    
    // Validate current password if changing password
    if (!empty($new_password) && !password_verify($current_password, $current_user['password'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Password saat ini salah']);
        exit();
    }
    
    // Update username and email
    $stmt = $conn->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
    $stmt->bind_param("ssi", $username, $email, $user_id);
    
    if (!$stmt->execute()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Gagal memperbarui profil']);
        exit();
    }
    
    // Update password if provided
    if (!empty($new_password)) {
        if ($new_password !== $confirm_password) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Password baru tidak cocok']);
            exit();
        }
        
        if (strlen($new_password) < 6) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Password minimal 6 karakter']);
            exit();
        }
        
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed_password, $user_id);
        
        if (!$stmt->execute()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Gagal memperbarui password']);
            exit();
        }
    }
    
    // Update session username
    $_SESSION['username'] = $username;
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Profil berhasil diperbarui']);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
