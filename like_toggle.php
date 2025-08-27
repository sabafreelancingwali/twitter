<?php
session_start();
 
if (!isset($_SESSION['user_id'])) {
    echo "not_logged_in";
    exit();
}
 
$servername = "localhost";
$username = "upknjbhg8vsv8";
$password = "yz88ljtio3sf";
$dbname = "dbsubd42rr081b";
 
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }
 
if (isset($_POST['tweet_id'])) {
    $tweet_id = intval($_POST['tweet_id']);
    $user_id = $_SESSION['user_id'];
 
    // Check if already liked
    $check = $conn->prepare("SELECT * FROM likes WHERE tweet_id=? AND user_id=?");
    $check->bind_param("ii", $tweet_id, $user_id);
    $check->execute();
    $result = $check->get_result();
 
    if ($result->num_rows > 0) {
        // Unlike
        $del = $conn->prepare("DELETE FROM likes WHERE tweet_id=? AND user_id=?");
        $del->bind_param("ii", $tweet_id, $user_id);
        $del->execute();
        echo "unliked";
    } else {
        // Like
        $stmt = $conn->prepare("INSERT INTO likes (tweet_id, user_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $tweet_id, $user_id);
        $stmt->execute();
        echo "liked";
    }
}
?>
 
