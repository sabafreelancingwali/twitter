<?php
include 'config.php';
if (!isset($_SESSION['user_id'])) {
    echo '<script>window.location.href = "login.php";</script>';
    exit;
}
$currentUserId = $_SESSION['user_id'];
 
// Handle AJAX actions
if (isset($_POST['action'])) {
    $response = [];
    switch ($_POST['action']) {
        case 'post_tweet':
            $content = trim($_POST['content']);
            if ($content) {
                $stmt = $pdo->prepare("INSERT INTO tweets (user_id, content) VALUES (?, ?)");
                $stmt->execute([$currentUserId, $content]);
                $response['success'] = true;
            }
            break;
        case 'like_tweet':
            $tweetId = $_POST['tweet_id'];
            $stmt = $pdo->prepare("SELECT likes FROM tweets WHERE id = ?");
            $stmt->execute([$tweetId]);
            $likes = json_decode($stmt->fetchColumn(), true);
            if (in_array($currentUserId, $likes)) {
                $likes = array_filter($likes, fn($id) => $id != $currentUserId);
            } else {
                $likes[] = $currentUserId;
            }
            $stmt = $pdo->prepare("UPDATE tweets SET likes = ? WHERE id = ?");
            $stmt->execute([json_encode($likes), $tweetId]);
            $response['likes'] = count($likes);
            break;
        case 'add_comment':
            $tweetId = $_POST['tweet_id'];
            $content = trim($_POST['content']);
            if ($content) {
                $stmt = $pdo->prepare("SELECT comments FROM tweets WHERE id = ?");
                $stmt->execute([$tweetId]);
                $comments = json_decode($stmt->fetchColumn(), true);
                $comments[] = ['user_id' => $currentUserId, 'content' => $content, 'timestamp' => date('Y-m-d H:i:s')];
                $stmt = $pdo->prepare("UPDATE tweets SET comments = ? WHERE id = ?");
                $stmt->execute([json_encode($comments), $tweetId]);
                $response['success'] = true;
            }
            break;
        case 'edit_tweet':
            $tweetId = $_POST['tweet_id'];
            $content = trim($_POST['content']);
            if ($content) {
                $stmt = $pdo->prepare("UPDATE tweets SET content = ? WHERE id = ? AND user_id = ?");
                $stmt->execute([$content, $tweetId, $currentUserId]);
                $response['success'] = true;
            }
            break;
        case 'delete_tweet':
            $tweetId = $_POST['tweet_id'];
            $stmt = $pdo->prepare("DELETE FROM tweets WHERE id = ? AND user_id = ?");
            $stmt->execute([$tweetId, $currentUserId]);
            $response['success'] = true;
            break;
        case 'follow_user':
            $userId = $_POST['user_id'];
            $stmt = $pdo->prepare("SELECT following FROM users WHERE id = ?");
            $stmt->execute([$currentUserId]);
            $following = json_decode($stmt->fetchColumn(), true);
            $stmt2 = $pdo->prepare("SELECT followers FROM users WHERE id = ?");
            $stmt2->execute([$userId]);
            $followers = json_decode($stmt2->fetchColumn(), true);
            if (in_array($userId, $following)) {
                $following = array_filter($following, fn($id) => $id != $userId);
                $followers = array_filter($followers, fn($id) => $id != $currentUserId);
            } else {
                $following[] = $userId;
                $followers[] = $currentUserId;
            }
            $stmt = $pdo->prepare("UPDATE users SET following = ? WHERE id = ?");
            $stmt->execute([json_encode($following), $currentUserId]);
            $stmt2 = $pdo->prepare("UPDATE users SET followers = ? WHERE id = ?");
            $stmt2->execute([json_encode($followers), $userId]);
            $response['success'] = true;
            break;
        case 'get_feed':
            // Fetch users
            $usersStmt = $pdo->query("SELECT * FROM users");
            $users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);
            $usersMap = [];
            foreach ($users as $u) {
                $usersMap[$u['id']] = $u;
            }
            // Fetch current user following
            $stmt = $pdo->prepare("SELECT following FROM users WHERE id = ?");
            $stmt->execute([$currentUserId]);
            $following = json_decode($stmt->fetchColumn(), true);
            $following[] = $currentUserId; // Include own tweets
            // Fetch tweets
            $tweetsStmt = $pdo->prepare("SELECT * FROM tweets WHERE user_id IN (" . implode(',', array_fill(0, count($following), '?')) . ") ORDER BY timestamp DESC");
            $tweetsStmt->execute($following);
            $tweets = $tweetsStmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($tweets as &$tweet) {
                $tweet['likes'] = json_decode($tweet['likes'], true);
                $tweet['comments'] = json_decode($tweet['comments'], true);
                $tweet['user'] = $usersMap[$tweet['user_id']];
                foreach ($tweet['comments'] as &$comment) {
                    $comment['user'] = $usersMap[$comment['user_id']];
                }
            }
            $response['tweets'] = $tweets;
            // Fetch users for who to follow
            $userList = array_filter($users, fn($u) => $u['id'] != $currentUserId);
            foreach ($userList as &$u) {
                $u['is_following'] = in_array($u['id'], $following);
            }
            $response['user_list'] = $userList;
            break;
    }
    echo json_encode($response);
    exit;
}
 
