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
    <h1><a href="<?php echo BASE_PATH; ?>">Simple Blog</a></h1>
    <nav>
        <a href="<?php echo BASE_PATH; ?>">Home</a> |
        <a href="<?php echo BASE_PATH; ?>contact">Contact</a> |
        <?php if(isset($_SESSION['user']) && in_array($_SESSION['user']['role'], ['author','admin'])): ?>
            <a href="<?php echo BASE_PATH; ?>dashboard">Dashboard</a> |
        <?php endif; ?>
        <?php if(isset($_SESSION['user'])): ?>
            <a href="<?php echo BASE_PATH; ?>logout">Logout</a>
        <?php else: ?>
            <a href="<?php echo BASE_PATH; ?>login">Login</a>
        <?php endif; ?>
    </nav>
    <?php if(isset($_SESSION['user'])): ?>
        <span class="user-info">
            <img src="<?php echo BASE_PATH . (!empty($_SESSION['user']['profile_picture']) ? $_SESSION['user']['profile_picture'] : 'content/default-pfp.webp'); ?>" alt="Profile" class="avatar">
            <?php echo htmlspecialchars($_SESSION['user']['username']); ?>
        </span>
    <?php endif; ?>
</header>
<main>
