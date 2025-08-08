<?php
// Apache Setup and Fix Script
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Apache Forum Setup Script</h1>";
echo "<style>body{font-family:Arial;background:#000;color:#0ff;} .error{color:#f44;} .success{color:#4f4;} .warning{color:#fa0;} .code{background:#111;padding:10px;border:1px solid #0ff;margin:10px 0;}</style>";

$current_dir = __DIR__;
$db_path = $current_dir . '/db.sqlite';
$fixes_applied = [];
$errors = [];

// Function to log results
function logResult($message, $success = true) {
    global $fixes_applied, $errors;
    if ($success) {
        $fixes_applied[] = $message;
        echo "<span class='success'>✓ $message</span><br>";
    } else {
        $errors[] = $message;
        echo "<span class='error'>✗ $message</span><br>";
    }
}

echo "<h2>Step 1: Directory Permissions</h2>";

// Fix directory permissions
if (is_writable($current_dir)) {
    logResult("Directory is already writable");
} else {
    if (chmod($current_dir, 0755)) {
        logResult("Fixed directory permissions (755)");
    } else {
        logResult("Failed to fix directory permissions", false);
    }
}

echo "<h2>Step 2: Database Setup</h2>";

// Check if database exists
if (!file_exists($db_path)) {
    echo "Database doesn't exist. Creating...<br>";
    
    try {
        $db = new SQLite3($db_path);
        
        // Create all tables
        $db->exec('CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            email TEXT UNIQUE NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )');
        
        $db->exec('CREATE TABLE IF NOT EXISTS threads (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            user_id INTEGER,
            username TEXT DEFAULT "Anonymous",
            ip_address TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )');
        
        $db->exec('CREATE TABLE IF NOT EXISTS posts (
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
        
        $db->close();
        logResult("Database created successfully");
    } catch (Exception $e) {
        logResult("Failed to create database: " . $e->getMessage(), false);
    }
}

// Fix database permissions
if (file_exists($db_path)) {
    if (is_writable($db_path)) {
        logResult("Database is already writable");
    } else {
        if (chmod($db_path, 0666)) {
            logResult("Fixed database permissions (666)");
        } else {
            logResult("Failed to fix database permissions", false);
        }
    }
}

echo "<h2>Step 3: Session Directory</h2>";

// Check session directory
$session_path = session_save_path();
if (empty($session_path)) {
    $session_path = sys_get_temp_dir();
}

if (is_writable($session_path)) {
    logResult("Session directory is writable: $session_path");
} else {
    logResult("Session directory is not writable: $session_path", false);
    
    // Try to create a local session directory
    $local_session_dir = $current_dir . '/sessions';
    if (!is_dir($local_session_dir)) {
        if (mkdir($local_session_dir, 0755)) {
            logResult("Created local session directory");
            if (chmod($local_session_dir, 0755)) {
                logResult("Set permissions on session directory");
            }
        } else {
            logResult("Failed to create local session directory", false);
        }
    }
}

echo "<h2>Step 4: Test Database Operations</h2>";

// Test database operations
try {
    $db = new SQLite3($db_path);
    
    // Test insert
    $test_title = "Setup Test " . date('Y-m-d H:i:s');
    $stmt = $db->prepare('INSERT INTO threads (title, username, ip_address) VALUES (?, ?, ?)');
    $stmt->bindValue(1, $test_title, SQLITE3_TEXT);
    $stmt->bindValue(2, 'SetupScript', SQLITE3_TEXT);
    $stmt->bindValue(3, $_SERVER['REMOTE_ADDR'] ?? 'setup', SQLITE3_TEXT);
    
    if ($stmt->execute()) {
        $insert_id = $db->lastInsertRowID();
        logResult("Database insert test successful (ID: $insert_id)");
        
        // Test select
        $result = $db->query("SELECT * FROM threads WHERE id = $insert_id");
        if ($result && $result->fetchArray()) {
            logResult("Database select test successful");
        } else {
            logResult("Database select test failed", false);
        }
        
        // Clean up test data
        $db->exec("DELETE FROM threads WHERE id = $insert_id");
        logResult("Cleaned up test data");
    } else {
        logResult("Database insert test failed: " . $db->lastErrorMsg(), false);
    }
    
    $db->close();
} catch (Exception $e) {
    logResult("Database test failed: " . $e->getMessage(), false);
}

echo "<h2>Step 5: Session Test</h2>";

// Test session functionality
session_start();
$_SESSION['setup_test'] = 'apache_setup_' . time();
if (isset($_SESSION['setup_test'])) {
    logResult("Session test successful");
} else {
    logResult("Session test failed", false);
}

echo "<h2>Step 6: File Ownership (if needed)</h2>";

// Check file ownership
$file_owner = fileowner($current_dir);
$current_user = posix_getpwuid($file_owner);
$web_user = posix_getpwnam('www-data') ?: posix_getpwnam('apache') ?: posix_getpwnam('httpd');

if ($current_user && $web_user) {
    if ($file_owner === $web_user['uid']) {
        logResult("Files are owned by web server user");
    } else {
        echo "<div class='warning'>Files are owned by: " . $current_user['name'] . "</div>";
        echo "<div class='warning'>Web server user: " . $web_user['name'] . "</div>";
        echo "<div class='code'>Run this command to fix ownership:<br>";
        echo "sudo chown -R " . $web_user['name'] . ":" . $web_user['name'] . " " . $current_dir . "</div>";
    }
}

echo "<h2>Setup Summary</h2>";

if (count($fixes_applied) > 0) {
    echo "<h3 class='success'>Fixes Applied:</h3>";
    foreach ($fixes_applied as $fix) {
        echo "• $fix<br>";
    }
}

if (count($errors) > 0) {
    echo "<h3 class='error'>Issues Found:</h3>";
    foreach ($errors as $error) {
        echo "• $error<br>";
    }
    
    echo "<h3>Manual Fix Commands:</h3>";
    echo "<div class='code'>";
    echo "# Fix permissions<br>";
    echo "chmod 755 " . $current_dir . "<br>";
    echo "chmod 666 " . $db_path . "<br>";
    echo "<br># Fix ownership (run as root)<br>";
    echo "chown -R www-data:www-data " . $current_dir . "<br>";
    echo "<br># Create session directory<br>";
    echo "mkdir " . $current_dir . "/sessions<br>";
    echo "chmod 755 " . $current_dir . "/sessions<br>";
    echo "</div>";
} else {
    echo "<h3 class='success'>All checks passed! Your forum should now work on Apache.</h3>";
    echo "<p><a href='forum.php' style='color:#0ff;'>Test the forum now →</a></p>";
}

echo "<h2>Next Steps</h2>";
echo "<ol>";
echo "<li>Test the forum by visiting <a href='forum.php' style='color:#0ff;'>forum.php</a></li>";
echo "<li>Try creating a user account at <a href='auth.php' style='color:#0ff;'>auth.php</a></li>";
echo "<li>If issues persist, check <a href='apache_diagnostic.php' style='color:#0ff;'>apache_diagnostic.php</a></li>";
echo "<li>Remove setup files after testing: apache_setup.php, apache_diagnostic.php</li>";
echo "</ol>";

?>