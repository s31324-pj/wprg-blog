<?php
require_once 'auth.php';
require_login();


$db = get_db();
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(!verify_csrf_token($_POST['csrf_token'] ?? '')){
        die('Invalid CSRF token');
    }

    $new = $_POST['new_password'] ?? '';
    if ($new !== '') {
        $stmt = $db->prepare('UPDATE users SET password=? WHERE id=?');
        $stmt->execute([password_hash($new, PASSWORD_DEFAULT), $user['id']]);
        $message = 'Password updated';
    }

    $picture_path = trim($_POST['profile_picture_path'] ?? '');
    if($picture_path !== ''){
        $stmt = $db->prepare('UPDATE users SET profile_picture=? WHERE id=?');
        $stmt->execute([$picture_path, $user['id']]);
        $message = isset($message) ? $message . ' Profile picture updated' : 'Profile picture updated';
    }

    $stmt = $db->prepare('SELECT * FROM users WHERE id=?');
    $stmt->execute([$user['id']]);
    $_SESSION['user'] = $stmt->fetch(PDO::FETCH_ASSOC);
    $user = $_SESSION['user'];
}

include 'header.php';
include 'dashboard_nav.php';
?>
<h2>Dashboard</h2>
<p>
    <img src="<?php echo BASE_PATH . (!empty($user['profile_picture']) ? $user['profile_picture'] : 'content/default-pfp.webp'); ?>" class="avatar" alt="Profile">
    Welcome, <?php echo htmlspecialchars($user['username']); ?>!
</p>

<h3>Change Password</h3>
<?php if(isset($message)) echo '<p>'.htmlspecialchars($message).'</p>'; ?>
<?php if(isset($error)) echo '<p style="color:red">'.htmlspecialchars($error).'</p>'; ?>
<form method="post">
    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
    <label>New password <input type="password" name="new_password"></label><br>
    <input type="hidden" name="profile_picture_path" id="pfp-path" value="<?php echo htmlspecialchars($user['profile_picture'] ?? ''); ?>">
    <button type="button" onclick="window.open('media.php?popup=1&field=pfp-path&profile=1', 'media', 'width=800,height=600');">Select picture</button>
    <img id="pfp-path-preview" style="max-width:100px"><br>
    <button type="submit">Update</button>
</form>
<script>
    function updatePreview(id){
        var inp = document.getElementById(id);
        var prev = document.getElementById(id+'-preview');
        if(prev){ prev.src = inp.value ? '<?php echo BASE_PATH; ?>' + inp.value : ''; }
    }
    document.getElementById('pfp-path').addEventListener('change', function(){ updatePreview('pfp-path'); });
    updatePreview('pfp-path');
</script>
<?php include 'footer.php'; ?>
