<?php
include 'config.php';
if (!isset($_SESSION['user_id'])) {
    echo '<script>window.location.href = "login.php";</script>';
    exit;
}
$currentUserId = $_SESSION['user_id'];
 
// Handle AJAX actions for profile
if (isset($_POST['action'])) {
    $response = [];
    switch ($_POST['action']) {
        case 'save_profile':
            $handle = trim($_POST['handle']);
            $bio = trim($_POST['bio']);
            $pic = trim($_POST['profile_pic']) ?: 'https://via.placeholder.com/48';
            $stmt = $pdo->prepare("UPDATE users SET handle = ?, bio = ?, profile_pic = ? WHERE id = ?");
            $stmt->execute([$handle, $bio, $pic, $currentUserId]);
            $response['success'] = true;
            break;
        // Other actions like like_tweet, add_comment, edit_tweet, delete_tweet same as home.php
        case 'like_tweet':
            // Copy from home.php
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
            $response['success'] = true;
            break;
        case 'add_comment':
            // Copy from home.php
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
            // Copy from home.php
            $tweetId = $_POST['tweet_id'];
            $content = trim($_POST['content']);
            if ($content) {
                $stmt = $pdo->prepare("UPDATE tweets SET content = ? WHERE id = ? AND user_id = ?");
                $stmt->execute([$content, $tweetId, $currentUserId]);
                $response['success'] = true;
            }
            break;
        case 'delete_tweet':
            // Copy from home.php
            $tweetId = $_POST['tweet_id'];
            $stmt = $pdo->prepare("DELETE FROM tweets WHERE id = ? AND user_id = ?");
            $stmt->execute([$tweetId, $currentUserId]);
            $response['success'] = true;
            break;
        case 'get_profile':
            // Fetch user
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$currentUserId]);
            $user = $stmt->fetch();
            $user['followers'] = json_decode($user['followers'], true);
            $user['following'] = json_decode($user['following'], true);
            $response['user'] = $user;
            // Fetch tweets
            $tweetsStmt = $pdo->prepare("SELECT * FROM tweets WHERE user_id = ? ORDER BY timestamp DESC");
            $tweetsStmt->execute([$currentUserId]);
            $tweets = $tweetsStmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($tweets as &$tweet) {
                $tweet['likes'] = json_decode($tweet['likes'], true);
                $tweet['comments'] = json_decode($tweet['comments'], true);
                foreach ($tweet['comments'] as &$comment) {
                    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
                    $stmt->execute([$comment['user_id']]);
                    $comment['user'] = $stmt->fetchColumn();
                }
            }
            $response['tweets'] = $tweets;
            break;
    }
    echo json_encode($response);
    exit;
}
 
// Initial render
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$currentUserId]);
$user = $stmt->fetch();
$user['followers'] = json_decode($user['followers'], true);
$user['following'] = json_decode($user['following'], true);
$tweetsStmt = $pdo->prepare("SELECT * FROM tweets WHERE user_id = ? ORDER BY timestamp DESC");
$tweetsStmt->execute([$currentUserId]);
$tweets = $tweetsStmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($tweets as &$tweet) {
    $tweet['likes'] = json_decode($tweet['likes'], true);
    $tweet['comments'] = json_decode($tweet['comments'], true);
    foreach ($tweet['comments'] as &$comment) {
        $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$comment['user_id']]);
        $comment['user'] = $stmt->fetchColumn();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Twitter Clone - Profile</title>
    <style>
        /* Same CSS as before, omitted for brevity - copy from the profile.html CSS in previous response */
    </style>
