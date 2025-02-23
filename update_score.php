<?php
session_start();
$username = $_COOKIE['username']; // 获取当前用户名

// 检查比赛是否已投票
if (isset($_COOKIE['voted_match_' . $_POST['id']])) {
    echo json_encode(['success' => false, 'message' => '您已经为该比赛投过票']);
    exit();
}

if (isset($_POST['id'], $_POST['red'], $_POST['blue'])) {
    $match_id = intval($_POST['id']);
    $redPercentage = intval($_POST['red']);
    $bluePercentage = intval($_POST['blue']);
    $voteColor = ''; // 用来标记投票颜色

    $conn = new mysqli('localhost', 'dongqiudi', '800813', 'football_system');

    // 检查连接是否成功
    if ($conn->connect_error) {
        echo json_encode(['success' => false, 'message' => '数据库连接失败: ' . $conn->connect_error]);
        exit();
    }

    // 检查比赛ID是否存在
    $match_check_query = $conn->prepare("SELECT red, blue FROM line WHERE mid = ?");
    $match_check_query->bind_param("i", $match_id);
    $match_check_query->execute();
    $match_check_result = $match_check_query->get_result();

    if ($match_check_result->num_rows > 0) {
        // 获取现有的红色和蓝色投票值
        $row = $match_check_result->fetch_assoc();
        $existingRed = $row['red'];
        $existingBlue = $row['blue'];

        // 根据数据库中的值来选择投票颜色
        if ($redPercentage > $existingRed) {
            $voteColor = 'red';
        } elseif ($bluePercentage > $existingBlue) {
            $voteColor = 'blue';
        }

        // 更新或插入投票数据
        if ($voteColor == 'red') {
            $update_query = $conn->prepare("UPDATE line SET red = ?, blue = ?, voter = voter + 0.5 WHERE mid = ?");
        } elseif ($voteColor == 'blue') {
            $update_query = $conn->prepare("UPDATE line SET red = ?, blue = ?, voteb = voteb + 0.5 WHERE mid = ?");
        }

        if ($update_query === false) {
            echo json_encode(['success' => false, 'message' => '更新查询准备失败: ' . $conn->error]);
            exit();
        }

        $update_query->bind_param("iii", $redPercentage, $bluePercentage, $match_id);

        if ($update_query->execute()) {
            // 设置一个 Cookie，标记该比赛已经被投票
            setcookie('voted_match_' . $match_id, '1', time() + (30 * 24 * 60 * 60), '/'); // Cookie 有效期 30 天
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => '更新失败: ' . $update_query->error]);
        }
        $update_query->close();
    } else {
        // 如果比赛ID不存在，创建一条新记录
        if ($voteColor == 'red') {
            $insert_query = $conn->prepare("INSERT INTO line (mid, red, blue, voter) VALUES (?, ?, ?, 0.5)");
        } elseif ($voteColor == 'blue') {
            $insert_query = $conn->prepare("INSERT INTO line (mid, red, blue, voteb) VALUES (?, ?, ?, 0.5)");
        }

        if ($insert_query === false) {
            echo json_encode(['success' => false, 'message' => '插入查询准备失败: ' . $conn->error]);
            exit();
        }

        $insert_query->bind_param("iii", $match_id, $redPercentage, $bluePercentage);

        if ($insert_query->execute()) {
            // 设置一个 Cookie，标记该比赛已经被投票
            setcookie('voted_match_' . $match_id, '1', time() + (30 * 24 * 60 * 60), '/'); // Cookie 有效期 30 天
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => '插入失败: ' . $insert_query->error]);
        }
        $insert_query->close();
    }

    $match_check_query->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => '缺少必要参数']);
}
?>
