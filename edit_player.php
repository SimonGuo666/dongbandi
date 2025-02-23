<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['player_id']) && isset($_POST['firston']) && isset($_POST['position'])) {
    $player_id = $_POST['player_id'];
    $firston = $_POST['firston'];
    $position = $_POST['position'];

    // 连接数据库
    $conn = new mysqli('localhost', 'dongqiudi', '800813', 'football_system');
    if ($conn->connect_error) {
        die("连接失败: " . $conn->connect_error);
    }

    // 更新 players 表的 firston 状态和 position
    $update_query = $conn->prepare("UPDATE players SET firston = ?, `position` = ? WHERE id = ?");
    $update_query->bind_param("isi", $firston, $position, $player_id);

    if ($update_query->execute()) {
        echo "球员状态更新成功!";
    } else {
        echo "球员状态更新失败: " . $conn->error;
    }

    $conn->close();

    // 重定向回队长中心页面
    header("Location: captain_center.php");
    exit();
} else {
    echo "非法请求";
}
?>