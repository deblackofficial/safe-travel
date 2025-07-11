<?php
// Suppress all error output to prevent HTML in JSON response
error_reporting(0);
ini_set('display_errors', 0);

// Set JSON header first
header('Content-Type: application/json');

try {
    // Include database connection
    include 'conn.php';
    
    // Check if connection exists
    if (!isset($conn) || !$conn) {
        throw new Exception('Database connection failed');
    }
    
    // Check if it's a POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    // Get POST data
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $card_uid = isset($_POST['card_uid']) ? trim($_POST['card_uid']) : '';
    
    if ($user_id <= 0) {
        throw new Exception('Invalid user ID');
    }
    
    if (empty($card_uid)) {
        throw new Exception('Invalid card UID');
    }
    
    // Start transaction
    mysqli_autocommit($conn, false);
    
    try {
        // Check if user exists
        $userQuery = "SELECT id FROM users WHERE id = ?";
        $userStmt = mysqli_prepare($conn, $userQuery);
        mysqli_stmt_bind_param($userStmt, "i", $user_id);
        mysqli_stmt_execute($userStmt);
        $userResult = mysqli_stmt_get_result($userStmt);
        
        if (!mysqli_fetch_assoc($userResult)) {
            throw new Exception('User not found');
        }
        
        // Check if card is already assigned to another user
        $checkQuery = "SELECT user_id FROM rfid_cards WHERE card_uid = ? AND user_id != ?";
        $checkStmt = mysqli_prepare($conn, $checkQuery);
        mysqli_stmt_bind_param($checkStmt, "si", $card_uid, $user_id);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);
        
        if (mysqli_fetch_assoc($checkResult)) {
            throw new Exception('Card is already assigned to another user');
        }
        
        // Delete any existing card assignment for this user
        $deleteQuery = "DELETE FROM rfid_cards WHERE user_id = ?";
        $deleteStmt = mysqli_prepare($conn, $deleteQuery);
        mysqli_stmt_bind_param($deleteStmt, "i", $user_id);
        mysqli_stmt_execute($deleteStmt);
        
        // Insert new card assignment
        $insertQuery = "INSERT INTO rfid_cards (user_id, card_uid, assigned_at) VALUES (?, ?, NOW())";
        $insertStmt = mysqli_prepare($conn, $insertQuery);
        mysqli_stmt_bind_param($insertStmt, "is", $user_id, $card_uid);
        
        if (!mysqli_stmt_execute($insertStmt)) {
            throw new Exception('Failed to assign card: ' . mysqli_error($conn));
        }
        
        // Commit transaction
        mysqli_commit($conn);
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Card assigned successfully',
            'user_id' => $user_id,
            'card_uid' => $card_uid
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction
        mysqli_rollback($conn);
        throw $e;
    }
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

// Close database connection if it exists
if (isset($conn) && $conn) {
    mysqli_close($conn);
}
?>