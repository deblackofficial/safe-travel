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
    if (!$conn) {
        throw new Exception('Database connection failed');
    }
    
    // Get user_id from GET parameter
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
    
    if ($user_id <= 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid user ID'
        ]);
        exit;
    }
    
    // Query to get user information
    $query = "SELECT id, first_name, last_name, username, email FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    
    if (!$stmt) {
        throw new Exception('Failed to prepare user query: ' . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($user = mysqli_fetch_assoc($result)) {
        // User found, now check if they already have a card assigned
        $cardCheckQuery = "SELECT card_uid, assigned_at FROM rfid_cards WHERE user_id = ?";
        $cardStmt = mysqli_prepare($conn, $cardCheckQuery);
        
        if (!$cardStmt) {
            throw new Exception('Failed to prepare card check query: ' . mysqli_error($conn));
        }
        
        mysqli_stmt_bind_param($cardStmt, "i", $user_id);
        mysqli_stmt_execute($cardStmt);
        $cardResult = mysqli_stmt_get_result($cardStmt);
        $existingCard = mysqli_fetch_assoc($cardResult);
        
        // Prepare response based on whether user has existing card
        if ($existingCard) {
            echo json_encode([
                'status' => 'ok',
                'user' => [
                    'id' => $user['id'],
                    'first_name' => $user['first_name'],
                    'last_name' => $user['last_name'],
                    'username' => $user['username'],
                    'email' => $user['email']
                ],
                'existing_card' => [
                    'card_uid' => $existingCard['card_uid'],
                    'assigned_at' => $existingCard['assigned_at']
                ],
                'has_card' => true
            ]);
        } else {
            echo json_encode([
                'status' => 'ok',
                'user' => [
                    'id' => $user['id'],
                    'first_name' => $user['first_name'],
                    'last_name' => $user['last_name'],
                    'username' => $user['username'],
                    'email' => $user['email']
                ],
                'has_card' => false
            ]);
        }
        
        // Close card check statement
        mysqli_stmt_close($cardStmt);
    } else {
        // User not found
        echo json_encode([
            'status' => 'error',
            'message' => 'User not found'
        ]);
    }
    
    // Close user statement
    mysqli_stmt_close($stmt);
    
} catch (Exception $e) {
    // Return error as JSON
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

// Close database connection if it exists
if ($conn) {
    mysqli_close($conn);
}
?>