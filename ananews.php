<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>发布 News</title>
    <link rel="icon" type="image/x-icon" href="./assets/logo.ico" />
    <style>
        /* 白色背景层 - 一开始就显示，没有渐变 */
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
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .news-item a { text-decoration: none; }
        .news-item a:hover { text-decoration: none; }
        .news-item .news-content h3, .news-item .news-text { text-decoration: none; }

        /* 通用样式 */
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f9;
            padding: 20px;
            color: #333;
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
            color: white;
        }

        .logo {
            width: 250px;
            margin-top: -80px;
            margin-bottom: -70px;
        }

        nav a {
            color: white;
            margin: 0 15px;
            text-decoration: none;
            font-size: 16px;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        nav a:hover {
            text-decoration: underline;
            color: #f4f4f9;
        }

        nav a:focus { outline: none; }

        /* 优化后的表单样式 */
        form {
            width: 600px;
            margin: 20px auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        form label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
        }

        form input[type="text"],
        form textarea,
        form input[type="file"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            margin-bottom: 20px;
            box-sizing: border-box;
            font-size: 16px;
            color: #333;
            transition: border-color 0.3s ease;
        }

        form textarea {
            height: 150px;
            resize: vertical;
        }

        form input[type="text"]:focus,
        form textarea:focus,
        form input[type="file"]:focus {
            outline: none;
            border-color: #009cff;
            box-shadow: 0 0 5px rgba(0, 156, 255, 0.2);
        }

        /* 复选框样式 */
        form label[for^="team_"] {
            display: inline-block;
            margin-right: 15px;
            margin-bottom: 10px;
        }

        form input[type="checkbox"] {
            margin-right: 5px;
        }

        /* 按钮样式 */
        form button {
            background-color: #009cff;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 18px;
            transition: background-color 0.3s ease;
        }

        form button:hover {
            background-color: #007bbd;
        }

        /* 标题样式 */
        h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
            margin-top: 30px;
        }

        /* 手机设备样式 */
        @media (max-width: 600px) {
            form {
                width: 100%;
                padding: 20px;
            }

            form input[type="text"],
            form textarea,
            form input[type="file"] {
                font-size: 14px;
            }

            form button {
                font-size: 16px;
            }

            header h1 {
                font-size: 22px;
                color: white;
            }

            .player-photo {
                margin-bottom: 5px;
            }

            nav a {
                font-size: 14px;
            }

            #match-details h2,
            #news h2,
            #players h2 {
                font-size: 20px;
            }

            #match-details p,
            #news-item p,
            #players p {
                font-size: 14px;
            }

            .news-item h3,
            .player-item p {
                font-size: 14px;
            }

            .news-item,
            .player-item,
            .comment-item {
                flex-direction: column;
                align-items: flex-start;
            }

            .news-image,
            .player-image {
                width: 80px;
                height: 80px;
                margin-bottom: 10px;
            }

            .news-content h3,
            .player-content h3,
            .comment-item p {
                font-size: 14px;
            }

            .news-content p,
            .player-content p {
                font-size: 12px;
            }
        }

        /* 评论输入框样式 */
        #comment-form input {
            padding: 12px 16px;
            border: 1px solid #ccc;
            border-radius: 10px;
            margin-bottom: 15px;
            font-size: 16px;
            color: #333;
            background-color: #fafafa;
            width: 100%;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        /* 聚焦时的样式 */
        #comment-form input:focus {
            outline: none;
            border-color: #009cff;
            background-color: #fff;
            box-shadow: 0 2px 15px rgba(0, 156, 255, 0.3);
        }

        /* 占位符文本的样式 */
        #comment-form input::placeholder {
            color: #aaa;
            font-size: 14px;
            font-style: italic;
        }

        /* 提交按钮样式 */
        #comment-form button {
            padding: 12px 20px;
            background-color: #009cff;
            color: white;
            font-size: 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        #comment-form button:hover {
            background-color: #007acc;
        }

        #score-bar {
            margin-top: 40px;
            text-align: center;
        }

        #progress-bar-container {
            width: 80%;
            height: 30px;
            background-color: #f0f0f0;
            margin: 20px auto;
            position: relative;
            border-radius: 15px;
            overflow: hidden;
            display: flex;
        }

        #progress-bar-red,
        #progress-bar-blue {
            height: 100%;
        }

        #progress-bar-red {
            background-color: red;
        }

        #progress-bar-blue {
            background-color: #009cff;
        }

        #buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 20px;
        }

        .team-button {
            width: 200px;
            /* 调整按钮宽度 */
            height: 50px;
            /* 调整按钮高度 */
            font-size: 16px;
            /* 调整字体大小 */
            padding: 5px;
            /* 调整按钮内边距 */
            margin: 10px;
            /* 添加按钮间距 */
            background-color: #4CAF50;
            /* 按钮背景颜色 */
            color: white;
            /* 按钮文本颜色 */
            border: none;
            /* 去除边框 */
            border-radius: 5px;
            /* 设置圆角 */
            cursor: pointer;
            /* 鼠标悬停时显示手型 */
            transition: background-color 0.3s ease;
            /* 背景颜色过渡效果 */
        }

        .team-button:hover {
            background-color: #45a049;
            /* 鼠标悬停时的背景颜色 */
        }


        .team-info {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .team-name {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 0px;
        }

        .vote-count {
            font-size: 16px;
            color: #ddd;
        }

        .team-button#teama {
            background-color: #f44336;
            /* 红色队伍按钮 */
        }

        .team-button#teamb {
            background-color: #2196F3;
            /* 蓝色队伍按钮 */
        }

        .team-button#teama:hover {
            background-color: #e53935;
        }

        .team-button#teamb:hover {
            background-color: #1976D2;
        }

        .rating-container {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            margin-left: 20px;
            justify-content: space-between;
        }

        .stars-container {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            /* Align the stars and button to the right */
            justify-content: flex-start;
            /* Ensure it starts from the top */
            gap: 5px;
            /* Ensure it takes the full width */
            position: absolute;
            /* Position it relative to the parent container */
            right: 20px;
            /* Align it to the right of the parent container */
            top: 20ppx;
            /* Align it to the top of the parent container */
        }


        .stars {
            display: flex;
            gap: 5px;
            justify-content: flex-end;
            /* Align the stars to the right */
        }

        .star {
            width: 20px;
            height: 20px;
            background-color: #ddd;
            clip-path: polygon(50% 0%, 61% 35%, 98% 35%, 68% 57%, 79% 91%, 50% 70%, 21% 91%, 32% 57%, 2% 35%, 39% 35%);
            cursor: pointer;
        }

        .star.selected {
            background-color: gold;
        }

        .submit-rating {
            margin-top: 5px;
            padding: 5px 10px;
            background-color: #007BFF;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            align-self: flex-end;
        }

        .submit-rating:hover {
            background-color: #0056b3;
        }

        .player-content h3{
            margin-top: 15px;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <header>
        <img src="./assets/white.png" alt="Logo" class="logo">
        <h1>发布 News</h1>
        <nav>
            <a href="index.php">首页</a>
            <a href="logout.php">退出</a>
        </nav>
    </header>

    <h1>发布 News</h1>

    <form action="process_news.php" method="post" enctype="multipart/form-data">
        <label for="title">标题:</label>
        <input type="text" id="title" name="title" required>

        <label for="content">内容:</label>
        <textarea id="content" name="content" required></textarea>

        <label for="rel">关联队伍 (选择两个):</label>
        <?php
            $servername = "localhost";
            $username = "你的数据库用户名";
            $password = "你的数据库密码";
            $dbname = "你的数据库名";

            $conn = new mysqli('localhost', 'dongqiudi', '800813', 'football_system');

            if ($conn->connect_error) {
                die("连接失败: " . $conn->connect_error);
            }

            $sql = "SELECT id, name FROM teams"; 
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    echo '<label for="team_' . $row["id"] . '"><input type="checkbox" id="team_' . $row["id"] . '" name="teams[]" value="' . $row["id"] . '">' . $row["name"] . '</label>';
                }
            } else {
                echo "没有找到队伍。";
            }

            $conn->close();
        ?>

        <label for="image">图片:</label>
        <input type="file" id="image" name="image">

        <button type="submit">发布</button>
    </form>

</body>
</html>