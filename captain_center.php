<?php
session_start();

// 验证是否已登录，并且是队长
if (!isset($_SESSION['user']) || $_SESSION['user']['admin'] != 1) {
    // 如果没有队长信息，或没有权限，则从数据库查询
    if (isset($_COOKIE['username'])) {  // 修改：使用 Cookie 中的 username
        $username = $_COOKIE['username'];

        // 连接数据库
        $conn = new mysqli('localhost', 'dongqiudi', '800813', 'football_system');
        if ($conn->connect_error) {
            die("连接失败: " . $conn->connect_error);
        }

        // 查找当前用户的 main_team_id
        $user_query = $conn->prepare("SELECT main_team_id FROM users WHERE username = ?");
        $user_query->bind_param("s", $username);
        $user_query->execute();
        $user_result = $user_query->get_result();

        if ($user_result->num_rows > 0) {
            $user = $user_result->fetch_assoc();
            $_SESSION['user']['main_team_id'] = $user['main_team_id'];  // 将 main_team_id 存储到 session 中

            // 输出调试信息，确保成功获取 main_team_id
            echo "当前队长的 main_team_id: " . $_SESSION['user']['main_team_id'];
        } else {
            // 如果用户未在数据库中找到，跳转回登录页
            header("Location: index.php");
            exit();
        }
        $conn->close();
    } else {
        // 如果没有用户登录，跳转到首页
        header("Location: index.php");
        exit();
    }
}

$main_team_id = $_SESSION['user']['main_team_id'];   // 从 Session 中获取 main_team_id 作为队长所在队伍;

// 查询队伍中的用户
$conn = new mysqli('localhost', 'dongqiudi', '800813', 'football_system');
if ($conn->connect_error) {
    die("连接失败: " . $conn->connect_error);
}

// 查询当前队伍中的所有球员
$player_query = $conn->prepare("SELECT * FROM players WHERE team_id = ?");
$player_query->bind_param("i", $main_team_id);
$player_query->execute();
$players_result = $player_query->get_result();

$first_players = [];
$normal_players = [];

while ($player = $players_result->fetch_assoc()) {
    if ($player["firston"] == 1) {
        $first_players[] = $player;  // 首发球员
    } else {
        $normal_players[] = $player;  // 普通球员
    }
}

// 查找还没有加入队伍的其他用户
$users_query = $conn->prepare("SELECT * FROM users WHERE main_team_id IS NULL OR main_team_id = ?");
$users_query->bind_param("i", $main_team_id);
$users_query->execute();
$users_result = $users_query->get_result();  // 所有未加入队伍的用户

$other_users = [];

