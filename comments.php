<?php
require_once 'auth.php';
require_role('admin');

$db = get_db();
$comments = $db->query('SELECT c.*, p.title AS post_title, u.username FROM comments c LEFT JOIN posts p ON c.post_id = p.id LEFT JOIN users u ON c.user_id = u.id ORDER BY c.created_at DESC')->fetchAll(PDO::FETCH_ASSOC);

include 'header.php';
include 'dashboard_nav.php';
?>
<h2>All Comments</h2>
<table>
    <tr>
        <th>Post</th>
        <th>Author</th>
        <th>Content</th>
        <th>Date</th>
        <th>Actions</th>
    </tr>
    <?php foreach($comments as $c): ?>
    <tr>
        <td><?php echo htmlspecialchars($c['post_title']); ?></td>
        <td><?php echo htmlspecialchars($c['author']); ?></td>
        <td><?php echo nl2br(htmlspecialchars($c['content'])); ?></td>
        <td><?php echo $c['created_at']; ?></td>
        <td>
            <a href="<?php echo BASE_PATH; ?>delete_comment.php?id=<?php echo $c['id']; ?>" onclick="return confirm('delete?')">Delete</a>
            | <a href="<?php echo BASE_PATH; ?>edit_comment.php?id=<?php echo $c['id']; ?>">Edit</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php include 'footer.php'; ?>
