<?php
session_start();

if (isset($_POST['add_player']) && isset($_POST['username']) && isset($_POST['playerPosition'])) {
    $username = $_POST['username'];
    $playerPosition = $_POST['playerPosition'];

    $main_team_id = $_SESSION['user']['main_team_id'];

    $conn = new mysqli('localhost', 'dongqiudi', '800813', 'football_system');
    if ($conn->connect_error) {
        die("连接失败: " . $conn->connect_error);
    }

    $check_sql = "SELECT id FROM players WHERE `name` = ? AND team_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("si", $username, $main_team_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        echo "错误：该用户已在球队中！";
    } else {
        $sql = "INSERT INTO players (team_id, `name`, `position`, firston) VALUES (?, ?, ?, 0)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iss", $main_team_id, $username, $playerPosition);  // 使用 main_team_id
        if ($stmt->execute()) {
            echo "用户添加成功!";
        } else {
            echo "添加球员失败: " . $stmt->error;
        }
    }

    $update_query = $conn->prepare("UPDATE users SET main_team_id = ? WHERE username = ?");
    $update_query->bind_param("is", $main_team_id, $username);

    if ($update_query->execute()) {
        echo "users 表更新成功";
    } else {
        echo "users 表更新失败: " . $conn->error;
    }

    $conn->close();

    header("Location: captain_center.php");
    exit();
}
?>