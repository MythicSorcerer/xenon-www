<?php
// Database update script to add IP addresses and notifications
$db = new SQLite3('db.sqlite');

// Add IP address column to threads table
$db->exec('ALTER TABLE threads ADD COLUMN ip_address TEXT');

// Add IP address column to posts table  
$db->exec('ALTER TABLE posts ADD COLUMN ip_address TEXT');

// Create notifications table
$db->exec('CREATE TABLE IF NOT EXISTS notifications (
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

echo "Database updated successfully with IP tracking and notifications!";
$db->close();
?>