<?php
session_start();
if (!isset($_COOKIE['username'])) {
    header("Location: login.php");
    exit();
}

$username = $_COOKIE['username']; // 从Cookie读取username

$conn = new mysqli('localhost', 'dongqiudi', '800813', 'football_system');

// 查询用户信息，包括admin字段
$query = $conn->prepare("SELECT * FROM users WHERE username = ?");
$query->bind_param("s", $username);
$query->execute();
$user_result = $query->get_result();
$user = $user_result->fetch_assoc();
$query->close();

if (!$user) {
    // 用户不存在，处理错误 (例如，删除 cookie，重定向到登录页)
    setcookie('username', '', time() - 3600, '/'); // 删除cookie
    header("Location: login.php"); // 重定向到登录页面
    exit();
}
$_SESSION['user'] = $user;

// 获取用户的主队信息
$query = $conn->prepare("SELECT * FROM teams WHERE id = ?");
$query->bind_param("i", $user['main_team_id']);
$query->execute();
$team_result = $query->get_result();
$team = $team_result->fetch_assoc();
$query->close();

// 获取赛程信息
$matches = $conn->query("SELECT * FROM matches");

function getLeaderboard($conn, $section)
{
    $stmt = $conn->prepare("SELECT id, name, point FROM teams WHERE section = ? ORDER BY point DESC");
    $stmt->bind_param("s", $section);
    $stmt->execute();
    $result = $stmt->get_result();
    $leaderboard = [];
    while ($row = $result->fetch_assoc()) {
        $leaderboard[] = $row;
    }
    $stmt->close();
    return $leaderboard;
}

// 获取 A 区排行榜
$leaderboard_a = getLeaderboard($conn, 'A');

// 获取 B 区排行榜
$leaderboard_b = getLeaderboard($conn, 'B');

// 获取射手榜数据
$sql = "SELECT player_id, SUM(goals) AS total_goals
        FROM goals
        GROUP BY player_id
        ORDER BY total_goals DESC
        LIMIT 5";  // 显示前5名
