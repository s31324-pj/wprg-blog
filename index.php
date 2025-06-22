<?php
require_once 'config.php';
$db = get_db();
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 5;
$offset = ($page-1)*$perPage;
$stmt = $db->prepare('SELECT p.*, u.username FROM posts p LEFT JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC LIMIT :lim OFFSET :off');
$stmt->bindValue(':lim',$perPage,PDO::PARAM_INT);
$stmt->bindValue(':off',$offset,PDO::PARAM_INT);
$stmt->execute();
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total = $db->query('SELECT COUNT(*) FROM posts')->fetchColumn();
$pages = ceil($total/$perPage);
include 'header.php';
?>
<h2>Posts</h2>
<?php $lastDate=null; foreach($posts as $post): ?>
<?php $d = date('Y-m-d', strtotime($post['created_at'])); if($d!==$lastDate){ echo '<h3>'.htmlspecialchars($d).'</h3>'; $lastDate=$d; } ?>
<div class="post">
    <h3><a href="<?php echo BASE_PATH; ?>post/<?php echo htmlspecialchars($post['slug']); ?>"><?php echo htmlspecialchars($post['title']); ?></a></h3>
<small><?php echo $post['created_at']; ?> by <?php echo htmlspecialchars($post['username']); ?></small>
    <p>
        <?php if(!empty($post['excerpt'])){
            echo nl2br(htmlspecialchars($post['excerpt']));
        }else{
            echo nl2br(snippet_text($post['content']));
        } ?>
    </p>
    <img src="<?php echo BASE_PATH . ($post['image_path'] ?: 'content/default-featured-image.webp'); ?>" style="max-width:200px">
</div>
<?php endforeach; ?>
<p>
<?php if($page>1): ?><a href="<?php echo BASE_PATH; ?>?page=<?php echo $page-1; ?>">Previous</a><?php endif; ?>
<?php if($page<$pages): ?> <a href="<?php echo BASE_PATH; ?>?page=<?php echo $page+1; ?>">Next</a><?php endif; ?>
</p>
<?php include 'footer.php'; ?>
