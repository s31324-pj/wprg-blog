<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF token');
    }
    $email = trim($_POST['email'] ?? '');
    if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $db = get_db();
        $stmt = $db->prepare('SELECT id FROM users WHERE email=?');
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 3600);
            $stmt = $db->prepare('UPDATE users SET reset_token=?, reset_expires=? WHERE id=?');
            $stmt->execute([$token, $expires, $user['id']]);
            $link = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/reset.php?token=' . $token;
            @mail($email, 'Password Reset', "Click the link to reset your password: $link");
        }
        $sent = true;
    } else {
        $error = 'Please enter a valid email.';
    }
}

include 'header.php';
?>
<h2>Forgot Password</h2>
<?php if(isset($sent)): ?>
<p>If that email exists in our system, a reset link has been sent.</p>
<?php elseif(isset($error)): ?>
<p style="color:red"><?php echo htmlspecialchars($error); ?></p>
<?php endif; ?>
<form method="post">
    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
    <input type="email" name="email" placeholder="Your email" required>
    <button type="submit">Send Reset Link</button>
</form>
<?php include 'footer.php'; ?>
