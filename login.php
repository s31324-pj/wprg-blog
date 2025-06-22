<?php
require_once 'config.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(!verify_csrf_token($_POST['csrf_token'] ?? '')){
        die('Invalid CSRF token');
    }
    $db = get_db();
    $stmt = $db->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([$_POST['username']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && password_verify($_POST['password'], $user['password'])) {
        if(!$user['is_verified']){
            $error = 'Please verify your email before logging in.';
        }else{
            $_SESSION['user'] = $user;
            $db->prepare('INSERT INTO logs (message) VALUES (?)')->execute(["User {$user['username']} logged in"]);
            header('Location: ' . BASE_PATH);
            exit;
        }
    } else {
        $error = 'Invalid credentials';
    }
}
include 'header.php';
?>
<h2>Login</h2>
<?php if(isset($error)) echo '<p style="color:red">'.$error.'</p>'; ?>
<form method="post">
    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
    <input type="text" name="username" placeholder="Username" required>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit">Login</button>
</form>
<p><a href="<?php echo BASE_PATH; ?>forgot">Forgot password?</a></p>
<?php include 'footer.php'; ?>

