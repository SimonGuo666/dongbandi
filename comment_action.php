<?php
session_start();

// 检查是否登录
if (!isset($_COOKIE['username'])) {
    header("Location: login.php");
    exit();
}

// 检查新闻ID是否传递过来
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "新闻ID无效。";
    exit();
}

$news_id = $_GET['id'];  // 获取新闻ID

// 检查评论是否为空
if (isset($_POST['comment']) && !empty(trim($_POST['comment']))) {
    $comment = trim($_POST['comment']);
    $username = $_COOKIE['username'];  // 获取cookie中的用户名

    // 连接数据库
    $conn = new mysqli('localhost', 'dongqiudi', '800813', 'football_system');

    // 检查数据库连接
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // 插入评论到数据库，使用用户名（不是user_id）
    $stmt = $conn->prepare("INSERT INTO comments (news_id, user_name, comment, created_at) VALUES (?, ?, ?, NOW())");
    if ($stmt === false) {
        die("SQL查询准备失败: " . $conn->error);  // 错误信息
    }

    // 绑定参数并插入数据
    $stmt->bind_param("iss", $news_id, $username, $comment);  // 使用 's' 表示 'string' 类型
    if ($stmt->execute()) {
        // 评论成功，跳转到新闻详情页
        header("Location: news.php?id=" . $news_id);
    } else {
        echo "评论提交失败，请稍后重试。";
    }

    // 关闭数据库连接
    $stmt->close();
    $conn->close();
} else {
    echo "评论不能为空。";
}
?>