</head>
<body>
    <div class="sidebar">
        <a href="home.php" class="nav-link">Home</a>
        <a href="profile.php" class="nav-link">Profile</a>
        <button onclick="logout()" class="nav-link" style="background: none; border: none; cursor: pointer;">Logout</button>
    </div>
    <div class="profile-content">
        <div class="profile-header">
            <img id="profilePic" src="<?= htmlspecialchars($user['profile_pic']) ?>" alt="Profile">
            <h2 id="username"><?= htmlspecialchars($user['username']) ?></h2>
            <span id="handle"><?= htmlspecialchars($user['handle']) ?></span>
            <p id="bio"><?= htmlspecialchars($user['bio'] ?: 'No bio yet') ?></p>
            <div class="profile-stats">
                <div><strong id="followingCount"><?= count($user['following']) ?></strong> Following</div>
                <div><strong id="followersCount"><?= count($user['followers']) ?></strong> Followers</div>
            </div>
            <button class="edit-profile" onclick="toggleEditForm()">Edit Profile</button>
            <form id="editForm" class="edit-form" style="display: none;">
                <input type="text" id="editHandle" value="<?= htmlspecialchars($user['handle']) ?>" placeholder="Handle">
                <textarea id="editBio" placeholder="Bio" rows="3"><?= htmlspecialchars($user['bio']) ?></textarea>
                <input type="text" id="editProfilePic" value="<?= htmlspecialchars($user['profile_pic']) ?>" placeholder="Profile Pic URL">
                <button type="button" onclick="saveProfile()">Save</button>
            </form>
        </div>
        <div class="profile-tweets">
            <h3>Your Tweets</h3>
            <div id="profileTweets">
                <!-- PHP render initial tweets -->
                <?php foreach ($tweets as $tweet): ?>
                    <div class="tweet">
                        <div class="tweet-header">
                            <img src="<?= htmlspecialchars($user['profile_pic']) ?>" alt="Profile">
                            <div>
                                <strong><?= htmlspecialchars($user['username']) ?></strong> <span><?= htmlspecialchars($user['handle']) ?> · <?= $tweet['timestamp'] ?></span>
                            </div>
                        </div>
                        <div class="tweet-content"><?= htmlspecialchars($tweet['content']) ?></div>
                        <div class="tweet-actions">
                            <button onclick="likeTweet(<?= $tweet['id'] ?>)"><?= count($tweet['likes']) ?> <?= in_array($currentUserId, $tweet['likes']) ? 'Unlike' : 'Like' ?></button>
                            <button onclick="toggleComments(<?= $tweet['id'] ?>)">Comment (<?= count($tweet['comments']) ?>)</button>
                            <button onclick="editTweet(<?= $tweet['id'] ?>)">Edit</button>
                            <button onclick="deleteTweet(<?= $tweet['id'] ?>)">Delete</button>
                        </div>
                        <div id="comments-<?= $tweet['id'] ?>" class="comments" style="display: none;">
                            <?php foreach ($tweet['comments'] as $comment): ?>
                                <div class="comment"><strong><?= htmlspecialchars($comment['user']) ?></strong>: <?= htmlspecialchars($comment['content']) ?> <span><?= $comment['timestamp'] ?></span></div>
                            <?php endforeach; ?>
                            <textarea id="comment-<?= $tweet['id'] ?>" placeholder="Add comment" rows="2"></textarea>
                            <button onclick="addComment(<?= $tweet['id'] ?>)">Post Comment</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <script>
        function ajaxPost(action, data) {
            return fetch('profile.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({action, ...data})
            }).then(res => res.json());
        }
 
        function toggleEditForm() {
            const form = document.getElementById('editForm');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }
 
        function saveProfile() {
            const handle = document.getElementById('editHandle').value.trim();
            const bio = document.getElementById('editBio').value.trim();
            const profilePic = document.getElementById('editProfilePic').value.trim();
            ajaxPost('save_profile', {handle, bio, profile_pic: profilePic}).then(() => renderProfile());
            toggleEditForm();
        }
 
        function likeTweet(tweetId) {
            ajaxPost('like_tweet', {tweet_id: tweetId}).then(() => renderProfile());
        }
 
        function toggleComments(tweetId) {
            const commentsDiv = document.getElementById(`comments-${tweetId}`);
            commentsDiv.style.display = commentsDiv.style.display === 'none' ? 'block' : 'none';
        }
 
        function addComment(tweetId) {
            const content = document.getElementById(`comment-${tweetId}`).value.trim();
            if (!content) return alert('Comment cannot be empty');
            ajaxPost('add_comment', {tweet_id: tweetId, content}).then(() => renderProfile());
        }
 
        function editTweet(tweetId) {
            const newContent = prompt('Edit tweet:');
            if (newContent && newContent.trim()) {
                ajaxPost('edit_tweet', {tweet_id: tweetId, content: newContent.trim()}).then(() => renderProfile());
            }
        }
 
        function deleteTweet(tweetId) {
            if (confirm('Delete tweet?')) {
                ajaxPost('delete_tweet', {tweet_id: tweetId}).then(() => renderProfile());
            }
        }
 
        function logout() {
            window.location.href = 'login.php';
        }
 
        function renderProfile() {
            ajaxPost('get_profile', {}).then(data => {
                const user = data.user;
                document.getElementById('profilePic').src = user.profile_pic;
                document.getElementById('username').textContent = user.username;
                document.getElementById('handle').textContent = user.handle;
                document.getElementById('bio').textContent = user.bio || 'No bio yet';
                document.getElementById('followingCount').textContent = user.following.length;
                document.getElementById('followersCount').textContent = user.followers.length;
 
                const tweetsDiv = document.getElementById('profileTweets');
                tweetsDiv.innerHTML = '';
                data.tweets.forEach(tweet => {
                    const liked = tweet.likes.includes(<?= $currentUserId ?>);
                    const tweetElem = document.createElement('div');
                    tweetElem.className = 'tweet';
                    tweetElem.innerHTML = `
                        <div class="tweet-header">
                            <img src="${user.profile_pic}" alt="Profile">
                            <div>
                                <strong>${user.username}</strong> <span>${user.handle} · ${tweet.timestamp}</span>
                            </div>
                        </div>
                        <div class="tweet-content">${tweet.content}</div>
                        <div class="tweet-actions">
                            <button onclick="likeTweet(${tweet.id})">${tweet.likes.length} ${liked ? 'Unlike' : 'Like'}</button>
                            <button onclick="toggleComments(${tweet.id})">Comment (${tweet.comments.length})</button>
                            <button onclick="editTweet(${tweet.id})">Edit</button>
                            <button onclick="deleteTweet(${tweet.id})">Delete</button>
                        </div>
                        <div id="comments-${tweet.id}" class="comments" style="display: none;">
                            ${tweet.comments.map(c => `<div class="comment"><strong>${c.user}</strong>: ${c.content} <span>${c.timestamp}</span></div>`).join('')}
                            <textarea id="comment-${tweet.id}" placeholder="Add comment" rows="2"></textarea>
                            <button onclick="addComment(${tweet.id})">Post Comment</button>
                        </div>
                    `;
                    tweetsDiv.appendChild(tweetElem);
                });
            });
        }
    </script>
</body>
</html>
