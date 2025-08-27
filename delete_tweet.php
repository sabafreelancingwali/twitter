<?php
session_start();
 
// Check login
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='login.php';</script>";
    exit();
}
 
// DB connect
$servername = "localhost";
$username = "upknjbhg8vsv8";
$password = "yz88ljtio3sf";
$dbname = "dbsubd42rr081b";
 
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }
 
if (isset($_GET['id'])) {
    $tweet_id = intval($_GET['id']);
    $user_id = $_SESSION['user_id'];
 
    // Only allow delete if tweet belongs to user
    $stmt = $conn->prepare("DELETE FROM tweets WHERE id=? AND user_id=?");
    $stmt->bind_param("ii", $tweet_id, $user_id);
    $stmt->execute();
    $stmt->close();
 
    echo "<script>window.location.href='index.php';</script>";
    exit();
}
?>
 
