<?php
// Complete database reset and initialization script
$db_file = 'db.sqlite';

// Remove existing database if it exists
if (file_exists($db_file)) {
    unlink($db_file);
    echo "Existing database removed.<br>";
}

$db = new SQLite3($db_file);

// Create users table
$db->exec('CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    password TEXT NOT NULL,
    email TEXT UNIQUE NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)');

// Create threads table with all columns including IP address
$db->exec('CREATE TABLE threads (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    user_id INTEGER,
    username TEXT DEFAULT "Anonymous",
    ip_address TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)');

// Create posts table with all columns including IP address
$db->exec('CREATE TABLE posts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    thread_id INTEGER NOT NULL,
    content TEXT NOT NULL,
    user_id INTEGER,
    username TEXT DEFAULT "Anonymous",
    ip_address TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (thread_id) REFERENCES threads(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
)');

// Create notifications table
$db->exec('CREATE TABLE notifications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    thread_id INTEGER NOT NULL,
    post_id INTEGER NOT NULL,
    type TEXT NOT NULL DEFAULT "reply",
    message TEXT NOT NULL,
    is_read INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (thread_id) REFERENCES threads(id),
    FOREIGN KEY (post_id) REFERENCES posts(id)
)');

echo "Database reset and initialized successfully with all tables and columns!<br>";
echo "Tables created: users, threads, posts, notifications<br>";
echo "All tables include proper IP address tracking and notification support.<br>";

$db->close();
?>