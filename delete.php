<?php
require_once 'auth.php';
require_role('author');
$db = get_db();
if($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? '')){
    die('Invalid CSRF token');
}
$id = (int)($_POST['id'] ?? 0);
$post = $db->prepare('SELECT user_id FROM posts WHERE id=?');
$post->execute([$id]);
$authorId = $post->fetchColumn();
if($authorId === false){
    die('Not found');
}
if(current_user()['role'] !== 'admin' && current_user()['id'] != $authorId){
    die('Access denied');
}
$stmt = $db->prepare('DELETE FROM posts WHERE id=?');
$stmt->execute([$id]);
    $userId = current_user()['id'];
    $db->prepare('INSERT INTO logs (message) VALUES (?)')->execute(["Post $id deleted by user $userId"]);
    header('Location: ' . BASE_PATH . 'admin');
?>
