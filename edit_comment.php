<?php
require_once 'auth.php';
require_role('admin');

$db = get_db();
$id = (int)($_GET['id'] ?? 0);
$stmt = $db->prepare('SELECT * FROM comments WHERE id=?');
$stmt->execute([$id]);
$comment = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$comment){ die('Comment not found'); }

if($_SERVER['REQUEST_METHOD']==='POST'){
    $content = $_POST['content'];
    $stmt = $db->prepare('UPDATE comments SET content=? WHERE id=?');
    $stmt->execute([$content,$id]);
    $db->prepare('INSERT INTO logs (message) VALUES (?)')->execute(["Comment $id edited"]);
    header('Location: ' . BASE_PATH . 'comments');
    exit;
}

include 'header.php';
include 'dashboard_nav.php';
?>
<h2>Edit Comment</h2>
<form method="post">
    <textarea name="content" required><?php echo htmlspecialchars($comment['content']); ?></textarea><br>
    <button type="submit">Save</button>
</form>
<?php include 'footer.php'; ?>
