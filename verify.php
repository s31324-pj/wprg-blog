<?php
require_once 'config.php';

$code = $_GET['code'] ?? '';
$db = get_db();
$stmt = $db->prepare('SELECT id FROM users WHERE verification_code=?');
$stmt->execute([$code]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if($user){
    $stmt = $db->prepare('UPDATE users SET is_verified=1, verification_code=NULL WHERE id=?');
    $stmt->execute([$user['id']]);
    $message = 'Account verified. You may now log in.';
}else{
    $message = 'Invalid verification code.';
}

include 'header.php';
?>
<h2>Email Verification</h2>
<p><?php echo htmlspecialchars($message); ?></p>
<?php include 'footer.php'; ?>


