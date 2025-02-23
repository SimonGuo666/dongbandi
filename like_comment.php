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

// 获取用户ID（从数据库或Session中获取）
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

// 获取评论ID
if (isset($_GET['comment_id']) && !empty($_GET['comment_id'])) {
    $comment_id = intval($_GET['comment_id']);  // 强制转换为整数

    // 检查评论ID是否有效
    $comment_query = $conn->prepare("SELECT id FROM comments WHERE id = ?");
    $comment_query->bind_param("i", $comment_id);
    $comment_query->execute();
    $comment_result = $comment_query->get_result();

    // 如果评论不存在
    if ($comment_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => '评论ID无效']);
        exit();
    }

    // 检查用户是否已经点赞过该评论
    $like_check_query = $conn->prepare("SELECT * FROM likes WHERE user_id = ? AND comment_id = ?");
    $like_check_query->bind_param("ii", $user_id, $comment_id);
    $like_check_query->execute();
    $like_check_result = $like_check_query->get_result();

    if ($like_check_result->num_rows > 0) {
        // 用户已经点赞过，不做任何操作
        echo json_encode(['success' => false, 'message' => '您已点赞此评论']);
        exit();
    }

    // 用户未点赞，执行点赞操作
    $stmt = $conn->prepare("INSERT INTO likes (user_id, comment_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $user_id, $comment_id);
    if ($stmt->execute()) {
        // 增加评论的点赞数
        $stmt = $conn->prepare("UPDATE comments SET likes = likes + 1 WHERE id = ?");
        $stmt->bind_param("i", $comment_id);
        $stmt->execute();

        // 获取新的点赞数
        $stmt = $conn->prepare("SELECT likes FROM comments WHERE id = ?");
        $stmt->bind_param("i", $comment_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $comment = $result->fetch_assoc();

        echo json_encode(['success' => true, 'likes' => $comment['likes']]);
    } else {
        echo json_encode(['success' => false, 'message' => '点赞失败，请稍后重试']);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => '评论ID无效']);
}
?>
