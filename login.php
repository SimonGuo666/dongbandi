<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $conn = new mysqli('localhost', 'dongqiudi', '800813', 'football_system');
    if ($conn->connect_error) {
        die("连接失败: " . $conn->connect_error);
    }

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND password = ?");
    $stmt->bind_param("ss", $email, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        $_SESSION['user'] = $user;
        
        setcookie("username", $user['username'], time() + (60 * 24 * 60 * 60), "/"); // 30 天有效期

        header("Location: index.php");
    } else {
        $error = "用户名或密码错误";
    }

    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登录 - 懂班帝</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/x-icon" href="./assets/logo.ico" />
    <style>
        .logo{
            margin-top: -50px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <div class="logo-container">
                <img src="./assets/blue.png" alt="Logo" class="logo">
            </div>
            <h2>欢迎回来</h2>
            <form action="login.php" method="POST">
                <div class="input-group">
                    <label for="email">电子邮件</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="input-group">
                    <label for="password">密码</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit">登录</button>
            </form>
            <p>还没有账号？ <a href="register.php">注册</a></p>
        </div>
    </div>
</body>
</html>
