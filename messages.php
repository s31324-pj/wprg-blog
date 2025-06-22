<?php
require_once 'auth.php';
require_role('admin');
$db = get_db();
$messages = $db->query('SELECT * FROM contacts ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
include 'header.php';
include 'dashboard_nav.php';
?>
<h2>Contact Messages</h2>
<ul>
<?php foreach($messages as $m): ?>
    <li>
        <strong><?php echo htmlspecialchars($m['email']); ?></strong> (<?php echo $m['created_at']; ?>)
        <p><?php echo htmlspecialchars($m['subject']); ?></p>
        <p><?php echo nl2br(htmlspecialchars($m['message'])); ?></p>
    </li>
<?php endforeach; ?>
</ul>
<?php include 'footer.php'; ?>
