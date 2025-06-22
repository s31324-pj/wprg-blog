<?php
require_once 'config.php';
require_once 'auth.php';
$db = get_db();
$slug = $_GET['slug'] ?? '';
$post = $db->prepare('SELECT p.*, u.username FROM posts p LEFT JOIN users u ON p.user_id = u.id WHERE p.slug=?');
$post->execute([$slug]);
$post = $post->fetch(PDO::FETCH_ASSOC);
if(!$post){ die('Post not found'); }
$id = $post['id'];

// prev and next
$prev = $db->prepare('SELECT slug FROM posts WHERE id < ? ORDER BY id DESC LIMIT 1');
$prev->execute([$id]);
$prev = $prev->fetchColumn();
$next = $db->prepare('SELECT slug FROM posts WHERE id > ? ORDER BY id ASC LIMIT 1');
$next->execute([$id]);
$next = $next->fetchColumn();

// comments with pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page-1)*$perPage;
$comments = $db->prepare('SELECT c.*, u.username AS user_username, u.profile_picture FROM comments c LEFT JOIN users u ON c.user_id = u.id WHERE c.post_id=:post_id ORDER BY c.created_at LIMIT :lim OFFSET :off');
$comments->bindValue(':post_id', $id, PDO::PARAM_INT);
$comments->bindValue(':lim', $perPage, PDO::PARAM_INT);
$comments->bindValue(':off', $offset, PDO::PARAM_INT);
$comments->execute();
$comments = $comments->fetchAll(PDO::FETCH_ASSOC);
$totalComments = $db->prepare('SELECT COUNT(*) FROM comments WHERE post_id=?');
$totalComments->execute([$id]);
$commentPages = ceil($totalComments->fetchColumn()/$perPage);

if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!verify_csrf_token($_POST['csrf_token'] ?? '')){
        die('Invalid CSRF token');
    }
    $author = current_user()['username'] ?? 'Guest';
    $user_id = current_user()['id'] ?? null;
    $stmt = $db->prepare('INSERT INTO comments (post_id,user_id,author,content) VALUES (?,?,?,?)');
    $stmt->execute([$id,$user_id,$author,$_POST['content']]);
    $db->prepare('INSERT INTO logs (message) VALUES (?)')->execute(["Comment added by $author on post $id"]);
    header('Location: ' . BASE_PATH . "post/$slug?page=$page");
    exit;
}

include 'header.php';
?>





<article class="section post-page">
    <div class="content">
        <h2><?php echo htmlspecialchars($post['title']); ?></h2>
        <small><?php echo $post['created_at']; ?> by <?php echo htmlspecialchars($post['username']); ?></small>
        <img src="<?php echo BASE_PATH . ($post['image_path'] ?: 'content/default-featured-image.webp'); ?>">
        <div class="post-content"><?php echo sanitize_html($post['content']); ?></div>
        <div class="post-pagination pagination">
            <?php if($prev): ?>
                <a href="<?php echo BASE_PATH; ?>post/<?php echo $prev; ?>">Previous post</a>
            <?php else: ?>
                <div></div>
            <?php endif; ?>
            <?php if($next): ?>
                <a href="<?php echo BASE_PATH; ?>post/<?php echo $next; ?>">Next post</a>
            <?php else: ?>
                <div></div>
            <?php endif; ?>
        </div>
    </div>
</article>

<section class="post-page comments">
    <div class="content">
        <h3>Comments</h3>
        <?php foreach($comments as $c): ?>
        <div class="comment">
            <img src="<?php echo BASE_PATH . (!empty($c['profile_picture']) ? $c['profile_picture'] : 'content/default-pfp.webp'); ?>" class="avatar" alt="Profile">
            <div class="comment-text">
                <div class="comment-info">
                    <strong><?php echo htmlspecialchars($c['author']); ?></strong> (<?php echo $c['created_at']; ?>)
                    <?php if(isset($_SESSION['user']) && $_SESSION['user']['role']==='admin'): ?>
                        <a href="<?php echo BASE_PATH; ?>delete_comment.php?id=<?php echo $c['id']; ?>" onclick="return confirm('delete?')" class="delete-comment">Delete</a>
                    <?php endif; ?>
                </div>
                <p><?php echo nl2br(htmlspecialchars($c['content'])); ?></p>
            </div>
        </div>
        <?php endforeach; ?>
        <div class="comment-pagination pagination">
            <?php if($page>1): ?>
                <a href="<?php echo BASE_PATH; ?>post/<?php echo $slug; ?>?page=<?php echo $page-1; ?>">Previous comments</a>
            <?php else: ?>
                <div></div> 
            <?php endif; ?>
            <?php if($page<$commentPages): ?>
                <a href="<?php echo BASE_PATH; ?>post/<?php echo $slug; ?>?page=<?php echo $page+1; ?>">Next comments</a>
            <?php else: ?>
                <div></div> 
            <?php endif; ?>
            </div>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <label for="content"><h3>Add a comment</h3></label>
            <textarea name="content" required></textarea>
            <button type="submit">Add comment</button>
        </form>
    </div>
</section>



<?php include 'footer.php'; ?>
