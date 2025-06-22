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
        $_SESSION['flash_message'] = 'New API key generated (store it safely).';
        $_SESSION['new_api_key'] = $key;
        header('Location: ' . BASE_PATH . 'api_key');
        exit;
    }
    if(isset($_POST['delete'])){
        $stmt = $db->prepare('UPDATE users SET api_key=NULL WHERE id=?');
        $stmt->execute([$user['id']]);
        $_SESSION['user']['api_key'] = null;
        $_SESSION['flash_message'] = 'API key removed';
        header('Location: ' . BASE_PATH . 'api_key');
        exit;
    }
}

$message = $_SESSION['flash_message'] ?? null;
$new_key = $_SESSION['new_api_key'] ?? null;
unset($_SESSION['flash_message'], $_SESSION['new_api_key']);
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
        <?php if($api_key): ?>
        <p>An API key is set for your account.</p>
        <pre id="current-key" style="visibility:hidden"><?php echo htmlspecialchars($api_key); ?></pre>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <button type="submit" name="generate">Regenerate Key</button>
            <button type="button" id="show-key">Show API key</button>
            <button type="submit" name="delete" onclick="return confirm('Delete the key?');" class="delete-button">Delete Key</button>
        </form>
        <script>
            document.getElementById('show-key').addEventListener('click', function () {
                var pre = document.getElementById('current-key');
                if (pre.style.visibility === 'hidden') {
                    pre.style.visibility = 'visible';
                    this.textContent = 'Hide API key';
                } else {
                    pre.style.visibility = 'hidden';
                    this.textContent = 'Show API key';
                }
            });
        </script>
        <?php else: ?>        <p>You do not have an API key yet.</p>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <button type="submit" name="generate">Generate Key</button>
        </form>
        <?php endif; ?>
    </div>
</section>

<?php include 'footer.php'; ?>
