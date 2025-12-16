<?php
require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        exit();
    }
    
    $user_id = $_SESSION['user_id'];
    
    try {
        // Begin transaction
        $conn->begin_transaction();
        
        // Delete user's cart items
        $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        // Delete user's order items (cascade will handle orders)
        $stmt = $conn->prepare("
            DELETE oi FROM order_items oi 
            INNER JOIN orders o ON oi.order_id = o.id 
            WHERE o.user_id = ?
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        // Delete user's orders
        $stmt = $conn->prepare("DELETE FROM orders WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        // Delete user account
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        // Destroy session
        session_destroy();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Akun berhasil dihapus']);
        
    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollback();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Gagal menghapus akun: ' . $e->getMessage()]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
