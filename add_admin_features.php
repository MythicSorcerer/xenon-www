<?php
// Database update script to add admin features and cooldown tracking
$db = new SQLite3('db.sqlite');

// Add admin role column to users table
try {
    $db->exec('ALTER TABLE users ADD COLUMN is_admin INTEGER DEFAULT 0');
    echo "Added is_admin column to users table.<br>";
} catch (Exception $e) {
    echo "is_admin column may already exist: " . $e->getMessage() . "<br>";
}

// Add last_post_time column to users table for cooldown tracking
try {
    $db->exec('ALTER TABLE users ADD COLUMN last_post_time DATETIME');
    echo "Added last_post_time column to users table.<br>";
} catch (Exception $e) {
    echo "last_post_time column may already exist: " . $e->getMessage() . "<br>";
}

// Add deleted flag to posts table
try {
    $db->exec('ALTER TABLE posts ADD COLUMN is_deleted INTEGER DEFAULT 0');
    echo "Added is_deleted column to posts table.<br>";
} catch (Exception $e) {
    echo "is_deleted column may already exist: " . $e->getMessage() . "<br>";
}

// Add deleted flag to threads table
try {
    $db->exec('ALTER TABLE threads ADD COLUMN is_deleted INTEGER DEFAULT 0');
    echo "Added is_deleted column to threads table.<br>";
} catch (Exception $e) {
    echo "is_deleted column may already exist: " . $e->getMessage() . "<br>";
}

// Set admin privileges for specified usernames
$admin_usernames = ['admin', 'modmaster'];

foreach ($admin_usernames as $username) {
    $stmt = $db->prepare('UPDATE users SET is_admin = 1 WHERE username = :username');
    $stmt->bindValue(':username', $username, SQLITE3_TEXT);
    $result = $stmt->execute();
    
    // Check if user exists
    $check_stmt = $db->prepare('SELECT id FROM users WHERE username = :username');
    $check_stmt->bindValue(':username', $username, SQLITE3_TEXT);
    $check_result = $check_stmt->execute();
    
    if ($check_result->fetchArray()) {
        echo "Set admin privileges for user: $username<br>";
    } else {
        echo "User '$username' not found. Create this user account to grant admin privileges.<br>";
    }
}

echo "<br>Admin features added successfully!<br>";
echo "<br>Admin usernames with special privileges: " . implode(', ', $admin_usernames) . "<br>";
echo "<br>Admin privileges include:<br>";
echo "- No 30-second post cooldown<br>";
echo "- Ability to delete posts and threads<br>";
echo "- Admin badge display<br>";

$db->close();
?>