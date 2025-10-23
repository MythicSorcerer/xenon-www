<?php
// Safe database update script that checks for existing columns
$db = new SQLite3('db.sqlite');

echo "Checking database schema...<br>";

// Function to check if a column exists in a table
function columnExists($db, $table, $column) {
    $result = $db->query("PRAGMA table_info($table)");
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        if ($row['name'] === $column) {
            return true;
        }
    }
    return false;
}

// Function to check if a table exists
function tableExists($db, $table) {
    $result = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='$table'");
    return $result->fetchArray() !== false;
}

// Add IP address column to threads table if it doesn't exist
if (tableExists($db, 'threads')) {
    if (!columnExists($db, 'threads', 'ip_address')) {
        $db->exec('ALTER TABLE threads ADD COLUMN ip_address TEXT');
        echo "Added ip_address column to threads table.<br>";
    } else {
        echo "ip_address column already exists in threads table.<br>";
    }
} else {
    echo "threads table does not exist. Please run reset_db.php first.<br>";
}

// Add IP address column to posts table if it doesn't exist
if (tableExists($db, 'posts')) {
    if (!columnExists($db, 'posts', 'ip_address')) {
        $db->exec('ALTER TABLE posts ADD COLUMN ip_address TEXT');
        echo "Added ip_address column to posts table.<br>";
    } else {
        echo "ip_address column already exists in posts table.<br>";
    }
} else {
    echo "posts table does not exist. Please run reset_db.php first.<br>";
}

// Create notifications table if it doesn't exist
if (!tableExists($db, 'notifications')) {
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
    echo "Created notifications table.<br>";
} else {
    echo "notifications table already exists.<br>";
}

echo "Database update completed successfully!<br>";
$db->close();
?>