<?php
session_start();
if (!isset($_COOKIE['username'])) {
    header("Location: login.php");
    exit();
}

// 获取比赛ID
$match_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($match_id == 0) {
    header("Location: index.php");
    exit();
}

$conn = new mysqli('localhost', 'dongqiudi', '800813', 'football_system');

// 获取比赛信息
$match_query = $conn->prepare("SELECT * FROM matches WHERE id = ?");
$match_query->bind_param("i", $match_id);
$match_query->execute();
$match_result = $match_query->get_result();
$match = $match_result->fetch_assoc();
$match_query->close();
$match_time = new DateTime($match['match_time']);
$formatted_time = $match_time->format('Y-m-d H:i');

// 获取与比赛相关的新闻
$news_query = $conn->prepare("SELECT * FROM news WHERE FIND_IN_SET(?, rel) OR FIND_IN_SET(?, rel)");
$news_query->bind_param("ii", $match['team_a_id'], $match['team_b_id']);
$news_query->execute();
$news_result = $news_query->get_result();

// 获取队伍A和队伍B的球员信息，并按照 firston 列排序（首发优先）
$players_query = $conn->prepare("SELECT * FROM players WHERE team_id IN (?, ?) ORDER BY firston DESC, name ASC");
$players_query->bind_param("ii", $match['team_a_id'], $match['team_b_id']);
$players_query->execute();
$players_result = $players_query->get_result();

// 获取进球数据
$goals_query = $conn->prepare("SELECT player_id, goals FROM goals WHERE match_id = ?");
$goals_query->bind_param("i", $match_id);
$goals_query->execute();
$goals_result = $goals_query->get_result();
$goals = [];
while ($goal = $goals_result->fetch_assoc()) {
    $goals[$goal['player_id']] = $goal['goals'];
}
$goals_query->close();

// 获取黄牌数据
$yellow_cards_query = $conn->prepare("SELECT player_id, yellow_cards FROM yellow_cards WHERE match_id = ?");
$yellow_cards_query->bind_param("i", $match_id);
$yellow_cards_query->execute();
$yellow_cards_result = $yellow_cards_query->get_result();
$yellow_cards = [];
while ($yellow_card = $yellow_cards_result->fetch_assoc()) {
    $yellow_cards[$yellow_card['player_id']] = $yellow_card['yellow_cards'];
}
$yellow_cards_query->close();

// 获取当前进度
$score_query = $conn->prepare("SELECT * FROM line WHERE mid = ?");
$score_query->bind_param("i", $match_id);
$score_query->execute();
$score_result = $score_query->get_result();

// 如果没有记录，则初始化红色和蓝色的占比为 50%
if ($score_result->num_rows > 0) {
    $score = $score_result->fetch_assoc();
    $redPercentage = $score['red'];
    $bluePercentage = $score['blue'];
} else {
    $redPercentage = 50;
    $bluePercentage = 50;
    // 插入一条记录，初始 red=50，blue=50
    $insert_query = $conn->prepare("INSERT INTO line (mid, red, blue) VALUES (?, ?, ?)");
    $insert_query->bind_param("iii", $match_id, $redPercentage, $bluePercentage);
    $insert_query->execute();
    $insert_query->close();
}
// 获取投票人数
$voter_query = $conn->prepare("SELECT voter, voteb FROM line WHERE mid = ?");
$voter_query->bind_param("i", $match_id);
$voter_query->execute();
$voter_result = $voter_query->get_result();
$voter_data = $voter_result->fetch_assoc();
$voter_query->close();

// 如果找到了记录，则从中获取投票人数
$voter = isset($voter_data['voter']) ? $voter_data['voter'] : 0;
$voteb = isset($voter_data['voteb']) ? $voter_data['voteb'] : 0;

// 获取队伍A和队伍B的名称
$team_a_query = $conn->prepare("SELECT name FROM teams WHERE id = ?");
$team_a_query->bind_param("i", $match['team_a_id']);
$team_a_query->execute();
$team_a_result = $team_a_query->get_result();
$team_a = $team_a_result->fetch_assoc();
$team_a_query->close();

$team_b_query = $conn->prepare("SELECT name FROM teams WHERE id = ?");
$team_b_query->bind_param("i", $match['team_b_id']);
$team_b_query->execute();
$team_b_result = $team_b_query->get_result();
$team_b = $team_b_result->fetch_assoc();
$team_b_query->close();

