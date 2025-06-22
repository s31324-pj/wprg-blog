<?php
require_once 'auth.php';
require_role('author');

$field = $_GET['field'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 100;
$profile = isset($_GET['profile']);
$popup = isset($_GET['popup']);
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(!verify_csrf_token($_POST['csrf_token'] ?? '')){
        die('Invalid CSRF token');
    }
    if (!empty($_FILES['file']['name'])) {
        if ($_FILES['file']['size'] > MAX_IMAGE_SIZE) {
            $upload_error = 'Image exceeds 2 MB size limit';
        } else {
            if ($profile) {
                $dir = 'uploads/profile-pictures/' . $user['username'];
            } else {
                $year = date('Y');
                $month = date('m');
                $dir = "uploads/$year/$month";
            }
            if(!is_dir($dir)){
                mkdir($dir, 0777, true);
            }
            $target = $dir . '/' . basename($_FILES['file']['name']);
            $type = mime_content_type($_FILES['file']['tmp_name']);
            if(in_array($type, ['image/jpeg','image/png','image/gif','image/webp']) && move_uploaded_file($_FILES['file']['tmp_name'], $target)){
                // uploaded
            } else {
                $upload_error = 'Invalid image file';
            }
        }
    }
}

$baseDir = $profile ? 'uploads/profile-pictures/' . $user['username'] : 'uploads';

$files = [];
if (is_dir($baseDir)) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($baseDir, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($iterator as $file) {
        if(!$profile && strpos($file->getPathname(), 'uploads/profile-pictures') === 0){
            continue; // skip profile pictures for general library
        }

        // Skip hidden files and directories (starting with '.')
        if (strpos($file->getFilename(), '.') === 0) {
            continue;
        }

        if($file->isFile()){
            $files[$file->getPathname()] = $file->getMTime();
        }
    }
}
arsort($files);
$total = count($files);
$files = array_slice(array_keys($files), ($page-1)*$perPage, $perPage);

if(!$popup){
    include 'header.php';
    include 'dashboard_nav.php';
} else {
    if (session_status() === PHP_SESSION_NONE) session_start();
    echo '<!DOCTYPE html><html lang="pl"><head><meta charset="UTF-8">';
    echo '<link rel="stylesheet" href="' . BASE_PATH . 'css/style.css">';
    echo '</head><body><main>';
}
?>
<h2>Media Library</h2>
<?php if(isset($upload_error)) echo '<p style="color:red">'.htmlspecialchars($upload_error).'</p>'; ?>
<form method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
    <input type="file" name="file" required>
    <button type="submit">Upload</button>
</form>
<div class="media-grid">
<?php foreach($files as $f): ?>
    <div class="media-item">
        <img src="<?php echo BASE_PATH . $f; ?>" style="max-width:100px;cursor:pointer" onclick="select('<?php echo addslashes($f); ?>')">
        <div><?php echo htmlspecialchars(basename($f)); ?></div>
    </div>
<?php endforeach; ?>
</div>
<?php if($total > $page*$perPage): ?>
    <a href="?<?php echo http_build_query(['page'=>$page+1,'field'=>$field,'profile'=>$profile?1:null,'popup'=>$popup?1:null]); ?>">Load more</a>
<?php endif; ?>
<script>
function select(path){
    if(window.opener && '<?php echo $field; ?>'){
        var inp = window.opener.document.getElementById('<?php echo $field; ?>');
        if(inp){
            inp.value = path;
            var ev = new Event('change');
            inp.dispatchEvent(ev);
        }
        window.close();
    }
}
</script>
<?php if(!$popup){
    include 'footer.php';
} else {
    echo '</main></body></html>';
} ?>