$result = $conn->query($sql);
$top_scorers = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $player_id = $row['player_id'];

        // 获取球员信息
        $player_query = $conn->prepare("SELECT name, team_id FROM players WHERE id = ?");
        $player_query->bind_param("i", $player_id);
        $player_query->execute();
        $player_result = $player_query->get_result();

        if ($player = $player_result->fetch_assoc()) {
            $team_id = $player['team_id'];

            // 获取班级信息
            $team_query = $conn->prepare("SELECT name FROM teams WHERE id = ?");
            $team_query->bind_param("i", $team_id);
            $team_query->execute();
            $team_result = $team_query->get_result();

            if ($team = $team_result->fetch_assoc()) {
                $top_scorers[] = [
                    'player_name' => $player['name'],
                    'team_name' => $team['name'],
                    'total_goals' => $row['total_goals']
                ];
            }
            $team_query->close();
        }
        $player_query->close();
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>懂班帝</title>
    <link rel="stylesheet" href="indexstyle.css">
    <link rel="icon" type="image/x-icon" href="./assets/logo.ico" />
    <style>
        .leaderboard-container {
            display: flex;
            /* 水平排列 A 区和 B 区排行榜 */
            justify-content: space-around;
            /* 均匀分布在容器中 */
            flex-wrap: wrap;
            margin-top: 10px;
        }

        .leaderboard {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            width: 45%;
            margin-bottom: 20px;
            /* 增加底部间距 */
        }

        .leaderboard h3 {
            text-align: center;
            font-size: 20px;
            margin-bottom: 15px;
            color: #333;
        }

        .leaderboard-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .leaderboard-table th,
        .leaderboard-table td {
            border-bottom: 1px solid #ddd;
            /* 仅底部边框 */
            padding: 12px 8px;
            /* 增大 padding */
            text-align: left;
        }

        .leaderboard-table th {
            background-color: #f9f9f9;
            font-weight: bold;
            /* 加粗标题 */
            color: #555;
        }

        .leaderboard-table tr:hover {
            background-color: #f5f5f5;
            /* 鼠标悬停时改变颜色 */
        }

        .leaderboard-table td:first-child {
            color: #888;
            /* 排名字体颜色 */
        }

        .highlight {
            background-color: #e0f7fa;
            /* 高亮颜色 */
        }

        /* 手机设备样式 */
        @media (max-width: 600px) {
            .leaderboard-container {
                flex-direction: column;
                /* 在小屏幕上垂直排列排行榜 */
                align-items: center;
                /* 居中对齐 */
            }

            .leaderboard {
                width: 90%;
                /* 在手机上占据更多宽度 */
            }

            .leaderboard h3 {
                font-size: 18px;
            }
        }

        #white-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: white;
            /* 纯白色 */
            z-index: 1;
            /* 在背景层 */
            opacity: 1;
            /* 立即显示 */
        }

        /* 全屏加载层 - 蓝色背景 */
        #loading-screen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: #009cff;
            /* 纯蓝色背景 */
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 2;
            /* 高于内容层 */
            opacity: 0;
            /* 初始完全透明 */
            transition: opacity 1s ease-out;
            /* 渐显效果 */
        }

        /* 加载图标 */
        .loading-icon {
            width: 50px;
            height: 50px;
            border: 5px solid #fff;
            border-top: 5px solid transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            /* 旋转动画 */
        }

        /* 旋转动画 */
        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body>
    <div id="white-background"></div>

    <div id="loading-screen">
        <div class="loading-icon"></div>
    </div>
    <header>
        <img src="./assets/white.png" alt="Logo" class="logo">
        <nav>
            <a href="index.php">首页</a>
            <a href="edit_avatar.php">编辑个人头像</a>
            <?php if ($user && $user['admin'] == 1): ?>
                <a href="captain_center.php">队长中心</a>
                <a href="ananews.php">新闻撰写</a>
            <?php endif; ?>
            <a href="logout.php">退出</a>
        </nav>
    </header>

    <section id="match-schedule">
        <h2>赛程展示</h2>
        <div class="match-card-container">
            <?php while ($match = $matches->fetch_assoc()) {
                // 获取队伍A名称
                $team_a_query = $conn->prepare("SELECT name FROM teams WHERE id = ?");
                $team_a_query->bind_param("i", $match['team_a_id']);
                $team_a_query->execute();
                $team_a_result = $team_a_query->get_result();
                $team_a = $team_a_result->fetch_assoc();
                $team_a_name = $team_a['name'];

                // 获取队伍B名称
                $team_b_query = $conn->prepare("SELECT name FROM teams WHERE id = ?");
                $team_b_query->bind_param("i", $match['team_b_id']);
                $team_b_query->execute();
                $team_b_result = $team_b_query->get_result();
                $team_b = $team_b_result->fetch_assoc();
                $team_b_name = $team_b['name'];

                // 格式化日期时间
                $match_time = new DateTime($match['match_time']);
                $formatted_time = $match_time->format('Y-m-d H:i');
                ?>
                <div class="match-card" onclick="window.location.href='match_details.php?id=<?php echo $match['id']; ?>'">
                    <div class="team"><?php echo $team_a_name; ?> vs<br><?php echo $team_b_name; ?></div>
                    <div class="score" style="margin-bottom: 0px;">
                        <?php echo $match['score_a']; ?>-<?php echo $match['score_b']; ?>
                    </div>
                    <div class="location">时间: <?php echo $formatted_time; ?></div>
                </div>
            <?php } ?>
        </div>
    </section>
    <!-- A区排行榜 -->
    <div class="leaderboard-container">
        <section id="leaderboard-a" class="leaderboard">
            <h3>A 区排行榜</h3>
            <table class="leaderboard-table">
                <thead>
                    <tr>
                        <th>排名</th>
                        <th>队伍名称</th>
                        <th>积分</th>
                    </tr>
                </thead>
                <tbody>
                    <?php for ($i = 0; $i < count($leaderboard_a); $i++):
                        $team = $leaderboard_a[$i]; ?>
                        <tr <?php if ($i < 2)
                            echo 'class="highlight"'; ?>>
                            <td><?php echo $i + 1; ?></td>
                            <td><?php echo $team['name']; ?></td>
                            <td><?php echo $team['point']; ?></td>
                        </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
        </section>

        <!-- B区排行榜 -->
        <section id="leaderboard-b" class="leaderboard">
            <h3>B 区排行榜</h3>
            <table class="leaderboard-table">
                <thead>
                    <tr>
                        <th>排名</th>
                        <th>队伍名称</th>
                        <th>积分</th>
                    </tr>
                </thead>
                <tbody>
                    <?php for ($i = 0; $i < count($leaderboard_b); $i++):
                        $team = $leaderboard_b[$i]; ?>
                        <tr <?php if ($i < 2)
                            echo 'class="highlight"'; ?>>
                            <td><?php echo $i + 1; ?></td>
                            <td><?php echo $team['name']; ?></td>
                            <td><?php echo $team['point']; ?></td>
                        </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
        </section>
        <section id="top-scorers" class="leaderboard">
            <h3>射手榜</h3>
            <table class="leaderboard-table">
                <thead>
                    <tr>
                        <th>排名</th>
                        <th>球员名称</th>
                        <th>班级</th>
                        <th>总进球数</th>
                    </tr>
                </thead>
                <tbody>
                    <?php for ($i = 0; $i < count($top_scorers); $i++):
                        $scorer = $top_scorers[$i]; ?>
                        <tr>
                            <td><?php echo $i + 1; ?></td>
                            <td><?php echo $scorer['player_name']; ?></td>
                            <td><?php echo $scorer['team_name']; ?></td>
                            <td><?php echo $scorer['total_goals']; ?></td>
                        </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
        </section>
    </div>
    <footer style="text-align: center; padding: 20px; background-color: #f0f0f0; margin-top: 20px;font-size:14px">
        声明：本项目由SimonG创建并完善，<b>包含</b>AI的帮助
    </footer>
    <script src="script.js"></script>
</body>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        // 先让蓝色背景渐显出来
        setTimeout(function () {
            document.getElementById("loading-screen").style.opacity = 1;
        }, 100); // 500ms后渐显蓝色背景

        // 延迟 1 秒后开始渐隐
        setTimeout(function () {
            document.getElementById("loading-screen").style.opacity = 0;
            document.getElementById("white-background").style.opacity = 0;
            setTimeout(function () {
                document.getElementById("loading-screen").style.display = "none";
                document.getElementById("white-background").style.display = "none";
            }, 1000); // 1000ms 后完全隐藏
        }, 1500); // 1.5秒后开始渐变消失
    });
</script>

</html>
<?php
$conn->close();
?>