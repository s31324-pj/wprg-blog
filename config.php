<?php
// Database config using MySQL
define('DB_HOST', '127.0.0.1');
define('DB_PORT', 3306);
define('DB_NAME', 'blog');
define('DB_USER', 'root');
define('DB_PASS', '');

// Maximum upload file size (2 MB)
define('MAX_IMAGE_SIZE', 2 * 1024 * 1024);

// Base path of the application relative to the web root
// Adjust this when deploying on a different server
if(!defined('BASE_PATH')){
    define('BASE_PATH', '/wprg/projekt-wprg/');
}

function column_exists(PDO $pdo, string $table, string $column): bool {
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?"
    );
    $stmt->execute([DB_NAME, $table, $column]);
    return $stmt->fetchColumn() > 0;
}

function table_exists(PDO $pdo, string $table): bool {
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?"
    );
    $stmt->execute([DB_NAME, $table]);
    return $stmt->fetchColumn() > 0;
}

function database_initialized(PDO $pdo): bool {
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = ?"
    );
    $stmt->execute([DB_NAME]);
    return $stmt->fetchColumn() > 0;
}

function ensure_table(PDO $pdo, string $name, string $schema): void {
    if (!table_exists($pdo, $name)) {
        $pdo->exec($schema);
    }
}

function users_exist(PDO $pdo): bool {
    try {
        $stmt = $pdo->query('SELECT COUNT(*) FROM users');
        return $stmt->fetchColumn() > 0;
    } catch (Exception $e) {
        return false;
    }
}

function ensure_column(PDO $pdo, string $table, string $definition): void {
    $column = preg_split('/\s+/', trim($definition))[0];
    if (!column_exists($pdo, $table, $column)) {
        $pdo->exec("ALTER TABLE `$table` ADD COLUMN $definition");
    }
}

function initialize_db(PDO $pdo) {
    // Create database and essential tables if they do not exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    $pdo->exec("USE `" . DB_NAME . "`");

    ensure_table($pdo, 'users', "CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) UNIQUE,
        password VARCHAR(255),
        email VARCHAR(255),
        is_verified TINYINT DEFAULT 0,
        verification_code VARCHAR(64),
        role VARCHAR(50),
        reset_token VARCHAR(64),
        reset_expires DATETIME
    ) ENGINE=InnoDB");

    ensure_table($pdo, 'posts', "CREATE TABLE posts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255),
        slug VARCHAR(255) UNIQUE,
        content TEXT,
        excerpt TEXT,
        image_path VARCHAR(255),
        user_id INT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(user_id) REFERENCES users(id)
    ) ENGINE=InnoDB");

    ensure_table($pdo, 'comments', "CREATE TABLE comments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        post_id INT,
        user_id INT,
        author VARCHAR(100),
        content TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(post_id) REFERENCES posts(id),
        FOREIGN KEY(user_id) REFERENCES users(id)
    ) ENGINE=InnoDB");

    ensure_table($pdo, 'logs', "CREATE TABLE logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        message TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    ensure_table($pdo, 'contacts', "CREATE TABLE contacts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255),
        subject VARCHAR(255),
        message TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    // Ensure columns exist in case tables were created by an older version
    ensure_column($pdo, 'posts', 'user_id INT');
    ensure_column($pdo, 'posts', 'image_path VARCHAR(255)');
    ensure_column($pdo, 'posts', 'excerpt TEXT');
    ensure_column($pdo, 'posts', 'slug VARCHAR(255) UNIQUE');
    ensure_column($pdo, 'comments', 'user_id INT');
    ensure_column($pdo, 'users', 'role VARCHAR(50)');
    ensure_column($pdo, 'users', 'email VARCHAR(255)');
    ensure_column($pdo, 'users', 'is_verified TINYINT DEFAULT 0');
    ensure_column($pdo, 'users', 'verification_code VARCHAR(64)');
    ensure_column($pdo, 'users', 'profile_picture VARCHAR(255)');
    ensure_column($pdo, 'users', 'api_key VARCHAR(64)');
    ensure_column($pdo, 'users', 'reset_token VARCHAR(64)');
    ensure_column($pdo, 'users', 'reset_expires DATETIME');

}

function get_db() {
    static $db = null;
    if ($db === null) {
        // Connect without specifying the database first to allow creation
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4";
        $db = new PDO($dsn, DB_USER, DB_PASS);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Always initialize database structure
        initialize_db($db);

        // Ensure connection uses the correct database
        $db->exec("USE `" . DB_NAME . "`");

        // Redirect to setup when no user accounts exist
        if (!users_exist($db)) {
            $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
            if (PHP_SAPI !== 'cli' && $script !== 'install.php') {
                header('Location: ' . BASE_PATH . 'install');
                exit;
            }
        }
    }
    return $db;
}

session_start();

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Sanitize post HTML allowing a limited set of tags
function sanitize_html(string $html): string {
    $allowed = '<p><br><b><i><em><strong><a><ul><ol><li><h1><h2><h3><h4><h5><h6><blockquote><code><pre>';
    return strip_tags($html, $allowed);
}

# Generate a URL-friendly slug from text
function create_slug(string $text): string {
    $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text);
    $text = preg_replace('~[^\\pL\d\s]+~u', '', $text);
    $text = preg_replace('~\s+~', '-', $text);
    $text = trim($text, '-');
    return strtolower($text);
}

// Create a shortened plain-text snippet of post content
function snippet_text(string $html, int $length = 200): string {
    $text = strip_tags($html);
    if(mb_strlen($text) > $length){
        $text = mb_substr($text, 0, $length) . '...';
    }
    return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>

