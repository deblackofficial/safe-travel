<?php
session_start();
include '../conn.php';

// Check if the user is an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Unauthorized access.");
}

if (isset($_GET['id']) && isset($_GET['action'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];

    if ($action === 'activate') {
        $sql = "UPDATE users SET status = 'active' WHERE id = $id";
    } elseif ($action === 'deactivate') {
        $sql = "UPDATE users SET status = 'inactive' WHERE id = $id";
    } else {
        die("Invalid action.");
    }

    if (mysqli_query($conn, $sql)) {
        header('Location: userdetails.php');
        exit();
    } else {
        die("Error updating user status: " . mysqli_error($conn));
    }
}
?>