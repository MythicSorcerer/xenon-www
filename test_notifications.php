<?php
session_start();

// Use absolute path for database
$db_path = __DIR__ . '/db.sqlite';
$db = new SQLite3($db_path);

echo "<h1>Notification System Test</h1>";
echo "<style>body{font-family:Arial;background:#000;color:#0ff;} .error{color:#f44;} .success{color:#4f4;} .warning{color:#fa0;}</style>";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<p class='warning'>You need to be logged in to test notifications.</p>";
    echo "<p><a href='auth.php' style='color:#0ff;'>Login here</a></p>";
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

echo "<h2>Current User: $username (ID: $user_id)</h2>";

// Show current notifications
echo "<h3>Current Notifications:</h3>";
$stmt = $db->prepare('SELECT * FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC');
$stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
$result = $stmt->execute();

$notification_count = 0;
while ($notification = $result->fetchArray(SQLITE3_ASSOC)) {
    $notification_count++;
    $status = $notification['is_read'] ? 'Read' : '<span class="warning">Unread</span>';
    echo "<div style='border:1px solid #0ff;padding:10px;margin:5px;'>";
    echo "<strong>ID:</strong> {$notification['id']}<br>";
    echo "<strong>Message:</strong> {$notification['message']}<br>";
    echo "<strong>Status:</strong> $status<br>";
    echo "<strong>Created:</strong> {$notification['created_at']}<br>";
    echo "</div>";
}

if ($notification_count === 0) {
    echo "<p>No notifications found.</p>";
}

// Test notification count API
echo "<h3>Notification Count API Test:</h3>";
$count_stmt = $db->prepare('SELECT COUNT(*) as count FROM notifications WHERE user_id = :user_id AND is_read = 0');
$count_stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
$count_result = $count_stmt->execute();
$count_row = $count_result->fetchArray(SQLITE3_ASSOC);
echo "Unread notifications in database: " . $count_row['count'] . "<br>";

// Test creating a notification
if (isset($_POST['create_test_notification'])) {
    $test_message = "Test notification created at " . date('Y-m-d H:i:s');
    $insert_stmt = $db->prepare('INSERT INTO notifications (user_id, thread_id, post_id, message) VALUES (:user_id, 1, 1, :message)');
    $insert_stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
    $insert_stmt->bindValue(':message', $test_message, SQLITE3_TEXT);
    
    if ($insert_stmt->execute()) {
        echo "<p class='success'>Test notification created successfully!</p>";
        echo "<script>window.location.reload();</script>";
    } else {
        echo "<p class='error'>Failed to create test notification.</p>";
    }
}

// Show all threads and posts for context
echo "<h3>Available Threads:</h3>";
$threads_result = $db->query('SELECT * FROM threads ORDER BY created_at DESC');
while ($thread = $threads_result->fetchArray(SQLITE3_ASSOC)) {
    echo "<div style='border:1px solid #666;padding:10px;margin:5px;'>";
    echo "<strong>Thread ID:</strong> {$thread['id']}<br>";
    echo "<strong>Title:</strong> {$thread['title']}<br>";
    echo "<strong>Creator:</strong> {$thread['username']} (ID: {$thread['user_id']})<br>";
    echo "<strong>Created:</strong> {$thread['created_at']}<br>";
    
    // Show posts in this thread
    $posts_stmt = $db->prepare('SELECT * FROM posts WHERE thread_id = :thread_id ORDER BY created_at ASC');
    $posts_stmt->bindValue(':thread_id', $thread['id'], SQLITE3_INTEGER);
    $posts_result = $posts_stmt->execute();
    
    echo "<strong>Posts:</strong><br>";
    while ($post = $posts_result->fetchArray(SQLITE3_ASSOC)) {
        echo "&nbsp;&nbsp;• Post ID {$post['id']} by {$post['username']} (ID: {$post['user_id']}): " . substr($post['content'], 0, 50) . "...<br>";
    }
    echo "</div>";
}

echo "<h3>Test Actions:</h3>";
echo "<form method='post'>";
echo "<button type='submit' name='create_test_notification' style='padding:10px;background:#0ff;color:#000;border:none;margin:10px;'>Create Test Notification</button>";
echo "</form>";

echo "<p><a href='notifications.php' style='color:#0ff;'>View Notifications Page</a></p>";
echo "<p><a href='forum.php' style='color:#0ff;'>Back to Forum</a></p>";

$db->close();
?>