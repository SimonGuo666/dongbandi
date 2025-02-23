<?php
session_start();

// 验证队长权限
if (!isset($_SESSION['user']) || $_SESSION['user']['admin'] != 1) {
    header("Location: index.php");
    exit();
}

// 数据库连接配置
$servername = "localhost";
$username = "dongqiudi";
$password = "800813";
$dbname = "football_system";

// 创建连接
$conn = new mysqli($servername, $username, $password, $dbname);

// 检测连接
if ($conn->connect_error) {
    die("连接失败: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 获取 action, 用于区分不同操作
    $action = $_POST["action"];

    if ($action == "update_location") {
        //  处理更新位置的逻辑
        $player_id = $_POST["player_id"];
        $location = $_POST["location"];

        // 数据验证和清理 (根据你的需要添加更多验证)
        if (empty($location)) {
            echo "<script>alert('位置不能为空！');window.location.href='captain_center.php';</script>";
            exit();
        }
        //TODO 可以校验 $location的长度/内容。位置通常是  C M D LW 等等

        // 更新球员位置的 SQL 语句  -SQL层面 没有对 location的 位置或者类型做任何限制。

        $sql = "UPDATE players SET `location` = ? WHERE id = ?";    //务必保证DB有Location 列 建议你进行log打印验证. 你应该知道在执行SQL时候往哪一个column 里面插入数据吧。location column 用于表示 Player 最终更新数据所作用的数据库表的column，而不是随意的字符串

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $location, $player_id);    // si表示 string, integer ; 注意顺序，和 SQL 里的 ? 对应

        if ($stmt->execute() === TRUE) {
            echo "<script>alert('球员位置更新成功！');window.location.href='captain_center.php';</script>";
        } else {

            echo "更新球员位置失败: " . $conn->error;   //TODO 是否这里进入？SQL的执行失败 需要在这里展示。可以将错误都打印到log文件中。通过error_log可以保存到log

            error_log("SQL 错误: " . $conn->error); //日志代码.方便回溯跟踪 如果开发者能够及时的把ERROR级别的错误记录在案，并且保留上下文，问题排查效率会提升非常多。这是一个专业开发 应该做的
            error_log("SQL 语句: " . $sql);

        }
        $stmt->close(); //需要关闭资源

    } elseif ($action == "normal_player_action") {

        // 处理设置/取消首发的逻辑
        $player_id = $_POST["player_id"];

        // 三元运算符。 需要对checkbox 没选中的情况进行兼容,没有被checked,POST过来就是undefined 此时可以用三元运算做兼容

        $set_firston = isset($_POST["set_firston"]) ? 1 : 0; // 如果checkbox 被勾选 -> 1, 否则 0

        // 更新球员 firston 状态的 SQL 语句 - 更新 `firston` 状态不受任何位置或者 list 限制

        $sql = "UPDATE players SET firston = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $set_firston, $player_id);

        if ($stmt->execute() === TRUE) {

            //  在首发球员设置成功后返回到管理界面。可以通过刷新实现 及时的页面更新 方便管理及时看到更新.提升效率

            echo "<script>alert('球员首发状态更新成功！');window.location.href='captain_center.php';</script>";//TODO 返回时候需要重新请求 captain_center

        } else {
            //错误警告弹窗可以提示更友好的错误提示 便于开发Debug
            echo "更新球员首发状态失败: " . $conn->error; //可以将 SQL 错误输出到控制台方便debug

        }
        $stmt->close();//记得要释放
    } else {

        echo "未知的 action: " . $action; //是否代码会跑到这里需要确认

    }
}

$conn->close();

exit;

?>