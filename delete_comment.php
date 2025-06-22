<?php
require_once 'auth.php';
require_role('admin');
$db = get_db();
$id = (int)($_GET['id'] ?? 0);
$stmt = $db->prepare('DELETE FROM comments WHERE id=?');
$stmt->execute([$id]);
$db->prepare('INSERT INTO logs (message) VALUES (?)')->execute(["Comment $id deleted"]);
$ref = $_SERVER['HTTP_REFERER'] ?? BASE_PATH;
header('Location: ' . $ref);
exit;
?>
