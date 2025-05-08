<?php
include 'conn.php';

if ($conn) {
    echo "Database connection is working!";
} else {
    echo "Database connection failed: " . mysqli_connect_error();
}
?>