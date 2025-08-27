
                    
100% Script finished in 0.36 seconds
net2ftp
ftp.planetearth.school
Bookmark (accesskey h) Refresh (accesskey r) Help (accesskey i) Logout (accesskey l)
icon
View file register.php
Back (accesskey b)  

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Twitter Clone - Register</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #15202b;
            color: #ffffff;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
            background: linear-gradient(135deg, #15202b, #1c2b3a);
        }
        .register-container {
            background-color: #192734;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 400px;
            text-align: center;
            transition: transform 0.3s ease;
        }
        .register-container:hover {
            transform: translateY(-5px);
        }
        h1 {
            font-size: 28px;
            margin-bottom: 20px;
            color: #1da1f2;
            font-weight: bold;
        }
        input {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #38444d;
            border-radius: 8px;
            background-color: #253341;
            color: #ffffff;
            font-size: 16px;
            box-sizing: border-box;
            transition: border-color 0.2s;
        }
        input:focus {
            border-color: #1da1f2;
            outline: none;
        }
        button {
            width: 100%;
            padding: 12px;
            background-color: #1da1f2;
            border: none;
            border-radius: 9999px;
            color: #ffffff;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.2s, transform 0.2s;
        }
        button:hover {
            background-color: #0c84d1;
            transform: scale(1.02);
        }
        .login-link {
            margin-top: 20px;
            color: #1da1f2;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.2s;
        }
        .login-link:hover {
            color: #ffffff;
            text-decoration: underline;
        }
        .error {
            color: red;
            margin-top: 10px;
        }
        @media (max-width: 600px) {
            .register-container {
                padding: 20px;
                max-width: 90%;
            }
            h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h1>Register for Twitter Clone</h1>
        <form method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="text" name="handle" placeholder="Handle (e.g., @user)" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="text" name="bio" placeholder="Bio (optional)">
            <input type="text" name="profile_pic" placeholder="Profile Pic URL (optional)">
            <button type="submit" name="register">Register</button>
        </form>
        <?php
        include 'config.php';
        if (isset($_POST['register'])) {
            $user = $_POST['username'];
            $handle = $_POST['handle'];
            $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $bio = $_POST['bio'] ?? '';
            $pic = $_POST['profile_pic'] ?? 'https://via.placeholder.com/48';
            try {
                $stmt = $pdo->prepare("INSERT INTO users (username, handle, password, bio, profile_pic) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$user, $handle, $pass, $bio, $pic]);
                $userId = $pdo->lastInsertId();
                $_SESSION['user_id'] = $userId;
                echo '<script>window.location.href = "home.php";</script>';
            } catch (PDOException $e) {
                echo '<p class="error">Error: Username or handle already exists</p>';
            }
        }
        ?>
        <a href="login.php" class="login-link">Already have an account? Login</a>
    </div>
</body>
</html>
Syntax highlighting powered by GeSHi
Help Guide | License
Powered by net2ftp - a web based FTP client