// Initial render uses get_feed logic but in PHP for HTML
$usersStmt = $pdo->query("SELECT * FROM users");
$users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);
$usersMap = [];
foreach ($users as $u) {
    $usersMap[$u['id']] = $u;
}
$stmt = $pdo->prepare("SELECT following FROM users WHERE id = ?");
$stmt->execute([$currentUserId]);
$following = json_decode($stmt->fetchColumn(), true);
$following[] = $currentUserId;
$tweetsStmt = $pdo->prepare("SELECT * FROM tweets WHERE user_id IN (" . implode(',', array_fill(0, count($following), '?')) . ") ORDER BY timestamp DESC");
$tweetsStmt->execute($following);
$tweets = $tweetsStmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($tweets as &$tweet) {
    $tweet['likes'] = json_decode($tweet['likes'], true);
    $tweet['comments'] = json_decode($tweet['comments'], true);
    $tweet['user'] = $usersMap[$tweet['user_id']];
    foreach ($tweet['comments'] as &$comment) {
        $comment['user'] = $usersMap[$comment['user_id']];
    }
}
$userList = array_filter($users, fn($u) => $u['id'] != $currentUserId);
foreach ($userList as &$u) {
    $u['is_following'] = in_array($u['id'], $following);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Twitter Clone - Home</title>
    <style>
        /* Same CSS as before, omitted for brevity - copy from the home.html CSS in previous response */
    </style>
</head>
<body>
    <div class="sidebar">
        <a href="home.php" class="nav-link">Home</a>
        <a href="profile.php" class="nav-link">Profile</a>
        <button onclick="logout()" class="nav-link" style="background: none; border: none; cursor: pointer;">Logout</button>
    </div>
    <div class="feed">
        <div class="tweet-box">
            <textarea id="newTweet" placeholder="What's happening?" rows="3"></textarea>
            <button onclick="postTweet()">Tweet</button>
        </div>
        <div id="tweetFeed">
            <!-- PHP render initial tweets -->
            <?php foreach ($tweets as $tweet): ?>
                <div class="tweet">
                    <div class="tweet-header">
                        <img src="<?= htmlspecialchars($tweet['user']['profile_pic']) ?>" alt="Profile">
                        <div>
                            <strong><?= htmlspecialchars($tweet['user']['username']) ?></strong> <span><?= htmlspecialchars($tweet['user']['handle']) ?> · <?= $tweet['timestamp'] ?></span>
                        </div>
                    </div>
                    <div class="tweet-content"><?= htmlspecialchars($tweet['content']) ?></div>
                    <div class="tweet-actions">
                        <button onclick="likeTweet(<?= $tweet['id'] ?>)"><?= count($tweet['likes']) ?> <?= in_array($currentUserId, $tweet['likes']) ? 'Unlike' : 'Like' ?></button>
                        <button onclick="toggleComments(<?= $tweet['id'] ?>)">Comment (<?= count($tweet['comments']) ?>)</button>
                        <?php if ($tweet['user_id'] == $currentUserId): ?>
                            <button onclick="editTweet(<?= $tweet['id'] ?>)">Edit</button>
                            <button onclick="deleteTweet(<?= $tweet['id'] ?>)">Delete</button>
                        <?php endif; ?>
                    </div>
                    <div id="comments-<?= $tweet['id'] ?>" class="comments" style="display: none;">
                        <?php foreach ($tweet['comments'] as $comment): ?>
                            <div class="comment"><strong><?= htmlspecialchars($comment['user']['username']) ?></strong>: <?= htmlspecialchars($comment['content']) ?> <span><?= $comment['timestamp'] ?></span></div>
                        <?php endforeach; ?>
                        <textarea id="comment-<?= $tweet['id'] ?>" placeholder="Add comment" rows="2"></textarea>
                        <button onclick="addComment(<?= $tweet['id'] ?>)">Post Comment</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="suggested">
        <div class="user-list">
            <h3>Who to follow</h3>
            <div id="userList">
                <!-- PHP render initial user list -->
                <?php foreach ($userList as $user): ?>
                    <div class="user-item">
                        <img src="<?= htmlspecialchars($user['profile_pic']) ?>" alt="Profile">
                        <div>
                            <strong><?= htmlspecialchars($user['username']) ?></strong>
                            <span><?= htmlspecialchars($user['handle']) ?></span>
                        </div>
                        <button onclick="followUser(<?= $user['id'] ?>)" class="<?= $user['is_following'] ? 'following' : '' ?>"><?= $user['is_following'] ? 'Following' : 'Follow' ?></button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <script>
        function ajaxPost(action, data) {
            return fetch('home.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({action, ...data})
            }).then(res => res.json());
        }
 
        function postTweet() {
            const content = document.getElementById('newTweet').value.trim();
            if (!content) return alert('Tweet cannot be empty');
            ajaxPost('post_tweet', {content}).then(() => renderFeed());
            document.getElementById('newTweet').value = '';
        }
 
        function likeTweet(tweetId) {
            ajaxPost('like_tweet', {tweet_id: tweetId}).then(() => renderFeed());
        }
 
        function toggleComments(tweetId) {
            const commentsDiv = document.getElementById(`comments-${tweetId}`);
            commentsDiv.style.display = commentsDiv.style.display === 'none' ? 'block' : 'none';
        }
 
        function addComment(tweetId) {
            const content = document.getElementById(`comment-${tweetId}`).value.trim();
            if (!content) return alert('Comment cannot be empty');
            ajaxPost('add_comment', {tweet_id: tweetId, content}).then(() => renderFeed());
        }
 
        function editTweet(tweetId) {
            const newContent = prompt('Edit tweet:');
            if (newContent && newContent.trim()) {
                ajaxPost('edit_tweet', {tweet_id: tweetId, content: newContent.trim()}).then(() => renderFeed());
            }
        }
 
        function deleteTweet(tweetId) {
            if (confirm('Delete tweet?')) {
                ajaxPost('delete_tweet', {tweet_id: tweetId}).then(() => renderFeed());
            }
        }
 
        function followUser(userId) {
            ajaxPost('follow_user', {user_id: userId}).then(() => renderFeed());
        }
 
        function logout() {
            window.location.href = 'login.php'; // Session destroy in login or separate
        }
 
        function renderFeed() {
            ajaxPost('get_feed', {}).then(data => {
                const feedDiv = document.getElementById('tweetFeed');
                feedDiv.innerHTML = '';
                data.tweets.forEach(tweet => {
                    const isOwn = tweet.user_id === <?= $currentUserId ?>;
                    const liked = tweet.likes.includes(<?= $currentUserId ?>);
                    const tweetElem = document.createElement('div');
                    tweetElem.className = 'tweet';
                    tweetElem.innerHTML = `
                        <div class="tweet-header">
                            <img src="${tweet.user.profile_pic}" alt="Profile">
                            <div>
                                <strong>${tweet.user.username}</strong> <span>${tweet.user.handle} · ${tweet.timestamp}</span>
                            </div>
                        </div>
                        <div class="tweet-content">${tweet.content}</div>
                        <div class="tweet-actions">
                            <button onclick="likeTweet(${tweet.id})">${tweet.likes.length} ${liked ? 'Unlike' : 'Like'}</button>
                            <button onclick="toggleComments(${tweet.id})">Comment (${tweet.comments.length})</button>
                            ${isOwn ? `<button onclick="editTweet(${tweet.id})">Edit</button>` : ''}
                            ${isOwn ? `<button onclick="deleteTweet(${tweet.id})">Delete</button>` : ''}
                        </div>
                        <div id="comments-${tweet.id}" class="comments" style="display: none;">
                            ${tweet.comments.map(c => `<div class="comment"><strong>${c.user.username}</strong>: ${c.content} <span>${c.timestamp}</span></div>`).join('')}
                            <textarea id="comment-${tweet.id}" placeholder="Add comment" rows="2"></textarea>
                            <button onclick="addComment(${tweet.id})">Post Comment</button>
                        </div>
                    `;
                    feedDiv.appendChild(tweetElem);
                });
 
                const userListDiv = document.getElementById('userList');
                userListDiv.innerHTML = '';
                data.user_list.forEach(user => {
                    const userElem = document.createElement('div');
                    userElem.className = 'user-item';
                    userElem.innerHTML = `
                        <img src="${user.profile_pic}" alt="Profile">
                        <div>
                            <strong>${user.username}</strong>
                            <span>${user.handle}</span>
                        </div>
                        <button onclick="followUser(${user.id})" class="${user.is_following ? 'following' : ''}">${user.is_following ? 'Following' : 'Follow'}</button>
                    `;
                    userListDiv.appendChild(userElem);
                });
            });
        }
 
        setInterval(renderFeed, 5000); // Real-time refresh
    </script>
</body>
</html>
