<?php
// Database connection function
function getDatabaseConnection() {
    $db = new SQLite3('db.sqlite');
    
    // Create users table
    $db->exec('CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        email TEXT UNIQUE NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        font_preference TEXT DEFAULT "orbitron",
        theme_preference TEXT DEFAULT "dark"
    )');
    
    // Create threads table
    $db->exec('CREATE TABLE IF NOT EXISTS threads (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        user_id INTEGER,
        username TEXT DEFAULT "Anonymous",
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )');
    
    // Create posts table
    $db->exec('CREATE TABLE IF NOT EXISTS posts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        thread_id INTEGER NOT NULL,
        content TEXT NOT NULL,
        user_id INTEGER,
        username TEXT DEFAULT "Anonymous",
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (thread_id) REFERENCES threads(id),
        FOREIGN KEY (user_id) REFERENCES users(id)
    )');
    
    // Create notifications table
    $db->exec('CREATE TABLE IF NOT EXISTS notifications (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        message TEXT NOT NULL,
        is_read INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )');
    
    return $db;  // Return the connection, don't close it!
}
?>
