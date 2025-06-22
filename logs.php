<?php
require_once 'auth.php';
require_role('admin');
$db = get_db();
$logs = $db->query('SELECT * FROM logs ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
include 'header.php';
include 'dashboard_nav.php';
?>








<section class="logs-page">
    <div class="content">
        <h2>Logs</h2>
        <ul>
        <?php foreach($logs as $l): ?>
            <li><?php echo $l['created_at']; ?> - <?php echo htmlspecialchars($l['message']); ?></li>
        <?php endforeach; ?>
        </ul>
    </div>
</section>






<?php include 'footer.php'; ?>
