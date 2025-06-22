<?php
require_once 'config.php';
if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!verify_csrf_token($_POST['csrf_token'] ?? '')){
        die('Invalid CSRF token');
    }
    $email = trim($_POST['email'] ?? '');
    if(!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)){
        $error = 'Valid email required';
    }else{
        $db = get_db();
        $code = bin2hex(random_bytes(32));
        $stmt = $db->prepare('INSERT INTO users (username,password,role,email,is_verified,verification_code) VALUES (?,?,?,?,0,?)');
        try{
            $stmt->execute([$_POST['username'], password_hash($_POST['password'], PASSWORD_DEFAULT), "user", $email, $code]);
            $link = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/verify.php?code=' . $code;
            @mail($email, 'Verify your account', "Click the link to verify: $link");
            header('Location: ' . BASE_PATH . 'login');
            exit;
        }catch(Exception $e){
            $error = 'Registration error';
        }
    }
}
include 'header.php';
?>










<section class="login-page">
    <div class="content">
        <h2>Register</h2>
        <?php if(isset($error)) echo '<p style="color:red">'.$error.'</p>'; ?>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <label for="username">Username</label>
            <input type="text" name="username" required>
            <label for="email">Email</label>
            <input type="email" name="email" required>
            <label for="password">Password</label>
            <input type="password" name="password" required>
            <button type="submit">Register</button>
        </form>
    </div>
</section>









<?php include 'footer.php'; ?>

