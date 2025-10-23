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
        is_deleted INTEGER DEFAULT 0
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
        is_deleted INTEGER DEFAULT 0
    )');
    
    // Create notifications table
    $db->exec('CREATE TABLE IF NOT EXISTS notifications (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        thread_id INTEGER NOT NULL,
        post_id INTEGER,
        message TEXT NOT NULL,
        is_read INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )');
    
    // Create IP cooldowns table
    $db->exec('CREATE TABLE IF NOT EXISTS ip_cooldowns (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        ip_address TEXT NOT NULL UNIQUE,
        last_post_time DATETIME NOT NULL
    )');
    
    // Create tags table
    $db->exec('CREATE TABLE IF NOT EXISTS tags (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL UNIQUE,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )');
    
    // Create thread_tags table (many-to-many relationship)
    $db->exec('CREATE TABLE IF NOT EXISTS thread_tags (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        thread_id INTEGER NOT NULL,
        tag_id INTEGER NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(thread_id, tag_id)
    )');
    
    // Create post_tags table (many-to-many relationship)
    $db->exec('CREATE TABLE IF NOT EXISTS post_tags (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        post_id INTEGER NOT NULL,
        tag_id INTEGER NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(post_id, tag_id)
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
    if (!in_array('font_preference', $users_columns)) {
        $db->exec('ALTER TABLE users ADD COLUMN font_preference TEXT DEFAULT "orbitron"');
    }
    if (!in_array('theme_preference', $users_columns)) {
        $db->exec('ALTER TABLE users ADD COLUMN theme_preference TEXT DEFAULT "dark"');
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
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
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
    
    // Check if tags table exists
    $result = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='tags'");
    if ($result->fetchArray() === false) {
        $db->exec('CREATE TABLE tags (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )');
    }
    
    // Check if thread_tags table exists
    $result = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='thread_tags'");
    if ($result->fetchArray() === false) {
        $db->exec('CREATE TABLE thread_tags (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            thread_id INTEGER NOT NULL,
            tag_id INTEGER NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(thread_id, tag_id)
        )');
    }
    
    // Check if post_tags table exists
    $result = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='post_tags'");
    if ($result->fetchArray() === false) {
        $db->exec('CREATE TABLE post_tags (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            post_id INTEGER NOT NULL,
            tag_id INTEGER NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(post_id, tag_id)
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

// Helper functions for tag management
function getOrCreateTag($db, $tag_name) {
    $tag_name = trim(strtolower($tag_name));
    if (empty($tag_name)) return null;
    
    // Check if tag exists
    $stmt = $db->prepare('SELECT id FROM tags WHERE name = :name');
    $stmt->bindValue(':name', $tag_name, SQLITE3_TEXT);
    $result = $stmt->execute();
    $tag = $result->fetchArray(SQLITE3_ASSOC);
    
    if ($tag) {
        return $tag['id'];
    }
    
    // Create new tag
    $stmt = $db->prepare('INSERT INTO tags (name) VALUES (:name)');
    $stmt->bindValue(':name', $tag_name, SQLITE3_TEXT);
    $stmt->execute();
    
    return $db->lastInsertRowID();
}

function addTagsToThread($db, $thread_id, $tags_string) {
    if (empty($tags_string)) return;
    
    $tags = array_map('trim', explode(',', $tags_string));
    foreach ($tags as $tag_name) {
        if (!empty($tag_name)) {
            $tag_id = getOrCreateTag($db, $tag_name);
            if ($tag_id) {
                // Insert thread-tag relationship (ignore if already exists)
                $stmt = $db->prepare('INSERT OR IGNORE INTO thread_tags (thread_id, tag_id) VALUES (:thread_id, :tag_id)');
                $stmt->bindValue(':thread_id', $thread_id, SQLITE3_INTEGER);
                $stmt->bindValue(':tag_id', $tag_id, SQLITE3_INTEGER);
                $stmt->execute();
            }
        }
    }
}

function addTagsToPost($db, $post_id, $tags_string) {
    if (empty($tags_string)) return;
    
    $tags = array_map('trim', explode(',', $tags_string));
    foreach ($tags as $tag_name) {
        if (!empty($tag_name)) {
            $tag_id = getOrCreateTag($db, $tag_name);
            if ($tag_id) {
                // Insert post-tag relationship (ignore if already exists)
                $stmt = $db->prepare('INSERT OR IGNORE INTO post_tags (post_id, tag_id) VALUES (:post_id, :tag_id)');
                $stmt->bindValue(':post_id', $post_id, SQLITE3_INTEGER);
                $stmt->bindValue(':tag_id', $tag_id, SQLITE3_INTEGER);
                $stmt->execute();
            }
        }
    }
}

function getThreadTags($db, $thread_id) {
    $stmt = $db->prepare('
        SELECT t.name
        FROM tags t
        JOIN thread_tags tt ON t.id = tt.tag_id
        WHERE tt.thread_id = :thread_id
        ORDER BY t.name
    ');
    $stmt->bindValue(':thread_id', $thread_id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    
    $tags = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $tags[] = $row['name'];
    }
    return $tags;
}

function getPostTags($db, $post_id) {
    $stmt = $db->prepare('
        SELECT t.name
        FROM tags t
        JOIN post_tags pt ON t.id = pt.tag_id
        WHERE pt.post_id = :post_id
        ORDER BY t.name
    ');
    $stmt->bindValue(':post_id', $post_id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    
    $tags = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $tags[] = $row['name'];
    }
    return $tags;
}
?>