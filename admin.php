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




<section class="dashboard-page heading">
    <div class="content">
        <h2>Manage Posts</h2>
    </div>
</section>
<section class="dashboard-page post-editor">
    <div class="content">
        <h3>Add New Post</h3>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="text" name="title" placeholder="Title" required>
            <input type="text" name="slug" placeholder="Slug (optional)">
            <textarea id="content-editor" name="content" placeholder="Content"></textarea>
            <textarea name="excerpt" placeholder="Excerpt (optional)"></textarea>
            <input type="hidden" name="image_path" id="image-path">
            <button type="button" onclick="window.open('media.php?popup=1&field=image-path', 'media', 'width=800,height=600');">Choose featured image</button>
            <img id="image-path-preview" src="<?php echo BASE_PATH; ?>content/default-featured-image.webp">
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
            if ('' == document.getElementById('image-path').value) {
                document.getElementById('image-path').value = 'content/default-featured-image.webp';
            }
            updatePreview('image-path');
        </script>
        
    </div>
</section>

<section class="dashboard-page posts">
    <div class="content">
        <h3>Existing Posts</h3>
        <ul>
        <?php foreach($posts as $p): ?>
        <li>
            <span><?php echo htmlspecialchars($p['title']); ?> (<?php echo htmlspecialchars($p['username']); ?>)</span><a href="<?php echo BASE_PATH; ?>edit?id=<?php echo $p['id']; ?>" class="edit-button">Edit</a>
            <form action="<?php echo BASE_PATH; ?>delete" method="post" style="display:inline">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                <button type="submit" onclick="return confirm('delete?')" class="delete-button">Delete</button>
            </form>
        </li>
        <?php endforeach; ?>
        </ul>
    </div>
</section>


<?php include 'footer.php'; ?>
