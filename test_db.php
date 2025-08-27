<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
 
$conn = new mysqli("localhost", "upknjbhg8vsv8", "yz88ljtio3sf", "dbsubd42rr081b");
 
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} else {
    echo "Database connected successfully!";
}
?>
