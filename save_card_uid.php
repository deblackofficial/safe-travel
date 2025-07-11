<?php
// Arduino endpoint - receives card UID and processes it
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

try {
    include 'conn.php';
    
    if (!$conn) {
        throw new Exception('Database connection failed');
    }
    
    // Get UID from GET parameter (as sent by Arduino)
    $card_uid = isset($_GET['uid']) ? trim(strtoupper($_GET['uid'])) : '';
    
    if (empty($card_uid)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'No card UID provided',
            'action' => 'error_beep'
        ]);
        exit;
    }
    
    // Check if this card is already registered to someone
    $checkQuery = "SELECT u.id, u.first_name, u.last_name, u.username, u.email, r.assigned_at
                   FROM rfid_cards r
                   JOIN users u ON r.user_id = u.id
                   WHERE r.card_uid = ?";
    
    $stmt = mysqli_prepare($conn, $checkQuery);
    mysqli_stmt_bind_param($stmt, "s", $card_uid);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        // Card is already registered - store in temp with user info
        $clearQuery = "DELETE FROM temp_card";
        mysqli_query($conn, $clearQuery);
        
        $insertQuery = "INSERT INTO temp_card (card_uid, card_status, user_info) VALUES (?, 'registered', ?)";
        $userInfo = json_encode([
            'user_id' => $row['id'],
            'name' => $row['first_name'] . ' ' . $row['last_name'],
            'username' => $row['username'],
            'email' => $row['email'],
            'assigned_at' => $row['assigned_at']
        ]);
        
        $stmt2 = mysqli_prepare($conn, $insertQuery);
        mysqli_stmt_bind_param($stmt2, "ss", $card_uid, $userInfo);
        mysqli_stmt_execute($stmt2);
        
        echo json_encode([
            'status' => 'registered',
            'message' => 'Card already registered',
            'card_uid' => $card_uid,
            'user' => [
                'id' => $row['id'],
                'name' => $row['first_name'] . ' ' . $row['last_name'],
                'username' => $row['username'],
                'email' => $row['email'],
                'assigned_at' => $row['assigned_at']
            ],
            'action' => 'success_beep'
        ]);
        
    } else {
        // Card is not registered - store as new card
        $clearQuery = "DELETE FROM temp_card";
        mysqli_query($conn, $clearQuery);
        
        $insertQuery = "INSERT INTO temp_card (card_uid, card_status, user_info) VALUES (?, 'new', NULL)";
        $stmt3 = mysqli_prepare($conn, $insertQuery);
        mysqli_stmt_bind_param($stmt3, "s", $card_uid);
        mysqli_stmt_execute($stmt3);
        
        echo json_encode([
            'status' => 'new',
            'message' => 'New card detected',
            'card_uid' => $card_uid,
            'action' => 'new_card_beep'
        ]);
    }
    
    // Close prepared statements
    if (isset($stmt)) mysqli_stmt_close($stmt);
    if (isset($stmt2)) mysqli_stmt_close($stmt2);
    if (isset($stmt3)) mysqli_stmt_close($stmt3);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error occurred',
        'action' => 'error_beep'
    ]);
} finally {
    // Close database connection
    if (isset($conn)) {
        mysqli_close($conn);
    }
}
?>