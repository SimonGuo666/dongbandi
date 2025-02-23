<?php
session_start();

// 检查是否登录
if (!isset($_COOKIE['username'])) {
    header("Location: login.php");
    exit();
}

// 获取新闻ID
$news_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($news_id == 0) {
    header("Location: index.php");
    exit();
}

$conn = new mysqli('localhost', 'dongqiudi', '800813', 'football_system');

// 获取新闻详细信息
$news_query = $conn->prepare("SELECT * FROM news WHERE nid = ?");
$news_query->bind_param("i", $news_id);
$news_query->execute();
$news_result = $news_query->get_result();
$news = $news_result->fetch_assoc();
$news_query->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>新闻详情 - 懂班帝</title>
    <link rel="stylesheet" href="match.css">
    <link rel="icon" type="image/x-icon" href="./assets/logo.ico" />
    <style>
        .news-image {
            width: 300px;
            height: auto;
            object-fit: cover;
            margin-right: 20px;
            border-radius: 8px;
        }
    </style>
</head>

<body>
    <header>
        <img src="./assets/white.png" alt="Logo" class="logo">
        <h1>新闻详情</h1>
        <nav>
            <a href="index.php">首页</a>
            <a href="logout.php">退出</a>
        </nav>
    </header>

    <section id="news-detail">
        <?php if ($news['image_path']) { ?>
            <img src="<?php echo $news['image_path']; ?>" alt="新闻图片" class="news-image"
                style="margin-bottom: 20px;margin-top: 10px;">
        <?php } ?>
        <h2 style="margin-bottom: 10px;"><?php echo $news['title']; ?></h2>
        <p><?php echo $news['content']; ?></p>
    </section><br>
    <hr>
    <section id="comments">
        <h3>评论区</h3>
        <form method="post" action="comment_action.php?id=<?php echo $news_id; ?>" id="comment-form">
            <input type="text" name="comment" placeholder="发表你的评论..." />
            <button type="submit">提交评论</button>
        </form>
        <br>
        <div id="comment-list">
            <?php
            $conn = new mysqli('localhost', 'dongqiudi', '800813', 'football_system');

            $comment_query = $conn->prepare("
        SELECT comments.*, users.username, users.ava 
        FROM comments 
        JOIN users ON comments.user_name = users.username 
        WHERE comments.news_id = ? 
        ORDER BY comments.likes DESC
    ");

            $comment_query->bind_param("i", $news_id);
            $comment_query->execute();
            $comment_result = $comment_query->get_result();

            while ($comment = $comment_result->fetch_assoc()) {
                echo "<div class='comment-item' id='comment-" . $comment['id'] . "'>";

                // 用户头像和名字
                echo "<div class='comment-author'>";
                echo "<img src='" . $comment['ava'] . "' alt='" . $comment['username'] . "' class='avatar'>";
                echo "<p class='username'>" . $comment['username'] . "</p>";
                echo "</div>";

                // 评论内容
                echo "<div style='height:10px;'></div><p style='margin-left:10px; margin-right:10px; max-width:80%; text-align:left; word-wrap:break-word;'>" . $comment['comment'] . "</p><div style='height:10px;'></div>";

                echo "<div class='like-container'>";
                echo "<p id='like-count-" . $comment['id'] . "' class='likes-count'>点赞: " . $comment['likes'] . "</p>";
                echo "<button id='like-btn-" . $comment['id'] . "' class='like-btn' data-comment-id='" . $comment['id'] . "'>点赞</button>";
                echo "</div>";

                echo "</div>";
            }

            $comment_query->close();
            $conn->close();
            ?>

        </div>
    </section>


    <script src="script.js"></script>
</body>

</html>