<?php if (!function_exists('current_user')) require_once 'auth.php'; ?>
<?php $user = current_user(); ?>
<nav class="dashboard-nav">
<?php if($user['role'] === 'author' || $user['role'] === 'admin'): ?>
        <a href="<?php echo BASE_PATH; ?>admin">Manage Posts</a> |
        <a href="<?php echo BASE_PATH; ?>media" target="_blank">Media Library</a> |
        <a href="<?php echo BASE_PATH; ?>api_key">API Key</a> |
    <?php endif; ?>
    <?php if($user['role'] === 'admin'): ?>
        <a href="<?php echo BASE_PATH; ?>messages">Messages</a> |
        <a href="<?php echo BASE_PATH; ?>logs">Logs</a> |
        <a href="<?php echo BASE_PATH; ?>comments">Comments</a> |
        <a href="<?php echo BASE_PATH; ?>users">Manage Users</a> |
    <?php endif; ?>
</nav>
