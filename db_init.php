<?php
// Database initialization and connection helper
// This file ensures the database and all required tables exist

function initializeDatabase($db_path) {
    try {
        // Create database connection (SQLite will create file if it doesn't exist)
        $db = new SQLite3($db_path);
        
        // Enable foreign keys
        $db->exec('PRAGMA foreign_keys = ON');
        
        // Check if database is initialized by looking for users table
        $result = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
        $users_table_exists = $result->fetchArray() !== false;
        
        if (!$users_table_exists) {
            // Database is new or empty, initialize all tables
            initializeTables($db);
        } else {
            // Database exists, check for missing columns/tables
            updateDatabaseSchema($db);
        }
        
        return $db;
    } catch (Exception $e) {
        die("Database initialization failed: " . $e->getMessage());
    }
}

function initializeTables($db) {
    // Create users table
    $db->exec('CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        email TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        is_admin INTEGER DEFAULT 0,
        last_post_time DATETIME
    )');
    
    // Create threads table
    $db->exec('CREATE TABLE IF NOT EXISTS threads (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        user_id INTEGER,
        username TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        ip_address TEXT,
        is_deleted INTEGER DEFAULT 0,
        FOREIGN KEY (user_id) REFERENCES users (id)
    )');
    
    // Create posts table
    $db->exec('CREATE TABLE IF NOT EXISTS posts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        thread_id INTEGER NOT NULL,
        content TEXT NOT NULL,
        user_id INTEGER,
        username TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        ip_address TEXT,
        is_deleted INTEGER DEFAULT 0,
        FOREIGN KEY (thread_id) REFERENCES threads (id),
        FOREIGN KEY (user_id) REFERENCES users (id)
    )');
    
    // Create notifications table
    $db->exec('CREATE TABLE IF NOT EXISTS notifications (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        thread_id INTEGER NOT NULL,
        post_id INTEGER,
        message TEXT NOT NULL,
        is_read INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users (id),
        FOREIGN KEY (thread_id) REFERENCES threads (id),
        FOREIGN KEY (post_id) REFERENCES posts (id)
    )');
    
    // Create IP cooldowns table
    $db->exec('CREATE TABLE IF NOT EXISTS ip_cooldowns (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        ip_address TEXT NOT NULL UNIQUE,
        last_post_time DATETIME NOT NULL
    )');
}

function updateDatabaseSchema($db) {
    // Check and add missing columns to existing tables
    
    // Check users table for missing columns
    $users_columns = getTableColumns($db, 'users');
    if (!in_array('is_admin', $users_columns)) {
        $db->exec('ALTER TABLE users ADD COLUMN is_admin INTEGER DEFAULT 0');
    }
    if (!in_array('last_post_time', $users_columns)) {
        $db->exec('ALTER TABLE users ADD COLUMN last_post_time DATETIME');
    }
    
    // Check threads table for missing columns
    $threads_columns = getTableColumns($db, 'threads');
    if (!in_array('ip_address', $threads_columns)) {
        $db->exec('ALTER TABLE threads ADD COLUMN ip_address TEXT');
    }
    if (!in_array('is_deleted', $threads_columns)) {
        $db->exec('ALTER TABLE threads ADD COLUMN is_deleted INTEGER DEFAULT 0');
    }
    
    // Check posts table for missing columns
    $posts_columns = getTableColumns($db, 'posts');
    if (!in_array('ip_address', $posts_columns)) {
        $db->exec('ALTER TABLE posts ADD COLUMN ip_address TEXT');
    }
    if (!in_array('is_deleted', $posts_columns)) {
        $db->exec('ALTER TABLE posts ADD COLUMN is_deleted INTEGER DEFAULT 0');
    }
    
    // Check if notifications table exists
    $result = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='notifications'");
    if ($result->fetchArray() === false) {
        $db->exec('CREATE TABLE notifications (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            thread_id INTEGER NOT NULL,
            post_id INTEGER,
            message TEXT NOT NULL,
            is_read INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users (id),
            FOREIGN KEY (thread_id) REFERENCES threads (id),
            FOREIGN KEY (post_id) REFERENCES posts (id)
        )');
    }
    
    // Check if ip_cooldowns table exists
    $result = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='ip_cooldowns'");
    if ($result->fetchArray() === false) {
        $db->exec('CREATE TABLE ip_cooldowns (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ip_address TEXT NOT NULL UNIQUE,
            last_post_time DATETIME NOT NULL
        )');
    }
}

function getTableColumns($db, $table_name) {
    $columns = [];
    $result = $db->query("PRAGMA table_info($table_name)");
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $columns[] = $row['name'];
    }
    return $columns;
}

// Helper function to get database connection
function getDatabaseConnection() {
    $db_path = __DIR__ . '/db.sqlite';
    return initializeDatabase($db_path);
}
?>