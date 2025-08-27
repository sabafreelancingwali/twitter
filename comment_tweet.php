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
 
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['comment'], $_POST['tweet_id'])) {
    $comment = trim($_POST['comment']);
    $tweet_id = intval($_POST['tweet_id']);
    $user_id = $_SESSION['user_id'];
 
    if (!empty($comment)) {
        $stmt = $conn->prepare("INSERT INTO comments (tweet_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iis", $tweet_id, $user_id, $comment);
        $stmt->execute();
        $stmt->close();
    }
 
    echo "<script>window.location.href='index.php';</script>";
    exit();
}
?>
 
<!DOCTYPE html>
<html>
<head>
    <title>Comment on Tweet</title>
    <style>
        body { font-family: Arial; background: #e6ecf0; }
        .box { background: white; padding: 20px; width: 400px; margin: 50px auto; border-radius: 10px; }
        textarea { width: 100%; height: 80px; padding: 10px; border-radius: 8px; border: 1px solid #ccc; }
        button { background: #1da1f2; color: white; border: none; padding: 10px 15px; border-radius: 8px; cursor: pointer; margin-top: 10px; }
    </style>
</head>
<body>
<div class="box">
    <h3>💬 Write a Comment</h3>
    <form method="POST" action="">
        <textarea name="comment" placeholder="Write your comment..."></textarea>
        <input type="hidden" name="tweet_id" value="<?php echo intval($_GET['id']); ?>">
        <button type="submit">Post Comment</button>
    </form>
</div>
</body>
</html>
