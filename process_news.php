<?php
$servername = "localhost";
$username = "你的数据库用户名";
$password = "你的数据库密码";
$dbname = "你的数据库名";

$conn = new mysqli('localhost', 'dongqiudi', '800813', 'football_system');

if ($conn->connect_error) {
    die("连接失败: " . $conn->connect_error);
}

$title = $_POST["title"];
$content = $_POST["content"];
$teams = isset($_POST["teams"]) ? $_POST["teams"] : [];

if (count($teams) != 2) {
    die("请选择两个队伍。");
}

$rel = implode(",", $teams);

$image_path = null;

if (isset($_FILES["image"]) && $_FILES["image"]["error"] == 0) {
    $target_dir = "./assets/news/";
    $target_file = $target_dir . basename($_FILES["image"]["name"]);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    $allowed_types = array("jpg", "jpeg", "png", "gif");
    if (!in_array($imageFileType, $allowed_types)) {
        die("只允许上传 JPG, JPEG, PNG 和 GIF 文件。");
    }

    if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
        $image_path = $target_file;
    } else {
        echo "图片上传失败。";
    }
}

$sql = "INSERT INTO news (title, content, rel, image_path) VALUES ('$title', '$content', '$rel', '$image_path')";

if ($conn->query($sql) === TRUE) {
    // 使用 JavaScript 跳转回 publish_news.php
    echo '<script type="text/javascript">';
    echo 'alert("News 发布成功！");'; // 可以显示一个提示消息
    echo 'window.location.href = "ananews.php";';
    echo '</script>';
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;

     // 使用 JavaScript 跳转回 publish_news.php
     echo '<script type="text/javascript">';
     echo 'alert("News 发布失败！");'; // 可以显示一个提示消息
     echo 'window.location.href = "publish_news.php";';
     echo '</script>';
}

$conn->close();

?>