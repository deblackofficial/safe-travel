<?php
// Turn off all error reporting to prevent HTML output
error_reporting(0);

// Function to send JSON error response
function sendJsonError($message, $code = 400) {
    http_response_code($code);
    header('Content-Type: application/json');
    die(json_encode(['status' => 'error', 'message' => $message]));
}
?>