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


<section class="home-page">
    <div class="content">
        <div id="post-grid">
            <?php $lastDate=null; foreach($posts as $post): ?>
           <a href="<?php echo BASE_PATH; ?>post/<?php echo htmlspecialchars($post['slug']); ?>" class="post-tile">
                <img src="<?php echo BASE_PATH . ($post['image_path'] ?: 'content/default-featured-image.webp'); ?>">
                <h3><?php echo htmlspecialchars($post['title']); ?></h3>
                <p>
                    <?php if(!empty($post['excerpt'])){
                        echo nl2br(htmlspecialchars($post['excerpt']));
                    }else{
                        echo nl2br(snippet_text($post['content']));
                    } ?>
                </p>
                <small class="post-info"><?php echo $post['created_at']; ?> by <?php echo htmlspecialchars($post['username']); ?></small>
                
            </a>
            <?php endforeach; ?>
            
        </div>
        <p>
            <?php if($page>1): ?><a href="<?php echo BASE_PATH; ?>?page=<?php echo $page-1; ?>">Previous</a><?php endif; ?>
            <?php if($page<$pages): ?> <a href="<?php echo BASE_PATH; ?>?page=<?php echo $page+1; ?>">Next</a><?php endif; ?>
        </p>
    </div>
</section>




<?php include 'footer.php'; ?>
