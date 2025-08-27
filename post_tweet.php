<?php
session_start();
 
// DB connection
$servername = "localhost";
$username = "upknjbhg8vsv8";
$password = "yz88ljtio3sf";
$dbname = "dbsubd42rr081b";
 
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
 
// Check login
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='login.php';</script>";
    exit();
}
 
if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['content'])) {
    $content = htmlspecialchars($_POST['content']);
    $user_id = $_SESSION['user_id'];
 
    $stmt = $conn->prepare("INSERT INTO tweets (user_id, content, created_at) VALUES (?, ?, NOW())");
    $stmt->bind_param("is", $user_id, $content);
 
    if ($stmt->execute()) {
        echo "<script>window.location.href='index.php';</script>";
        exit();
    } else {
        echo "❌ Error posting tweet.";
    }
 
    $stmt->close();
}
 
$conn->close();
?>
