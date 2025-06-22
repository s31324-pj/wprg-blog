<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Simple Blog</title>
    <link rel="stylesheet" href="<?php echo BASE_PATH; ?>css/style.css">
    <script src="https://cdn.tiny.cloud/1/qzjeqaego350wuyybmdnnoxp884v9vl3bihl8ptayx8sn2t9/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
</head>
<body>
<header>
    <div class="content">
        <h1><a href="<?php echo BASE_PATH; ?>">Simple Blog</a></h1>
        <nav>
            <a href="<?php echo BASE_PATH; ?>">Home</a>
            <a href="<?php echo BASE_PATH; ?>contact">Contact</a>
            <?php if(isset($_SESSION['user'])): ?>
                <a href="<?php echo BASE_PATH; ?>dashboard" class="user-info">
                    <img src="<?php echo BASE_PATH . (!empty($_SESSION['user']['profile_picture']) ? $_SESSION['user']['profile_picture'] : 'content/default-pfp.webp'); ?>" alt="Profile" class="avatar">
                    <div id="header-greeting"><span id="header-hello">Hello,</span><span id="header-username"><?php echo htmlspecialchars($_SESSION['user']['username']); ?>!</span></div>
                </a>
            <?php else: ?>
                <a href="<?php echo BASE_PATH; ?>login" class="login-link">Login</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main>
