<?php
session_start();
$servername = "localhost";
$username = "upknjbhg8vsv8";
$password = "yz88ljtio3sf";
$dbname = "dbsubd42rr081b";
 
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }
 
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tweet_id'])) {
    $tweet_id = intval($_POST['tweet_id']);
    $user_id = $_SESSION['user_id'];
 
    // Check if already liked
    $check = $conn->prepare("SELECT * FROM likes WHERE tweet_id=? AND user_id=?");
    $check->bind_param("ii", $tweet_id, $user_id);
    $check->execute();
    $res = $check->get_result();
 
    if ($res->num_rows == 0) {
        $stmt = $conn->prepare("INSERT INTO likes (tweet_id, user_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $tweet_id, $user_id);
        $stmt->execute();
        echo "liked";
    }
}
?>
