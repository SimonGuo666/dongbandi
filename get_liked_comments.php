<?php
session_start();

// 检查用户是否登录
if (!isset($_COOKIE['username'])) {
    echo json_encode(['success' => false, 'message' => '未登录']);
    exit();
}

// 连接数据库
$conn = new mysqli('localhost', 'dongqiudi', '800813', 'football_system');

// 检查数据库连接
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => '数据库连接失败: ' . $conn->connect_error]);
    exit();
}

// 获取用户ID
$user_query = $conn->prepare("SELECT id FROM users WHERE username = ?");
$user_query->bind_param("s", $_COOKIE['username']);
$user_query->execute();
$user_result = $user_query->get_result();

// 检查用户是否存在
if ($user_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => '用户不存在']);
    exit();
}

$user = $user_result->fetch_assoc();
$user_id = $user['id'];
$user_query->close();

// 查询用户点赞的评论
$like_query = $conn->prepare("SELECT comment_id FROM likes WHERE user_id = ?");
$like_query->bind_param("i", $user_id);
$like_query->execute();
$like_result = $like_query->get_result();

$liked_comments = [];
while ($like = $like_result->fetch_assoc()) {
    $liked_comments[] = $like['comment_id'];
}

echo json_encode(['success' => true, 'liked_comments' => $liked_comments]);

$like_query->close();
$conn->close();
?>
