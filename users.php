<?php
require_once 'auth.php';
require_role('admin');

$db = get_db();

function remove_dir_recursive(string $dir): void {
    if(!is_dir($dir)){
        return;
    }
    foreach(scandir($dir) as $item){
        if($item === '.' || $item === '..'){
            continue;
        }
        $path = $dir . '/' . $item;
        if(is_dir($path)){
            remove_dir_recursive($path);
        }else{
            @unlink($path);
        }
    }
    @rmdir($dir);
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    if(!verify_csrf_token($_POST['csrf_token'] ?? '')){
        die('Invalid CSRF token');
    }
}

// Create new user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'user';
    if ($username && $password && in_array($role, ['user','author','admin'])) {
        $stmt = $db->prepare('INSERT INTO users (username, password, role) VALUES (?,?,?)');
        try {
            $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT), $role]);
            $message = 'User created';
        } catch (Exception $e) {
            $error = 'Error creating user';
        }
    } else {
        $error = 'Invalid data';
    }
}

// Update role
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {
    $id = (int)($_POST['id'] ?? 0);
    $role = $_POST['role'] ?? '';
    if ($id && in_array($role, ['user','author','admin'])) {
        $stmt = $db->prepare('UPDATE users SET role=? WHERE id=?');
        $stmt->execute([$role, $id]);
    }
}

// Update password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $id = (int)($_POST['id'] ?? 0);
    $password = $_POST['password_new'] ?? '';
    if ($id && $password !== '') {
        $stmt = $db->prepare('UPDATE users SET password=? WHERE id=?');
        try {
            $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
            $message = 'Password updated';
        } catch (Exception $e) {
            $error = 'Error updating password';
        }
    } else {
        $error = 'Invalid data for password update';
    }
}

// Remove profile picture
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_picture'])) {
    $id = (int)($_POST['id'] ?? 0);
    if ($id) {
        $stmt = $db->prepare('SELECT username FROM users WHERE id=?');
        $stmt->execute([$id]);
        $username = $stmt->fetchColumn();
        $stmt = $db->prepare('UPDATE users SET profile_picture=NULL WHERE id=?');
        $stmt->execute([$id]);
        if ($username !== false) {
            $dir = 'uploads/profile-pictures/' . $username;
            remove_dir_recursive($dir);
        }
        if (isset($_SESSION['user']) && $_SESSION['user']['id'] == $id) {
            $_SESSION['user']['profile_picture'] = null;
        }
        $message = 'Profile picture removed';
    }
}

// Delete user
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id) {
        $stmt = $db->prepare('SELECT username FROM users WHERE id=?');
        $stmt->execute([$id]);
        $username = $stmt->fetchColumn();
        $stmt = $db->prepare('DELETE FROM users WHERE id=?');
        $stmt->execute([$id]);
        if($username !== false){
            $dir = 'uploads/profile-pictures/' . $username;
            remove_dir_recursive($dir);
        }
        header('Location: ' . BASE_PATH . 'users');
        exit;
    }
}

$users = $db->query('SELECT id, username, role, profile_picture FROM users ORDER BY username')->fetchAll(PDO::FETCH_ASSOC);

include 'header.php';
include 'dashboard_nav.php';
?>





<section class="comments-page">
    <div class="content">
        <h2>Manage Users</h2>
        <?php if(isset($error)) echo '<p style="color:red">'.htmlspecialchars($error).'</p>'; ?>
        <?php if(isset($message)) echo '<p>'.htmlspecialchars($message).'</p>'; ?>
        <h3>Add User</h3>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="create" value="1">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <select name="role">
                <option value="user">User</option>
                <option value="author">Author</option>
                <option value="admin">Admin</option>
            </select>
            <button type="submit">Create</button>
        </form>
        <h3>Existing Users</h3>
        <table>
            <tr>
                <th>Username</th>
                <th>Picture</th>
                <th>Role</th>
                <th>New Password</th>
                <th>Actions</th>
            </tr>
            <?php foreach($users as $u): ?>
            <tr>
                <td><?php echo htmlspecialchars($u['username']); ?></td>
                <td>
                    <img src="<?php echo BASE_PATH . (!empty($u['profile_picture']) ? $u['profile_picture'] : 'content/default-pfp.webp'); ?>" class="avatar" alt="Profile">
                    <?php if(!empty($u['profile_picture'])): ?>
                        <form method="post" style="display:inline">
                            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                            <input type="hidden" name="remove_picture" value="1">
                            <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                            <button type="submit">Remove picture</button>
                        </form>
                    <?php endif; ?>
                </td>
                <td>
                    <form method="post" style="display:inline">
                        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                        <input type="hidden" name="update_role" value="1">
                        <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                        <select name="role">
                            <option value="user" <?php if($u['role']==='user') echo 'selected'; ?>>User</option>
                            <option value="author" <?php if($u['role']==='author') echo 'selected'; ?>>Author</option>
                            <option value="admin" <?php if($u['role']==='admin') echo 'selected'; ?>>Admin</option>
                        </select>
                        <button type="submit">Change</button>
                    </form>
                </td>
                <td>
                    <form method="post" style="display:inline">
                        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                        <input type="hidden" name="update_password" value="1">
                        <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                        <input type="password" name="password_new" placeholder="New password">
                        <button type="submit">Set</button>
                    </form>
                </td>
                <td>
                    <?php if($u['id'] != current_user()['id']): ?>
                        <a href="<?php echo BASE_PATH; ?>users?delete=<?php echo $u['id']; ?>" onclick="return confirm('delete?')">Delete</a>
                    <?php else: ?>-
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</section>




<?php include 'footer.php'; ?>
