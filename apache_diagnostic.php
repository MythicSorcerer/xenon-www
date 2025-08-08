<?php
// Apache Server Diagnostic Script
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Apache Forum Diagnostic</h1>";
echo "<style>body{font-family:Arial;background:#000;color:#0ff;} .error{color:#f44;} .success{color:#4f4;} .warning{color:#fa0;}</style>";

// Test 1: PHP Version and Extensions
echo "<h2>1. PHP Environment</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "SQLite3 Extension: " . (extension_loaded('sqlite3') ? '<span class="success">✓ Loaded</span>' : '<span class="error">✗ Missing</span>') . "<br>";
echo "Session Extension: " . (extension_loaded('session') ? '<span class="success">✓ Loaded</span>' : '<span class="error">✗ Missing</span>') . "<br>";
echo "JSON Extension: " . (extension_loaded('json') ? '<span class="success">✓ Loaded</span>' : '<span class="error">✗ Missing</span>') . "<br>";

// Test 2: File System
echo "<h2>2. File System</h2>";
$current_dir = __DIR__;
$db_path = $current_dir . '/db.sqlite';

echo "Current Directory: $current_dir<br>";
echo "Directory Readable: " . (is_readable($current_dir) ? '<span class="success">✓ Yes</span>' : '<span class="error">✗ No</span>') . "<br>";
echo "Directory Writable: " . (is_writable($current_dir) ? '<span class="success">✓ Yes</span>' : '<span class="error">✗ No</span>') . "<br>";
echo "Database Path: $db_path<br>";
echo "Database Exists: " . (file_exists($db_path) ? '<span class="success">✓ Yes</span>' : '<span class="error">✗ No</span>') . "<br>";

if (file_exists($db_path)) {
    echo "Database Readable: " . (is_readable($db_path) ? '<span class="success">✓ Yes</span>' : '<span class="error">✗ No</span>') . "<br>";
    echo "Database Writable: " . (is_writable($db_path) ? '<span class="success">✓ Yes</span>' : '<span class="error">✗ No</span>') . "<br>";
    echo "Database Size: " . filesize($db_path) . " bytes<br>";
}

// Test 3: Database Connection
echo "<h2>3. Database Connection</h2>";
try {
    $db = new SQLite3($db_path);
    echo "Database Connection: <span class='success'>✓ Success</span><br>";
    
    // Test table existence
    $tables = ['users', 'threads', 'posts', 'notifications'];
    foreach ($tables as $table) {
        $result = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='$table'");
        if ($result && $result->fetchArray()) {
            echo "Table '$table': <span class='success'>✓ Exists</span><br>";
        } else {
            echo "Table '$table': <span class='error'>✗ Missing</span><br>";
        }
    }
    
    // Test data
    $result = $db->query("SELECT COUNT(*) as count FROM threads");
    if ($result) {
        $row = $result->fetchArray();
        echo "Thread Count: " . $row['count'] . "<br>";
    }
    
    $db->close();
} catch (Exception $e) {
    echo "Database Connection: <span class='error'>✗ Failed - " . $e->getMessage() . "</span><br>";
}

// Test 4: Session Handling
echo "<h2>4. Session Handling</h2>";
session_start();
echo "Session Started: <span class='success'>✓ Yes</span><br>";
echo "Session ID: " . session_id() . "<br>";
echo "Session Save Path: " . session_save_path() . "<br>";
echo "Session Save Path Writable: " . (is_writable(session_save_path()) ? '<span class="success">✓ Yes</span>' : '<span class="error">✗ No</span>') . "<br>";

// Test session write
$_SESSION['test'] = 'apache_test_' . time();
echo "Session Write Test: <span class='success'>✓ Set test value</span><br>";

// Test 5: POST Data Handling
echo "<h2>5. POST Data Test</h2>";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "POST Request Received: <span class='success'>✓ Yes</span><br>";
    echo "POST Data: <pre>" . print_r($_POST, true) . "</pre>";
    
    // Test database insert
    if (!empty($_POST['test_title'])) {
        try {
            $db = new SQLite3($db_path);
            $stmt = $db->prepare('INSERT INTO threads (title, username, ip_address) VALUES (?, ?, ?)');
            $stmt->bindValue(1, $_POST['test_title'], SQLITE3_TEXT);
            $stmt->bindValue(2, 'DiagnosticTest', SQLITE3_TEXT);
            $stmt->bindValue(3, $_SERVER['REMOTE_ADDR'] ?? 'unknown', SQLITE3_TEXT);
            $result = $stmt->execute();
            
            if ($result) {
                echo "Database Insert: <span class='success'>✓ Success - ID " . $db->lastInsertRowID() . "</span><br>";
            } else {
                echo "Database Insert: <span class='error'>✗ Failed - " . $db->lastErrorMsg() . "</span><br>";
            }
            $db->close();
        } catch (Exception $e) {
            echo "Database Insert: <span class='error'>✗ Exception - " . $e->getMessage() . "</span><br>";
        }
    }
} else {
    echo "POST Request: <span class='warning'>- Not a POST request</span><br>";
}

// Test 6: Server Variables
echo "<h2>6. Server Environment</h2>";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "<br>";
echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "<br>";
echo "Script Filename: " . ($_SERVER['SCRIPT_FILENAME'] ?? 'Unknown') . "<br>";
echo "Remote Address: " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown') . "<br>";
echo "Request Method: " . ($_SERVER['REQUEST_METHOD'] ?? 'Unknown') . "<br>";

// Test Form
echo "<h2>7. Test Form</h2>";
echo '<form method="post" style="background:#111;padding:20px;border:1px solid #0ff;border-radius:5px;">';
echo '<input type="text" name="test_title" placeholder="Test thread title" required style="padding:10px;margin:10px;background:#000;color:#0ff;border:1px solid #0ff;">';
echo '<button type="submit" style="padding:10px 20px;background:#0ff;color:#000;border:none;margin:10px;">Test Database Insert</button>';
echo '</form>';

// Test 7: Permissions Fix Script
echo "<h2>8. Permission Fix Commands</h2>";
echo "<p>If you have shell access, run these commands:</p>";
echo "<pre style='background:#111;padding:10px;border:1px solid #0ff;'>";
echo "chmod 755 " . $current_dir . "\n";
echo "chmod 666 " . $db_path . "\n";
echo "chown www-data:www-data " . $current_dir . "/*\n";
echo "chown www-data:www-data " . $db_path . "\n";
echo "</pre>";

?>