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
    
    // Query to get the latest card UID with status
    $query = "SELECT card_uid, card_status, user_info FROM temp_card ORDER BY id DESC LIMIT 1";
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        throw new Exception('Database query failed: ' . mysqli_error($conn));
    }
    
    if ($row = mysqli_fetch_assoc($result)) {
        $card_uid = trim($row['card_uid']);
        $card_status = $row['card_status'] ?? 'available';
        $user_info = $row['user_info'] ? json_decode($row['user_info'], true) : null;
        
        // Clear the temp table after reading
        $deleteQuery = "DELETE FROM temp_card";
        mysqli_query($conn, $deleteQuery);
        
        // Return response based on card status
        if ($card_status === 'registered') {
            echo json_encode([
                'status' => 'registered',
                'uid' => $card_uid,
                'user_info' => $user_info,
                'timestamp' => time()
            ]);
        } else {
            echo json_encode([
                'status' => 'available',
                'uid' => $card_uid,
                'timestamp' => time()
            ]);
        }
    } else {
        // No card found
        echo json_encode([
            'status' => 'empty', 
            'uid' => null,
            'timestamp' => time()
        ]);
    }
    
} catch (Exception $e) {
    // Return error as JSON
    echo json_encode([
        'status' => 'error', 
        'message' => $e->getMessage(),
        'timestamp' => time()
    ]);
}

// Close database connection if it exists
if ($conn) {
    mysqli_close($conn);
}
?>