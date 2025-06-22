<?php
require_once 'config.php';

$db = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4", DB_USER, DB_PASS);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

initialize_db($db);
$db->exec("USE `" . DB_NAME . "`");

if (users_exist($db)) {
    echo "Setup has already been completed.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $email = trim($_POST['email'] ?? '');
    if ($username && $password !== '' && $email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $stmt = $db->prepare('INSERT INTO users (username, password, email, is_verified, verification_code, role) VALUES (?,?,?,?,?,?)');
        $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT), $email, 1, '', 'admin']);
        echo "Setup complete. You can now <a href='" . BASE_PATH . "login'>log in</a>.";
        exit;
    } else {
        $error = 'Please provide valid data.';
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Install</title>
</head>
<body>
<h2>Initial Setup</h2>
<?php if(isset($error)) echo '<p style="color:red">'.htmlspecialchars($error).'</p>'; ?>
<form method="post">
    <label>Username <input type="text" name="username" required></label><br>
    <label>Email <input type="email" name="email" required></label><br>
    <label>Password <input type="password" name="password" required></label><br>
    <button type="submit">Create Admin Account</button>
</form>
</body>
</html>
