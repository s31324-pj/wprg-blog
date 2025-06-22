<?php
require_once 'config.php';
$db = get_db();
$token = $_GET['token'] ?? ($_POST['token'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF token');
    }
    $stmt = $db->prepare('SELECT id, reset_expires FROM users WHERE reset_token=?');
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && strtotime($user['reset_expires']) > time()) {
        $password = $_POST['password'] ?? '';
        if ($password !== '') {
            $stmt = $db->prepare('UPDATE users SET password=?, reset_token=NULL, reset_expires=NULL WHERE id=?');
            $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
            $success = true;
        } else {
            $error = 'Invalid password';
        }
    } else {
        $error = 'Invalid or expired token';
    }
} else {
    if ($token) {
        $stmt = $db->prepare('SELECT id, reset_expires FROM users WHERE reset_token=?');
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user || strtotime($user['reset_expires']) <= time()) {
            $invalid = true;
        }
    } else {
        $invalid = true;
    }
}

include 'header.php';
?>
<h2>Reset Password</h2>
<?php if(isset($success)): ?>
<p>Password updated. You may <a href="<?php echo BASE_PATH; ?>login">log in</a>.</p>
<?php elseif(isset($invalid) && $invalid): ?>
<p>Invalid or expired token.</p>
<?php else: ?>
<?php if(isset($error)) echo '<p style="color:red">'.htmlspecialchars($error).'</p>'; ?>
<form method="post">
    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
    <input type="password" name="password" placeholder="New password" required>
    <button type="submit">Reset Password</button>
</form>
<?php endif; ?>
<?php include 'footer.php'; ?>
