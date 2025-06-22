<?php
require_once 'auth.php';
require_role('author');
$db = get_db();
$id = (int)($_GET['id'] ?? 0);
$post = $db->prepare('SELECT p.*, u.username FROM posts p LEFT JOIN users u ON p.user_id = u.id WHERE p.id=?');
$post->execute([$id]);
$post = $post->fetch(PDO::FETCH_ASSOC);
if(!$post){ die('Not found'); }
if(current_user()['role'] !== 'admin' && current_user()['id'] !== $post['user_id']){
    die('Access denied');
}
if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!verify_csrf_token($_POST['csrf_token'] ?? '')){
        die('Invalid CSRF token');
    }
    $title = $_POST['title'];
    $slug = trim($_POST['slug'] ?? '');
    $content = $_POST['content'];
    $excerpt = $_POST['excerpt'] ?? '';
    if($slug === ''){
        $slug = create_slug($title);
    } else {
        $slug = create_slug($slug);
    }
    $image_path = trim($_POST['image_path'] ?? $post['image_path']);
    if($image_path === ''){
        $image_path = 'content/default-featured-image.webp';
    }
    $stmt = $db->prepare('UPDATE posts SET title=?, slug=?, content=?, excerpt=?, image_path=? WHERE id=?');
    $stmt->execute([$title,$slug,$content,$excerpt,$image_path,$id]);
    $userId = current_user()['id'];
    $db->prepare('INSERT INTO logs (message) VALUES (?)')->execute(["Post $id updated by user $userId"]);
    header('Location: ' . BASE_PATH . 'admin');
    exit;
}
include 'header.php';
include 'dashboard_nav.php';
?>




<section class="dashboard-page post-editor">
    <div class="content">
        <h2>Edit Post</h2>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="text" name="title" value="<?php echo htmlspecialchars($post['title']); ?>" required>
            <input type="text" name="slug" value="<?php echo htmlspecialchars($post['slug']); ?>" placeholder="Slug (optional)">
            <textarea id="content-editor" name="content"><?php echo htmlspecialchars($post['content']); ?></textarea>
            <textarea name="excerpt" placeholder="Excerpt (optional)"><?php echo htmlspecialchars($post['excerpt']); ?></textarea>
            <input type="hidden" name="image_path" id="image-path" value="<?php echo htmlspecialchars($post['image_path']); ?>">
            <button type="button" onclick="window.open('media.php?popup=1&field=image-path', 'media', 'width=800,height=600');">Choose image</button>
            <img id="image-path-preview">
            <button type="submit">Save</button>
        </form>
        <script>
            tinymce.init({ selector: '#content-editor' });
            document.getElementById('content-editor').form.addEventListener('submit', function(){
                tinymce.triggerSave();
            });
            function updatePreview(id){
                var inp = document.getElementById(id);
                var prev = document.getElementById(id+'-preview');
                if(prev){ prev.src = inp.value ? '<?php echo BASE_PATH; ?>' + inp.value : ''; }
            }
            document.getElementById('image-path').addEventListener('change', function(){ updatePreview('image-path'); });
            updatePreview('image-path');
        </script>
        
    </div>
</section





<?php include 'footer.php'; ?>
