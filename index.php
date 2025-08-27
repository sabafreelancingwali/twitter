<?php
session_start();
 
// Check login
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='login.php';</script>";
    exit();
}
 
$servername = "localhost";
$username = "upknjbhg8vsv8";
$password = "yz88ljtio3sf";
$dbname = "dbsubd42rr081b";
 
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }
 
// Add new tweet
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tweet'])) {
    $tweet = trim($_POST['tweet']);
    if ($tweet != "") {
        $stmt = $conn->prepare("INSERT INTO tweets (user_id, content) VALUES (?, ?)");
        $stmt->bind_param("is", $_SESSION['user_id'], $tweet);
        $stmt->execute();
    }
}
 
// Fetch all tweets
$sql = "SELECT tweets.id, tweets.user_id, tweets.content, tweets.created_at, users.username 
        FROM tweets 
        JOIN users ON tweets.user_id = users.id 
        ORDER BY tweets.created_at DESC";
$tweets = $conn->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Twitter Clone</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #0f2027;  
            background: linear-gradient(to right, #2c5364, #203a43, #0f2027);
            margin: 0;
            padding: 0;
            color: white;
        }
        .container {
            width: 600px;
            margin: 30px auto;
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 0 25px rgba(0,255,255,0.3);
        }
        h2 { text-align: center; color: cyan; }
        textarea {
            width: 100%;
            height: 70px;
            padding: 10px;
            border-radius: 10px;
            border: none;
            outline: none;
            background: rgba(255,255,255,0.2);
            color: white;
        }
        button {
            background: cyan;
            color: black;
            border: none;
            padding: 10px 15px;
            border-radius: 20px;
            cursor: pointer;
            margin-top: 10px;
            float: right;
            font-weight: bold;
        }
        .tweet {
            border-bottom: 1px solid rgba(255,255,255,0.2);
            padding: 15px 0;
            position: relative;
        }
        .tweet p { margin: 5px 0; }
        .username { font-weight: bold; color: cyan; }
        .time { font-size: 12px; color: lightgrey; }
        .heart {
            cursor: pointer;
            font-size: 22px;
            color: grey;
            transition: 0.3s;
            text-shadow: 0 0 5px cyan, 0 0 10px cyan;
        }
        .heart.liked {
            color: red;
            text-shadow: 0 0 10px red, 0 0 20px red;
            transform: scale(1.2);
        }
        .delete-btn {
            position: absolute;
            right: 10px;
            top: 10px;
            background: red;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 12px;
            transition: 0.3s;
        }
        .delete-btn:hover {
            background: darkred;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Welcome, <?php echo $_SESSION['username']; ?> 👋</h2>
        <form method="POST">
            <textarea name="tweet" placeholder="What's happening?"></textarea>
            <button type="submit">Tweet</button>
        </form>
        <hr>
        <h3>Latest Tweets</h3>
        <?php while ($row = $tweets->fetch_assoc()): ?>
            <?php
            // Check if user already liked this tweet
            $liked = false;
            $checkLike = $conn->prepare("SELECT * FROM likes WHERE tweet_id=? AND user_id=?");
            $checkLike->bind_param("ii", $row['id'], $_SESSION['user_id']);
            $checkLike->execute();
            $res = $checkLike->get_result();
            if ($res->num_rows > 0) { $liked = true; }
 
            // Count total likes
            $countRes = $conn->query("SELECT COUNT(*) as total FROM likes WHERE tweet_id=".$row['id']);
            $likeCount = $countRes->fetch_assoc()['total'];
            ?>
            <div class="tweet">
                <span class="username">@<?php echo $row['username']; ?></span> 
                <span class="time"> • <?php echo $row['created_at']; ?></span>
                <p><?php echo htmlspecialchars($row['content']); ?></p>
 
                <span 
                    class="heart <?php echo $liked ? 'liked' : ''; ?>" 
                    data-id="<?php echo $row['id']; ?>">
                    ❤️
                </span> 
                <span id="count-<?php echo $row['id']; ?>"><?php echo $likeCount; ?></span>
 
                <?php if ($row['user_id'] == $_SESSION['user_id']): ?>
                    <button class="delete-btn" onclick="deleteTweet(<?php echo $row['id']; ?>)">Delete</button>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    </div>
 
    <script>
    // Like button (one time only)
    document.querySelectorAll(".heart").forEach(function(el) {
        el.addEventListener("click", function() {
            if (this.classList.contains("liked")) {
                return; // no unlike
            }
            let tweet_id = this.getAttribute("data-id");
            let heart = this;
            let countSpan = document.getElementById("count-" + tweet_id);
 
            fetch("like_once.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "tweet_id=" + tweet_id
            })
            .then(res => res.text())
            .then(data => {
                if (data === "liked") {
                    heart.classList.add("liked");
                    countSpan.innerText = parseInt(countSpan.innerText) + 1;
                }
            });
        });
    });
 
    // Delete Tweet
    function deleteTweet(id) {
        if (!confirm("Are you sure you want to delete this tweet?")) return;
 
        fetch("delete_tweet.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "tweet_id=" + id
        })
        .then(res => res.text())
        .then(data => {
            if (data === "deleted") {
                location.reload();
            }
        });
    }
    </script>
</body>
</html>
