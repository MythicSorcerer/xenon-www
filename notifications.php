<?php
session_start();

// Use absolute path for database to work with Apache
$db_path = __DIR__ . '/db.sqlite';

try {
    $db = new SQLite3($db_path);
} catch (Exception $e) {
    die("Database connection failed. Please ensure the database file exists and is writable.");
}

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: auth.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Mark notification as read if requested
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    $notification_id = (int)$_GET['mark_read'];
    $stmt = $db->prepare('UPDATE notifications SET is_read = 1 WHERE id = :id AND user_id = :user_id');
    $stmt->bindValue(':id', $notification_id, SQLITE3_INTEGER);
    $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
    $stmt->execute();
    header('Location: notifications.php');
    exit;
}

// Mark all notifications as read if requested
if (isset($_GET['mark_all_read'])) {
    $stmt = $db->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = :user_id');
    $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
    $stmt->execute();
    header('Location: notifications.php');
    exit;
}

// Get all notifications for the user
$stmt = $db->prepare('
    SELECT n.*, t.title as thread_title 
    FROM notifications n 
    JOIN threads t ON n.thread_id = t.id 
    WHERE n.user_id = :user_id 
    ORDER BY n.created_at DESC
');
$stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
$notifications_result = $stmt->execute();

// Count unread notifications
$stmt = $db->prepare('SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = :user_id AND is_read = 0');
$stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
$unread_result = $stmt->execute();
$unread_count = $unread_result->fetchArray(SQLITE3_ASSOC)['unread_count'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Notifications - Xenon Forum</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .notifications-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .notifications-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 1rem;
            background: rgba(0, 255, 225, 0.1);
            border: 1px solid #00ffe1;
            border-radius: 10px;
        }
        .notifications-header h2 {
            color: #00ffe1;
            margin: 0;
        }
        .mark-all-read {
            background: #00ffe1;
            color: #000;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            font-family: 'Orbitron', sans-serif;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background 0.3s;
        }
        .mark-all-read:hover {
            background: #00ccb8;
        }
        .notification {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid #444;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: border-color 0.3s;
        }
        .notification.unread {
            border-color: #00ffe1;
            background: rgba(0, 255, 225, 0.05);
        }
        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        .notification-message {
            color: #ccc;
            margin-bottom: 0.5rem;
        }
        .notification-date {
            color: #888;
            font-size: 0.8rem;
        }
        .notification-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }
        .notification-actions a {
            color: #00ffe1;
            text-decoration: none;
            font-size: 0.9rem;
            padding: 0.3rem 0.8rem;
            border: 1px solid #00ffe1;
            border-radius: 3px;
            transition: background 0.3s;
        }
        .notification-actions a:hover {
            background: rgba(0, 255, 225, 0.1);
        }
        .no-notifications {
            text-align: center;
            color: #888;
            padding: 3rem;
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid #444;
            border-radius: 10px;
        }
        .unread-badge {
            background: #ff4444;
            color: white;
            padding: 0.2rem 0.6rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        .back-link {
            display: inline-block;
            color: #00ffe1;
            text-decoration: none;
            margin: 2rem auto;
            padding: 0.5rem 1rem;
            border: 1px solid #00ffe1;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .back-link:hover {
            background: rgba(0, 255, 225, 0.1);
        }
    </style>
</head>
<body>
    <!-- Headers will be loaded here -->
    <div id="headers"></div>
    
    <header>
        <h1>Xenon Forum</h1>
        <p>Your Notifications</p>
    </header>

    <div style="text-align: center;">
        <a href="forum.php" class="back-link">← Back to Forum</a>
    </div>

    <div class="notifications-container">
        <div class="notifications-header">
            <h2>
                Notifications 
                <?php if ($unread_count > 0): ?>
                    <span class="unread-badge"><?= $unread_count ?> unread</span>
                <?php endif; ?>
            </h2>
            <?php if ($unread_count > 0): ?>
                <a href="notifications.php?mark_all_read=1" class="mark-all-read">Mark All Read</a>
            <?php endif; ?>
        </div>

        <?php 
        $has_notifications = false;
        while ($notification = $notifications_result->fetchArray(SQLITE3_ASSOC)): 
            $has_notifications = true;
        ?>
            <div class="notification <?= $notification['is_read'] ? '' : 'unread' ?>">
                <div class="notification-header">
                    <div class="notification-message">
                        <?= htmlspecialchars($notification['message']) ?>
                    </div>
                    <?php if (!$notification['is_read']): ?>
                        <span class="unread-badge">NEW</span>
                    <?php endif; ?>
                </div>
                <div class="notification-date">
                    <?= date('M j, Y \a\t g:i A', strtotime($notification['created_at'])) ?>
                </div>
                <div class="notification-actions">
                    <a href="thread.php?id=<?= $notification['thread_id'] ?>#post-<?= $notification['post_id'] ?>">View Reply</a>
                    <?php if (!$notification['is_read']): ?>
                        <a href="notifications.php?mark_read=<?= $notification['id'] ?>">Mark Read</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; ?>

        <?php if (!$has_notifications): ?>
            <div class="no-notifications">
                <h3>No notifications yet</h3>
                <p>When someone replies to your threads or posts, you'll see notifications here.</p>
            </div>
        <?php endif; ?>
    </div>

    <footer>
        &copy; <?= date("Y") ?> Xenon Forum
    </footer>

    <script>
        async function loadHeaders() {
            const res = await fetch('headers.php');
            const text = await res.text();
            document.getElementById('headers').innerHTML = text;
        }
        loadHeaders();
    </script>
</body>
</html>