<?php
include '../conn.php'; // Include the database connection file

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $sql = "UPDATE passenger_report SET archived = 1 WHERE id = $id";
    if (mysqli_query($conn, $sql)) {
        header('Location: passengerdetails.php');
        exit();
    } else {
        echo "Error archiving record: " . mysqli_error($conn);
    }
}
?>