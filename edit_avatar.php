<?php
session_start();

if (!isset($_COOKIE['username'])) {
    header("Location: login.php");
    exit();
}

// 设置上传目录（相对路径，保证 ava 文件夹可写！）
$uploadDirectory = './assets/ava/';

// 最大文件大小（单位：字节）
$maxFileSize = 5 * 1024 * 1024; // 5MB

// 允许的文件类型
$allowedFileTypes = ['image/jpeg', 'image/png', 'image/gif']; // 添加 gif 类型

// 错误消息（根据上传结果进行定制）
$uploadStatus = null;  // 不在if中定义可以保证不会产生 变量范围外读取错误, 同时在前端页面起到类型确认和声明的目的

// 检查表单是否已提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //1. 检查有无文件被上传和 error = 0的情况
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {  //$_FILES['avatar']['error'] === 0,如果代码上传没问题的话就是0,

        //2.验证上传内容,文件大小是否符合
        if ($_FILES['avatar']['size'] > $maxFileSize) {
            $uploadStatus = 'error_filesize'; // "文件大小超出限制"
        }   //验证上传的文件mimeType 保证图片一定是 图片/XXX类型
        else if (!in_array($_FILES['avatar']['type'], $allowedFileTypes)) {
            $uploadStatus = 'error_filetype'; // "不允许的文件类型"
        } else {
            //文件类型正确 开始写入：  获取临时文件名和上传内容进行上传

            //临时文件路径和 name，使用真实类型可以绕过前面的MIME type check
            $tempFile = $_FILES['avatar']['tmp_name'];  // 获取上传临时文件名;

            //1. 安全的文件名:使用 uniqid()防止文件名被恶意利用，可以使用其他方式 (时间戳，HASH
            //使用 pathinfo获取 扩展，然后和 uniqid 拼接生成文件
            $fileInfo = pathinfo($_FILES['avatar']['name']);   //返回一个关联数组，包含了有关 path 的信息.  https://www.php.net/manual/zh/function.pathinfo.php
            $fileName = uniqid() . '.' . strtolower($fileInfo['extension']);    //生成 唯一的文件名 (防注入
            $targetFile = rtrim($uploadDirectory, '/') . '/' . $fileName;   //拼接 upload的目录 +uniqid文件名;  同时清理尾部的 `/`;  "./assets/ava/xxx.jpg"

            // 检查 assets/ava 目录是否存在并且可写，使用真实的，防止相对路径检查有误
            $realUploadDirectory = realpath($uploadDirectory);
            if (!$realUploadDirectory || !is_writable($realUploadDirectory)) {
                $uploadStatus = 'error_dir_permission'; // 目标目录不可写";

                //如果报错的话就使用真实的展示，帮助debug;  使用真实path和message解决相对目录报错的时候问题
                error_log("Upload directory is not writable: " . $realUploadDirectory); //写入到apache的log里
                //写入之后最好删除这个console.warn 因为有暴露目录的风险

            } else {
                //所有验证通过 执行上传：执行上传操作之前最好把该做的校验全部做了(高并发下)
                if (move_uploaded_file($tempFile, $targetFile)) {

                    //文件上传没有问题就写入 DB：文件上传成功后，更新数据库中的用户头像信息
                    $username = $_COOKIE['username'];    //get username from cookie

                    $avaPath = rtrim('./assets/ava/', '/') . '/' . $fileName;    //将assets文件 和  文件名再次进行 拼接; 因为存在/ 或者 \问题

                    //使用单独的链接 更新 user
                    $conn_user = new mysqli('localhost', 'dongqiudi', '800813', 'football_system');
                    if ($conn_user->connect_error) {
                         $uploadStatus = 'error_db'; // "连接数据库失败";
                         error_log("Connection failed: " . $conn_user->connect_error);
                    } else {
                        $updateQuery = $conn_user->prepare("UPDATE users SET ava = ? WHERE username = ?");  //prepare UPDATE SQL

                        $updateQuery->bind_param("ss", $avaPath, $username);    //bindParam 两个 string param

                         if ($updateQuery->execute()) {

                              $uploadStatus = 'success';    //log结束；如果还是有问题直接从文件/配置入手检查 (最好一开始就设置好文件logger方便debug;
                               //设置一个 session成功
                               $_SESSION['avatar_path'] = $avaPath; // 将头像路径保存到session, 其他需要用到的地方到时候进行获取就好.同时持久化数据。

                        } else {
                            $uploadStatus = 'error_db'; // "更新users表失败";
                             error_log("users  Database update failed: " . $conn_user->error);
                        }
                        $updateQuery->close(); // 确保关闭 statement
                      }

                       //用完链接后 关闭users链接 
                       $conn_user->close(); 


                       // 使用新的链接,更新 player
                       $conn_players = new mysqli('localhost', 'dongqiudi', '800813', 'football_system');
                        if ($conn_players->connect_error) {
                              $uploadStatus = 'error_db'; // "连接players数据库失败";
                             error_log("Connection failed: " . $conn_players->connect_error);

                        }else{

                             // 同时更新 players 表如果存在对应的名字
                            $updatePlayersQuery = $conn_players->prepare("UPDATE players SET location = ? WHERE name = ?");
                             $updatePlayersQuery->bind_param("ss", $avaPath, $username);

                              if ($updatePlayersQuery->execute()) {
                                    // players 表更新成功（可选择记录日志）

                                 } else {
                                      error_log("Players table update failed: " . $conn_players->error);

                                }
                              $updatePlayersQuery->close();
                          }  

                           // 确保关闭连接
                       $conn_players->close();       
                     // log结束；如果还是有问题直接从文件/配置入手检查 (最好一开始就设置好文件logger方便debug;
                 } else {
                       $uploadStatus = 'error_upload';  //意外上传失败的情况。
                       error_log("File move_uploaded_file failed. Error code:" . $_FILES['avatar']['error']); //将PHP内部的文件error记录下来。
                }
           }

        }

    } else {

        $uploadStatus = 'error_no_file'; //"请选择要上传的文件";  用户没有选择文件

        error_log("No file was uploaded or unknown upload error. Error code: " . $_FILES['avatar']['error']); //可以使用apache error 打印
        //https://www.php.net/manual/zh/features.file-upload.errors.php
        // $_FILES['avatar']['error'] 可以获取代码 方便debug (代码和message永远是好伙伴.jpg;
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>编辑个人头像 - 懂班帝</title>
    <link rel="stylesheet" href="match.css">
    <link rel="icon" type="image/x-icon" href="./assets/logo.ico" />

    <style>
        .current-avatar {
            max-width: 200px;
            /* 限定头像大小 */
            border-radius: 10px;
            /* 更柔和的边框 */
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            /* 淡淡的阴影，使头像“浮动” */
            transition: all 0.3s ease;
            /* 平滑的过渡效果 */
            display: block; /* 修改：改成block，允许margin生效 */
            margin: 0 auto; /* 修改：使用margin: 0 auto实现水平居中 */
        }

        .current-avatar:hover {
            transform: scale(1.05);
            /* 放大一点 */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
            /* 阴影更明显 */
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

        /*表单的基础styling, 可以写进全局style，这里方便展示 */
        form {
            max-width: 500px;
            /*限制form 宽度  */
            margin: 20px auto;
            /*居中显示*/
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            /* label 粗体  */
        }

        input[type="file"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            /* 保证padding和border 在width以内  */
        }

        button[type="submit"] {
            background-color: #009cff;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }

        button[type="submit"]:hover {
            background-color: #007acc;
        }

        /* 状态信息的样式 */
        .status-message {
            margin-top: 20px;
            padding: 10px;
            border-radius: 4px;
            text-align: center;
            /* 文字居中  */
        }

        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        header {
            background-color: #009cff;
            color: white;
            padding: 15px;
            text-align: center;
        }

        header h1 {
            font-size: 26px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .logo {
            width: 250px;
            margin-top: -80px;
            margin-bottom: -60px;
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
        <h1>编辑个人头像</h1>
        <nav>
            <a href="index.php">首页</a>
            <a href="logout.php">退出</a>
        </nav>
    </header>

    <main>
        <form action="edit_avatar.php" method="post" enctype="multipart/form-data">
            <label for="avatar">选择新头像：</label>
            <input type="file" name="avatar" id="avatar" accept="image/*" required>
            <button type="submit">上传头像</button>
        </form>

        <!-- upload status information output-->
        <?php if ($uploadStatus !== null): ?>
            <div class="status-message <?php echo strpos($uploadStatus, 'error') === 0 ? 'error' : 'success'; ?>">
                <?php
                if ($uploadStatus === 'success') {
                    echo "头像更新成功！";  //可以使用 <strong>标签 来突出
                } else if ($uploadStatus === 'error_no_file') {
                    echo "请选择要上传的文件！";
                } else if ($uploadStatus === 'error_filesize') {
                    echo "文件大小超出限制！";
                } else if ($uploadStatus === 'error_filetype') {
                    echo "不允许的文件类型！";
                } else if ($uploadStatus === 'error_upload') {
                    echo "上传失败，请重试。";
                } else if ($uploadStatus === 'error_dir_permission') {
                    echo "上传目录权限错误，请联系管理员。"; //为了安全考虑 不应该明示目录信息 应该写入LOG 避免泄露
                } else if ($uploadStatus === 'error_db') {
                    echo "数据库更新失败，请重试。";
                } else {
                    echo "未知错误！";    //兜底; 可以写入 Error\_log 方便错误搜索和分析
                }
                ?>
            </div>
        <?php endif; ?>
        <!--显示最新图片信息
        更新完毕或者DB中已经有了头像的情况下, 展示-->
        <?php if (isset($_SESSION['avatar_path']) && $_SESSION['avatar_path']): ?>
            <img src="<?php echo htmlspecialchars($_SESSION['avatar_path']); ?>" alt="当前头像" class="current-avatar">
            <!--XSS protection -->
        <?php endif; ?>

    </main>
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

</body>

</html>