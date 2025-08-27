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
 
    // Check if already liked
    $check = $conn->prepare("SELECT * FROM likes WHERE tweet_id=? AND user_id=?");
    $check->bind_param("ii", $tweet_id, $user_id);
    $check->execute();
    $result = $check->get_result();
 
    if ($result->num_rows == 0) {
        $stmt = $conn->prepare("INSERT INTO likes (tweet_id, user_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $tweet_id, $user_id);
        $stmt->execute();
        $stmt->close();
    }
 
    echo "<script>window.location.href='index.php';</script>";
    exit();
}
?>