while ($user = $users_result->fetch_assoc()) {
    $is_player = false;
    // 检查该用户是否已经加入队伍
    foreach ($first_players as $first) {
        if ($first['name'] == $user["username"]) {
            $is_player = true;
            break;
        }
    }
    if (!$is_player) {
        foreach ($normal_players as $normal) {
            if ($normal["name"] == $user["username"]) {
                $is_player = true;
                break;
            }
        }
    }
    if (!$is_player) {
        $other_users[] = $user;  // 将未加入队伍的用户添加到列表
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>队长中心 - 懂班帝</title>
    <link rel="icon" type="image/x-icon" href="./assets/logo.ico" />
    <style>
        /* General reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            color: #333;
        }

        /* Global Container styling */
        .container {
            width: 95%;
            max-width: 1200px;
            margin: 20px auto;
            background: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            padding: 20px;
        }

        header {
            background-color: #009cff;
            color: white;
            padding: 15px;
            text-align: center;
        }

        header h1 {
            font-size: 24px;
            margin-bottom: 10px;
        }

        nav a {
            color: white;
            margin: 0 15px;
            text-decoration: none;
            font-size: 16px;
        }

        nav a:hover {
            text-decoration: underline;
        }

        header .logo {
            width: 250px;
            margin-top: -80px;
            margin-bottom: -70px;
        }

        .container h2, section h2 {
            text-align: center;
            font-size: 22px;
            margin-bottom: 20px;
            color: #333;
        }

        section {
            padding: 15px;
            margin-bottom: 20px;
            background: #f4f4f4;
            border-radius: 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        td, th {
            padding: 10px 15px;
            text-align: center;
        }

        th {
            font-size: 1.4em;
        }

        label {
            margin: 5px;
        }

        button {
            background-color: #009cff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }

        button:hover {
            background-color: #007acc;
        }

        #white-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: white;
            z-index: 1;
            opacity: 1;
        }

        #loading-screen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: #009cff;
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 2;
            opacity: 0;
            transition: opacity 1s ease-out;
        }

        .loading-icon {
            width: 50px;
            height: 50px;
            border: 5px solid #fff;
            border-top: 5px solid transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        main {
            width: 80%;
            margin: 20px auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        label {
            margin-right: 10px;
            display: inline-block;
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
        <h1>队长中心</h1>
        <nav>
            <a href="index.php">首页</a>
            <a href="logout.php">退出</a>
        </nav>
    </header>

    <main>
        <!-- 首发球员列表 -->
        <section id="first-players">
            <h2>首发球员</h2>
            <?php if (!empty($first_players)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>用户名</th>
                            <th>位置</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($first_players as $player): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($player['name']); ?></td>
                                <td>
                                     <form action="edit_player.php" method="POST" style="display: inline;">
                                        <input type="hidden" name="player_id" value="<?php echo htmlspecialchars($player['id']); ?>">
                                        <input type="text" name="position" value="<?php echo htmlspecialchars($player['position']); ?>" size="5">
                                        
                                </td>
                                <td>
                                         <input type="hidden" name="firston" value="0">  <!-- 设置为非首发 -->
                                        <button type="submit">设为普通球员</button>
                                       </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>没有首发球员。</p>
            <?php endif; ?>
        </section>

        <!-- 普通球员列表 -->
        <section id="normal-players">
            <h2>普通球员</h2>
            <?php if (!empty($normal_players)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>用户名</th>
                            <th>位置</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($normal_players as $player): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($player['name']); ?></td>
                                <td>
                                 <form action="edit_player.php" method="POST" style="display: inline;">
                                        <input type="hidden" name="player_id" value="<?php echo htmlspecialchars($player['id']); ?>">
                                         <input type="text" name="position" value="<?php echo htmlspecialchars($player['position']); ?>" size="5">
                                        
                                </td>
                                <td>
                                  <input type="hidden" name="firston" value="1">  <!-- 设置为首发 -->
                                        <button type="submit">设为首发球员</button>
                                           </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>没有普通球员。</p>
            <?php endif; ?>
        </section>

        <!-- 其他用户表单 -->
        <section id="other-users">
            <h2>其他用户</h2>
            <?php
            // 检查 $other_users 是否为空
            if (!empty($other_users)) {
                foreach ($other_users as $other): ?>
                    <form action="add_player.php" method="POST">
                        <table>
                            <thead>
                                <tr>
                                    <th>用户名</th>
                                    <th>位置</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <?php echo htmlspecialchars($other['username']); ?>
                                        <input type="hidden" name="username" value="<?php echo htmlspecialchars($other['username']); ?>">
                                        <input type="hidden" name="team_id" value="<?php echo $main_team_id; ?>">  <!-- 传递 team_id -->
                                    </td>
                                    <td>
                                        <label>位置：<input type="text" id="CF" size="5" placeholder="请输入双字符缩写" name="playerPosition" /></label>
                                    </td>
                                    <td>
                                        <button type="submit" name="add_player" value="<?php echo htmlspecialchars($other['username']); ?>">加入球队</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </form>
                <?php endforeach;
            } else {
                echo "<p>没有其他用户可添加。</p>";
            }
            ?>
        </section>
    </main>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            setTimeout(function () {
                document.getElementById("loading-screen").style.opacity = 1;
            }, 100);

            setTimeout(function () {
                document.getElementById("loading-screen").style.opacity = 0;
                document.getElementById("white-background").style.opacity = 0;
                setTimeout(function () {
                    document.getElementById("loading-screen").style.display = "none";
                    document.getElementById("white-background").style.display = "none";
                }, 1000);
            }, 1500);
        });
    </script>
</body>
</html>