$ratings_query = $conn->prepare("SELECT player_id, rating FROM player_ratings WHERE match_id = ?");
$ratings_query->bind_param("i", $match_id);
$ratings_query->execute();
$ratings_result = $ratings_query->get_result();
$player_ratings = [];
while ($rating = $ratings_result->fetch_assoc()) {
    $player_ratings[$rating['player_id']] = $rating['rating'];
}
$ratings_query->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>比赛详情 - 懂班帝</title>
    <link rel="stylesheet" href="match.css">
    <link rel="icon" type="image/x-icon" href="./assets/logo.ico" />
    <style>
    .player-item h3 .firston-icon {
        margin-top: 5px;
        color: gold; 
        margin-right: 0px; 
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
        <h1>比赛详情</h1>
        <nav>
            <a href="index.php">首页</a>
            <a href="logout.php">退出</a>
        </nav>
    </header>

    <section id="match-details">
        <h2>比赛信息</h2>
        <p>队伍：<?php echo $team_a['name']; ?> vs <?php echo $team_b['name'] ?></p>
        <p>比分：<?php echo $match['score_a']; ?> - <?php echo $match['score_b']; ?></p>
        <p>地点：<?php echo $match['location']; ?></p>
        <p>时间：<?php echo $formatted_time; ?></p>
    </section>
    <section id="score-bar">
        <h2>比赛竞猜</h2>
        <div id="progress-bar-container">
            <div id="progress-bar-red" class="progress-bar" style="width: <?php echo $redPercentage; ?>%;"></div>
            <div id="progress-bar-blue" class="progress-bar" style="width: <?php echo $bluePercentage; ?>%;"></div>
        </div>

        <div id="buttons">
            <button id="teama" class="team-button" onclick="updateProgressBar('red')">
                <div class="team-info">
                    <span class="team-name"><?php echo $team_a['name']; ?></span>
                    <span class="vote-count" id="vote-count-red">
                        <span id="vote-count-red-number"><?php echo number_format($voter, 1); ?></span>
                        投票
                    </span>
                </div>
            </button>

            <button id="teamb" class="team-button" onclick="updateProgressBar('blue')">
                <div class="team-info">
                    <span class="team-name"><?php echo $team_b['name']; ?></span>
                    <span class="vote-count" id="vote-count-blue">
                        <span id="vote-count-blue-number"><?php echo number_format($voteb, 1); ?></span>
                        投票
                    </span>
                </div>
            </button>
        </div>

    </section>


    <section id="news">
        <h2>相关新闻</h2>
        <?php if ($news_result->num_rows > 0) { ?>
            <?php while ($news = $news_result->fetch_assoc()) { ?>
                <div class="news-item">
                    <a href="news.php?id=<?php echo $news['nid']; ?>">
                        <?php if ($news['image_path']) { ?>
                            <img src="<?php echo $news['image_path']; ?>" alt="新闻图片" class="news-image">
                        <?php } ?>
                        <div class="news-content">
                            <h3><?php echo $news['title']; ?></h3>
                            <p class="news-text" data-full-content="<?php echo $news['content']; ?>">
                                <?php echo mb_strimwidth($news['content'], 0, 100, "..."); ?>
                            </p>
                        </div>
                    </a>
                </div>
            <?php } ?>
        <?php } else { ?>
            <p>暂无相关新闻。</p>
        <?php } ?>
    </section>

    <section id="players">
        <h2>球员信息</h2>
        <?php
        // 在这里循环输出球员信息
        $players_result->data_seek(0); 
        while ($player = $players_result->fetch_assoc()) { ?>
            <div class="player-item">
                <img src="<?php echo $player['location']; ?>" alt="球员照片" class="player-photo">
                <div class="player-content">
                    <h3>
                    <?php if ($player['firston'] == 1) { ?>
                    <span class="firston-icon">⭐</span>
                    <?php } ?>
                    <?php echo $player['name']; ?> (<span><?php echo $player['position']; ?></span>)
                    </h3>
                    <p>评分：<?php echo isset($player_ratings[$player['id']]) ? number_format($player_ratings[$player['id']], 2) : '无'; ?><br>
                        <!-- 显示进球和黄牌数量及 emoji 表情 -->
                        <?php echo str_repeat("⚽️", isset($goals[$player['id']]) ? $goals[$player['id']] : 0); ?><br>
                        <?php echo str_repeat("🟡", isset($yellow_cards[$player['id']]) ? $yellow_cards[$player['id']] : 0); ?>
                    </p>
                </div>
                <div class="stars-container">
                    <div class="stars">
                        <span class="star" data-value="2"></span>
                        <span class="star" data-value="4"></span>
                        <span class="star" data-value="6"></span>
                        <span class="star" data-value="8"></span>
                        <span class="star" data-value="10"></span>
                    </div>
                    <button class="submit-rating" data-player-id="<?php echo $player['id']; ?>"
                        data-match-id="<?php echo $match_id; ?>">提交评分</button>
                </div>
            </div>
        <?php } ?>
    </section>

    <script src="script.js"></script>
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
        let redPercentage = <?php echo $redPercentage; ?>;  // PHP 传递给 JavaScript
        let bluePercentage = <?php echo $bluePercentage; ?>;  // PHP 传递给 JavaScript

        document.addEventListener("DOMContentLoaded", function () {
            const redBar = document.getElementById("progress-bar-red");
            const blueBar = document.getElementById("progress-bar-blue");

            // 初始化时根据数据库中的值设置宽度
            redBar.style.width = redPercentage + "%";
            blueBar.style.width = bluePercentage + "%";

            // 点击事件：更新进度
            document.getElementById("teama").addEventListener("click", function () {
                updateProgressBar('red');
            });

            document.getElementById("teamb").addEventListener("click", function () {
                updateProgressBar('blue');
            });
        });

        function updateProgressBar(team) {
            const redBar = document.getElementById("progress-bar-red");
            const blueBar = document.getElementById("progress-bar-blue");

            // 根据点击的队伍更新占比
            if (team === 'red') {
                redPercentage += 1;  // 红色增加 1%
                bluePercentage = 100 - redPercentage; // 蓝色减少 1%
            } else if (team === 'blue') {
                bluePercentage += 1;  // 蓝色增加 1%
                redPercentage = 100 - bluePercentage;  // 红色减少 1%
            }

            // 向服务器发送数据更新占比
            fetch("update_score.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: `id=<?php echo $match_id; ?>&red=${redPercentage}&blue=${bluePercentage}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // 延迟2秒钟后刷新页面
                        setTimeout(function () {
                            window.location.reload();
                        }, 2000);
                    } else {
                        console.log("进度更新失败");
                    }
                })
                .catch(error => {
                    console.error("请求失败:", error);
                });
        }

        document.addEventListener("DOMContentLoaded", function () {
            let selectedRating = 0;  // 当前选择的评分
            let playerId = 0;  // 当前球员ID

            // 为每颗星星添加点击事件
            document.querySelectorAll('.star').forEach(star => {
                star.addEventListener('click', function () {
                    selectedRating = parseInt(star.getAttribute('data-value'));
                    updateStars(star);
                });
            });

            // 更新星星的状态（高亮）
            function updateStars(selectedStar) {
                document.querySelectorAll('.star').forEach(star => {
                    if (parseInt(star.getAttribute('data-value')) <= selectedRating) {
                        star.classList.add('selected');
                    } else {
                        star.classList.remove('selected');
                    }
                });
            }

            // 提交评分
            document.querySelectorAll('.submit-rating').forEach(button => {
                button.addEventListener('click', function () {
                    playerId = button.getAttribute('data-player-id');
                    matchId = button.getAttribute('data-match-id');
                    if (selectedRating === 0) {
                        alert('请选择评分!');
                        return;
                    }

                    // 获取当前球员的评分并计算平均分
                    let ratingElement = document.querySelector(`#player-rating-${playerId}`);
                    let currentRating = 0; // 默认值为 0

                    if (ratingElement) {
                        currentRating = parseFloat(ratingElement.textContent) || 0; 
                    }

                    let newRating = (currentRating + selectedRating) / 2;

                    newRating = parseFloat(newRating.toFixed(2)); 

                    fetch('update_player_rating.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `player_id=${playerId}&new_rating=${newRating}&match_id=${matchId}`  // 传递场次ID
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                setTimeout(function () {
                                    window.location.reload();
                                }, 2000);
                            } else {
                                console.log("评分更新失败");
                            }
                        })
                        .catch(error => {
                            console.error("请求失败:", error);
                        });
                });
            });


        });
        document.addEventListener('DOMContentLoaded', function () {
            //select count element
            const redElement = document.querySelector("#vote-count-red-number");
            const blueElement = document.querySelector("#vote-count-blue-number");

            fixVote(redElement)
            fixVote(blueElement)
        });

        function fixVote(element) {
            if (!element) {
                return
            }

            const vote = element.textContent

            const number = Number(vote)

            if (Number.isInteger(number)) {
                return; 
            } else if (!isNaN(number)) {  

                element.textContent = Math.round(number + 0.5); 
                return
            }

            console.warn('vote计数器：类型异常')  
        }
    </script>

</body>

</html>