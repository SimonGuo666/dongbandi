<?php
session_start();
if (!isset($_COOKIE['username'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ensure you are getting the correct values from POST
    $player_id = isset($_POST['player_id']) ? intval($_POST['player_id']) : 0;
    $new_rating = isset($_POST['new_rating']) ? floatval($_POST['new_rating']) : 0;
    $match_id = isset($_POST['match_id']) ? intval($_POST['match_id']) : 0;

    // Validate the data
    if ($player_id > 0 && $new_rating > 0 && $match_id > 0) {
        // Check if the user has already voted for this player in this match
        $cookie_name = "voted_player_{$player_id}_match_{$match_id}";
        if (isset($_COOKIE[$cookie_name])) {
            echo json_encode(['success' => false, 'message' => '你已经为这个球员在这个比赛中投过票了！']);
            exit();
        }

        $conn = new mysqli('localhost', 'dongqiudi', '800813', 'football_system');
        if ($conn->connect_error) {
            die("连接失败: " . $conn->connect_error);
        }

        // Check if the player has a previous rating for this match
        $query = $conn->prepare("SELECT rating FROM player_ratings WHERE player_id = ? AND match_id = ?");
        $query->bind_param("ii", $player_id, $match_id);

        if ($query->execute()) {
            $result = $query->get_result();

            if ($result->num_rows > 0) {
                // Get the current rating
                $row = $result->fetch_assoc();
                $current_rating = $row['rating'];

                // Calculate the new average rating
                $new_rating = ($current_rating + $new_rating) / 2;

                // Update the existing rating with the new average
                $update_query = $conn->prepare("UPDATE player_ratings SET rating = ? WHERE player_id = ? AND match_id = ?");
                $update_query->bind_param("dii", $new_rating, $player_id, $match_id);

                if ($update_query->execute()) {
                    // Set a cookie to record the vote
                    setcookie($cookie_name, 'voted', time() + 3600 * 24 * 30, "/"); // Cookie expires in 30 days
                    echo json_encode(['success' => true, 'message' => '更新成功！新评分: ' . $new_rating]);
                } else {
                    echo json_encode(['success' => false, 'message' => '更新失败: ' . $update_query->error]);
                }
                $update_query->close();
            } else {
                // Insert new rating if not present
                $insert_query = $conn->prepare("INSERT INTO player_ratings (player_id, match_id, rating) VALUES (?, ?, ?)");
                $insert_query->bind_param("iid", $player_id, $match_id, $new_rating);

                if ($insert_query->execute()) {
                    // Set a cookie to record the vote
                    setcookie($cookie_name, 'voted', time() + 3600 * 24 * 30, "/"); // Cookie expires in 30 days
                    echo json_encode(['success' => true, 'message' => '插入成功！评分: ' . $new_rating]);
                } else {
                    echo json_encode(['success' => false, 'message' => '插入失败: ' . $insert_query->error]);
                }
                $insert_query->close();
            }
        } else {
            echo json_encode(['success' => false, 'message' => '查询失败: ' . $query->error]);
        }

        $conn->close();
    } else {
        echo json_encode(['success' => false, 'message' => '无效数据']);
    }
}
?>
