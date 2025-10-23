<?php
require_once 'db_init.php';

$db = getDatabaseConnection();

echo "=== THREADS ===\n";
$threads = $db->query('SELECT * FROM threads');
while ($thread = $threads->fetchArray(SQLITE3_ASSOC)) {
    print_r($thread);
}

echo "\n=== POSTS ===\n";
$posts = $db->query('SELECT * FROM posts');
while ($post = $posts->fetchArray(SQLITE3_ASSOC)) {
    print_r($post);
}

echo "\n=== USERS ===\n";
$users = $db->query('SELECT * FROM users');
while ($user = $users->fetchArray(SQLITE3_ASSOC)) {
    print_r($user);
}
?>