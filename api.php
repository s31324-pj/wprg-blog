<?php
require_once 'config.php';

header('Content-Type: application/json');
$db = get_db();

function get_api_user(PDO $db){
    $key = '';
    if(isset($_SERVER['HTTP_AUTHORIZATION']) && preg_match('/Bearer\s+(.*)/', $_SERVER['HTTP_AUTHORIZATION'], $m)){
        $key = trim($m[1]);
    } elseif(isset($_GET['api_key'])) {
        $key = $_GET['api_key'];
    }
    if($key !== ''){
        $stmt = $db->prepare('SELECT * FROM users WHERE api_key=?');
        $stmt->execute([$key]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if($user) return $user;
    }
    return ['role' => 'user', 'id' => null];
}

$user = get_api_user($db);
$method = $_SERVER['REQUEST_METHOD'];
$path = trim($_GET['path'] ?? '', '/');
$parts = $path === '' ? [] : explode('/', $path);
$resource = $parts[0] ?? '';
$id = isset($parts[1]) ? (int)$parts[1] : 0;

function respond($data, int $status=200){
    http_response_code($status);
    echo json_encode($data);
    exit;
}

switch($resource){
    case 'posts':
        if($method === 'GET'){
            if($id){
                $stmt = $db->prepare('SELECT * FROM posts WHERE id=?');
                $stmt->execute([$id]);
                $post = $stmt->fetch(PDO::FETCH_ASSOC);
                if(!$post) respond(['error'=>'Not found'],404);
                respond($post);
            } else {
                $posts = $db->query('SELECT * FROM posts ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
                respond($posts);
            }
        } elseif($method === 'POST'){
            if(!in_array($user['role'], ['author','admin'])) respond(['error'=>'Forbidden'],403);
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            $title = trim($input['title'] ?? '');
            $content = $input['content'] ?? '';
            if($title === '' || $content === '') respond(['error'=>'Invalid'],400);
            $slug = create_slug($input['slug'] ?? $title);
            $excerpt = $input['excerpt'] ?? '';
            $image = $input['image_path'] ?? 'content/default-featured-image.webp';
            $stmt = $db->prepare('INSERT INTO posts (title,slug,content,excerpt,image_path,user_id) VALUES (?,?,?,?,?,?)');
            $stmt->execute([$title,$slug,$content,$excerpt,$image,$user['id']]);
            respond(['id'=>$db->lastInsertId()],201);
        } elseif($method === 'PUT' && $id){
            if(!in_array($user['role'], ['author','admin'])) respond(['error'=>'Forbidden'],403);
            $stmt = $db->prepare('SELECT user_id FROM posts WHERE id=?');
            $stmt->execute([$id]);
            $owner = $stmt->fetchColumn();
            if(!$owner) respond(['error'=>'Not found'],404);
            if($user['role'] !== 'admin' && $user['id'] != $owner) respond(['error'=>'Forbidden'],403);
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            $title = trim($input['title'] ?? '');
            $content = $input['content'] ?? '';
            $slug = $input['slug'] ?? null;
            $excerpt = $input['excerpt'] ?? null;
            $image = $input['image_path'] ?? null;
            $fields = [];
            $values = [];
            if($title !== ''){ $fields[]='title=?'; $values[]=$title; }
            if($slug !== null){ $fields[]='slug=?'; $values[]=create_slug($slug); }
            if($content !== ''){ $fields[]='content=?'; $values[]=$content; }
            if($excerpt !== null){ $fields[]='excerpt=?'; $values[]=$excerpt; }
            if($image !== null){ $fields[]='image_path=?'; $values[]=$image; }
            if(!$fields) respond(['error'=>'No data'],400);
            $values[]=$id;
            $stmt = $db->prepare('UPDATE posts SET '.join(',', $fields).' WHERE id=?');
            $stmt->execute($values);
            respond(['status'=>'ok']);
        } elseif($method === 'DELETE' && $id){
            if(!in_array($user['role'], ['author','admin'])) respond(['error'=>'Forbidden'],403);
            $stmt = $db->prepare('SELECT user_id FROM posts WHERE id=?');
            $stmt->execute([$id]);
            $owner = $stmt->fetchColumn();
            if(!$owner) respond(['error'=>'Not found'],404);
            if($user['role'] !== 'admin' && $user['id'] != $owner) respond(['error'=>'Forbidden'],403);
            $stmt = $db->prepare('DELETE FROM posts WHERE id=?');
            $stmt->execute([$id]);
            respond(['status'=>'deleted']);
        }
        break;
    default:
        respond(['error'=>'Unknown endpoint'],404);
}
