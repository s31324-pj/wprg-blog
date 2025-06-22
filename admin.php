<?php
require_once 'auth.php';
require_role('author');
$db = get_db();
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
    $image_path = trim($_POST['image_path'] ?? '');
    if($image_path === ''){
        $image_path = 'content/default-featured-image.webp';
    }
    $stmt = $db->prepare('INSERT INTO posts (title,slug,content,excerpt,image_path,user_id) VALUES (?,?,?,?,?,?)');
    $stmt->execute([$title,$slug,$content,$excerpt,$image_path,current_user()['id']]);
    $postId = $db->lastInsertId();
    $userId = current_user()['id'];
    $db->prepare('INSERT INTO logs (message) VALUES (?)')->execute(["Post $postId created by user $userId"]);
}
$posts = $db->query('SELECT p.*, u.username FROM posts p LEFT JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
include 'header.php';
include 'dashboard_nav.php';
?>
<h2>Admin Panel</h2>
<form method="post">
    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
    <input type="text" name="title" placeholder="Title" required><br>
    <input type="text" name="slug" placeholder="Slug (optional)"><br>
    <textarea id="content-editor" name="content" placeholder="Content"></textarea><br>
    <textarea name="excerpt" placeholder="Excerpt (optional)"></textarea><br>
    <input type="hidden" name="image_path" id="image-path">
    <button type="button" onclick="window.open('media.php?popup=1&field=image-path', 'media', 'width=800,height=600');">Choose image</button>
    <img id="image-path-preview" style="max-width:100px"><br>
    <button type="submit">Add Post</button>
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
<h3>Existing Posts</h3>
<ul>
<?php foreach($posts as $p): ?>
<li>
    <?php echo htmlspecialchars($p['title']); ?> (<?php echo htmlspecialchars($p['username']); ?>) - <a href="<?php echo BASE_PATH; ?>edit?id=<?php echo $p['id']; ?>">Edit</a> |
    <form action="<?php echo BASE_PATH; ?>delete" method="post" style="display:inline">
        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
        <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
        <button type="submit" onclick="return confirm('delete?')">Delete</button>
    </form>
</li>
<?php endforeach; ?>
</ul>
<?php include 'footer.php'; ?>
