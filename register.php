<?php
session_start();  // 开启会话，保存错误消息

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $main_team_id = $_POST['main_team_id'];

    if ($password !== $confirm_password) {
        $_SESSION['error_message'] = "密码和确认密码不匹配";
        header("Location: register.php");
        exit();
    }

    $conn = new mysqli('localhost', 'dongqiudi', '800813', 'football_system');
    if ($conn->connect_error) {
        die("连接失败: " . $conn->connect_error);
    }

    $email_check_query = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $email_check_query->bind_param("s", $email);
    $email_check_query->execute();
    $email_check_result = $email_check_query->get_result();

    if ($email_check_result->num_rows > 0) {
        $_SESSION['error_message'] = "该电子邮件已被注册";
        $email_check_query->close();
        $conn->close();
        header("Location: register.php");
        exit();
    }

    // 检查用户名是否已存在
    $username_check_query = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $username_check_query->bind_param("s", $username);
    $username_check_query->execute();
    $username_check_result = $username_check_query->get_result();

    if ($username_check_result->num_rows > 0) {
        $_SESSION['error_message'] = "该用户名已被注册";
        $username_check_query->close();
        $conn->close();
        header("Location: register.php");
        exit();
    }

    // 插入用户数据
    $stmt = $conn->prepare("INSERT INTO users (email, username, password, main_team_id) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $email, $username, $password, $main_team_id);
    $stmt->execute();

    // 注册成功后清除错误信息，并重定向到登录页面
    $_SESSION['success_message'] = "注册成功！您已成功创建账户！";
    $stmt->close();
    $conn->close();

    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>注册 - 懂班帝</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/x-icon" href="./assets/logo.ico" />
    <style>
    .modal-content a.modal-link {
    display: inline-block;
    padding: 10px 20px;
    background-color: #4CAF50;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    margin-top: 10px;
}
        /* Basic Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4); /* Black with opacity */
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: #fefefe;
            margin: auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px; /* Added maximum width */
            border-radius: 5px;
            box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2);
        }

        /* Close button style */
        .close-btn {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close-btn:hover,
        .close-btn:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

    </style>
</head>

<body>
    <div class="container">
        <div class="form-container">
            <img src="./assets/blue.png" alt="Logo" class="logo">
            <h2>创建一个新账户</h2>
            <form action="register.php" method="POST">
                 <!-- Warning Modal Button -->
                <button type="button" id="openWarningModal" style="display:none;">查看注册信息</button>

                <div class="input-group">
                    <label for="username">用户名</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="input-group">
                    <label for="email">电子邮件</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="input-group">
                    <label for="password">密码</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="input-group">
                    <label for="confirm_password">确认密码</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <div class="input-group">
                    <label for="main_team_id">选择主队</label>
                    <select id="main_team_id" name="main_team_id" required>
                        <?php
                        // 获取所有队伍
                        $conn = new mysqli('localhost', 'dongqiudi', '800813', 'football_system');
                        if ($conn->connect_error) {
                            die("连接失败: " . $conn->connect_error);
                        }

                        $teams_result = $conn->query("SELECT id, name FROM teams");
                        while ($team = $teams_result->fetch_assoc()) {
                            echo "<option value='" . $team['id'] . "'>" . $team['name'] . "</option>";
                        }

                        $conn->close();
                        ?>
                    </select>
                </div>
                 <button type="button" id="submitButton">注册</button>

               
            </form>
            <p>已有账号？ <a href="login.php">登录</a></p>
        </div>
    </div>

      <div id="warningModal" class="modal">
            <div class="modal-content">
                <span class="close-btn" onclick="closeWarningModal()">×</span>
                <h2>请注意！</h2>
                <p>我们强烈建议您使用真实姓名作为用户名，以便后期可以自动同步到球员列表中。</p><br>
                <p>如果您参赛，主队必须为您的参赛队伍！</p>
                 <button onclick="submitForm()">我已知晓并继续注册</button>

            </div>
      </div>


    <?php if (isset($_SESSION['error_message'])): ?>
        <div id="errorModal" class="modal">
            <div class="modal-content">
                <span class="close-btn" onclick="closeModal()">×</span>
                <h2>注册失败</h2>
                <p><?php echo $_SESSION['error_message']; ?></p>
                <button onclick="closeModal()">关闭</button>
            </div>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div id="successModal" class="modal">
            <div class="modal-content">
                <span class="close-btn" onclick="closeModal()">×</span>
                <h2>注册成功！</h2>
                <p><?php echo $_SESSION['success_message']; ?></p>
            </div>
        </div>
        <?php unset($_SESSION['success_message']); ?>

       <script>
            window.onload = function() {
                const successModal = document.getElementById("successModal");
                 if (successModal) {
                     successModal.style.display = "flex";
                     setTimeout(function() {
                        window.location.href = "login.php";
                    }, 2000); 
                  }
               };

         </script>
        <?php endif; ?>

    <script>
           const warningModal = document.getElementById("warningModal");
           const openWarningModalButton = document.getElementById("openWarningModal"); 
            const submitButton = document.getElementById("submitButton");      
           const form = document.querySelector("form");                            

        function closeWarningModal() {
              warningModal.style.display = "none"; 
         }


        function submitForm() {
            form.submit();   
            }
        submitButton.addEventListener('click', function(event) {
                 event.preventDefault();
            warningModal.style.display = "flex";
             });

       function closeModal() {
            const modal = document.getElementById('errorModal');
            const successModal = document.getElementById('successModal');
            modal.style.display = 'none';
        }

          <?php if (isset($_SESSION['error_message'])): ?>
            const errorModal = document.getElementById("errorModal");
            errorModal.style.display = "flex";
           
         <?php endif; ?>
    </script>
</body>

</html>