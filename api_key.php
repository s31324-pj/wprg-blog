<?php
require_once 'auth.php';
require_role('author');

$db = get_db();
$user = current_user();

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    if(!verify_csrf_token($_POST['csrf_token'] ?? '')){
        die('Invalid CSRF token');
    }
    if(isset($_POST['generate'])){
        $key = bin2hex(random_bytes(32));
        $stmt = $db->prepare('UPDATE users SET api_key=? WHERE id=?');
        $stmt->execute([$key, $user['id']]);
        $_SESSION['user']['api_key'] = $key;
        $message = 'New API key generated';
        $new_key = $key;
    }
    if(isset($_POST['delete'])){
        $stmt = $db->prepare('UPDATE users SET api_key=NULL WHERE id=?');
        $stmt->execute([$user['id']]);
        $_SESSION['user']['api_key'] = null;
        $message = 'API key removed';
    }
}

$stmt = $db->prepare('SELECT api_key FROM users WHERE id=?');
$stmt->execute([$user['id']]);
$api_key = $stmt->fetchColumn();

include 'header.php';
include 'dashboard_nav.php';
?>


<section class="api-page">
    <div class="content">
        <h2>API Key Management</h2>
        <?php if(isset($message)) echo '<p>'.htmlspecialchars($message).'</p>'; ?>
        <?php if(isset($new_key)): ?>
        <p>Your new API key (store it safely):</p>
        <pre><?php echo htmlspecialchars($new_key); ?></pre>
        <?php endif; ?>
        <?php if($api_key): ?>
        <p>An API key is set for your account.</p>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <button type="submit" name="generate">Regenerate Key</button>
            <button type="submit" name="delete" onclick="return confirm('Delete the key?');">Delete Key</button>
        </form>
        <?php else: ?>
        <p>You do not have an API key yet.</p>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <button type="submit" name="generate">Generate Key</button>
        </form>
        <?php endif; ?>
    </div>
</section>

<?php include 'footer.php'; ?>
