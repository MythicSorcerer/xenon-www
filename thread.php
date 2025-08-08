<?php
session_start();

// Include admin configuration and database initialization
require_once 'admin_config.php';
require_once 'db_init.php';

// Initialize database with automatic table creation
$db = getDatabaseConnection();

$thread_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($thread_id <= 0) {
    header('Location: forum.php');
    exit;
}

// Get thread information
$stmt = $db->prepare('SELECT * FROM threads WHERE id = :id');
$stmt->bindValue(':id', $thread_id, SQLITE3_INTEGER);
$result = $stmt->execute();
$thread = $result->fetchArray(SQLITE3_ASSOC);

if (!$thread) {
    header('Location: forum.php');
    exit;
}

// Handle thread deletion (admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_thread']) && isset($_SESSION['user_id'])) {
    // Check if user is admin
    if (isset($_SESSION['username']) && is_admin($_SESSION['username'])) {
        $delete_stmt = $db->prepare('UPDATE threads SET is_deleted = 1 WHERE id = :thread_id');
        $delete_stmt->bindValue(':thread_id', $thread_id, SQLITE3_INTEGER);
        $delete_stmt->execute();
        
        header('Location: forum.php');
        exit;
    }
}

// Handle post deletion with enhanced permissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_post'])) {
    $post_id = (int)$_POST['delete_post'];
    $current_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $can_delete = false;
    $delete_reason = '';
    
    // Get post information
    $post_stmt = $db->prepare('SELECT * FROM posts WHERE id = :post_id AND is_deleted = 0');
    $post_stmt->bindValue(':post_id', $post_id, SQLITE3_INTEGER);
    $post_result = $post_stmt->execute();
    $post_to_delete = $post_result->fetchArray(SQLITE3_ASSOC);
    
    if ($post_to_delete) {
        // Check deletion permissions
        
        // 1. Admin can delete any post
        if (isset($_SESSION['user_id']) && isCurrentUserAdmin($db, $_SESSION['user_id'])) {
            $can_delete = true;
            $delete_reason = 'admin';
        }
        // 2. Logged-in user can delete their own posts
        elseif (isset($_SESSION['user_id']) && $post_to_delete['user_id'] == $_SESSION['user_id']) {
            $can_delete = true;
            $delete_reason = 'own_post';
        }
        // 3. Anonymous user can delete their own post from same IP within 10 minutes
        elseif (!isset($_SESSION['user_id']) && !$post_to_delete['user_id'] && $post_to_delete['ip_address'] == $current_ip) {
            $post_time = strtotime($post_to_delete['created_at']);
            $current_time = time();
            $time_diff = $current_time - $post_time;
            
            if ($time_diff <= 600) { // 10 minutes = 600 seconds
                $can_delete = true;
                $delete_reason = 'anonymous_window';
            }
        }
        
        if ($can_delete) {
            try {
                // Begin transaction to handle foreign key constraints
                $db->exec('BEGIN TRANSACTION');
                
                // First, delete any notifications related to this post
                $delete_notifications_stmt = $db->prepare('DELETE FROM notifications WHERE post_id = :post_id');
                $delete_notifications_stmt->bindValue(':post_id', $post_id, SQLITE3_INTEGER);
                $delete_notifications_stmt->execute();
                
                // Then soft-delete the post
                $delete_stmt = $db->prepare('UPDATE posts SET is_deleted = 1 WHERE id = :post_id');
                $delete_stmt->bindValue(':post_id', $post_id, SQLITE3_INTEGER);
                $delete_stmt->execute();
                
                // Commit the transaction
                $db->exec('COMMIT');
                
                header('Location: thread.php?id=' . $thread_id);
                exit;
            } catch (Exception $e) {
                // Rollback on error
                $db->exec('ROLLBACK');
                error_log("Post deletion error: " . $e->getMessage());
            }
        }
    }
}

// Handle new post submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['content'])) {
    $content = trim($_POST['content']);
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Anonymous';
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $cooldown_error = '';
    
    // Check cooldown for users (admins are exempt)
    if ($user_id) {
        // Registered user cooldown check
        if (!isCurrentUserAdmin($db, $user_id)) {
            $user_stmt = $db->prepare('SELECT last_post_time FROM users WHERE id = :user_id');
            $user_stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
            $user_result = $user_stmt->execute();
            $user_data = $user_result->fetchArray(SQLITE3_ASSOC);
            
            if ($user_data && $user_data['last_post_time']) {
                $last_post = strtotime($user_data['last_post_time']);
                $current_time = time();
                $time_diff = $current_time - $last_post;
                
                if ($time_diff < 5) {
                    $remaining = 5 - $time_diff;
                    $cooldown_error = "Please wait {$remaining} seconds before posting again.";
                }
            }
        }
    } else {
        // Anonymous user IP-based cooldown check
        $ip_stmt = $db->prepare('SELECT last_post_time FROM ip_cooldowns WHERE ip_address = :ip');
        $ip_stmt->bindValue(':ip', $ip_address, SQLITE3_TEXT);
        $ip_result = $ip_stmt->execute();
        $ip_data = $ip_result->fetchArray(SQLITE3_ASSOC);
        
        if ($ip_data) {
            $last_post = strtotime($ip_data['last_post_time']);
            $current_time = time();
            $time_diff = $current_time - $last_post;
            
            if ($time_diff < 5) {
                $remaining = 5 - $time_diff;
                $cooldown_error = "Please wait {$remaining} seconds before posting again. (Anonymous users have a 5-second cooldown)";
            }
        }
    }
    
    if (!empty($content) && empty($cooldown_error)) {
        try {
            $stmt = $db->prepare('INSERT INTO posts (thread_id, content, user_id, username, ip_address) VALUES (:thread_id, :content, :user_id, :username, :ip_address)');
            if (!$stmt) {
                throw new Exception("Database prepare failed");
            }
            
            $stmt->bindValue(':thread_id', $thread_id, SQLITE3_INTEGER);
            $stmt->bindValue(':content', $content, SQLITE3_TEXT);
            $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
            $stmt->bindValue(':username', $username, SQLITE3_TEXT);
            $stmt->bindValue(':ip_address', $ip_address, SQLITE3_TEXT);
            
            $result = $stmt->execute();
            if (!$result) {
                throw new Exception("Database execute failed");
            }
            
            $post_id = $db->lastInsertRowID();
            
            // Update last post time for registered users and IP addresses
            if ($user_id) {
                $update_time_stmt = $db->prepare('UPDATE users SET last_post_time = CURRENT_TIMESTAMP WHERE id = :user_id');
                $update_time_stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
                $update_time_stmt->execute();
            } else {
                // Update IP cooldown for anonymous users
                $ip_update_stmt = $db->prepare('INSERT OR REPLACE INTO ip_cooldowns (ip_address, last_post_time) VALUES (:ip, CURRENT_TIMESTAMP)');
                $ip_update_stmt->bindValue(':ip', $ip_address, SQLITE3_TEXT);
                $ip_update_stmt->execute();
            }
            
            // Create notifications for thread participants (excluding the poster)
            try {
                // Get all unique user IDs who should be notified
                $users_to_notify = [];
                
                // Add thread creator if they're a registered user and not the current poster
                if ($thread['user_id'] && $thread['user_id'] != $user_id) {
                    $users_to_notify[] = $thread['user_id'];
                }
                
                // Add all users who have posted in this thread (excluding current poster)
                $participants_stmt = $db->prepare('
                    SELECT DISTINCT user_id
                    FROM posts
                    WHERE thread_id = :thread_id
                    AND user_id IS NOT NULL
                    AND user_id != :current_user_id
                ');
                $participants_stmt->bindValue(':thread_id', $thread_id, SQLITE3_INTEGER);
                $participants_stmt->bindValue(':current_user_id', $user_id, SQLITE3_INTEGER);
                $participants_result = $participants_stmt->execute();
                
                while ($participant = $participants_result->fetchArray(SQLITE3_ASSOC)) {
                    if (!in_array($participant['user_id'], $users_to_notify)) {
                        $users_to_notify[] = $participant['user_id'];
                    }
                }
                
                // Create notifications for each user
                $message = $username . ' replied to "' . $thread['title'] . '"';
                foreach ($users_to_notify as $notify_user_id) {
                    $notification_stmt = $db->prepare('
                        INSERT INTO notifications (user_id, thread_id, post_id, message)
                        VALUES (:user_id, :thread_id, :post_id, :message)
                    ');
                    $notification_stmt->bindValue(':user_id', $notify_user_id, SQLITE3_INTEGER);
                    $notification_stmt->bindValue(':thread_id', $thread_id, SQLITE3_INTEGER);
                    $notification_stmt->bindValue(':post_id', $post_id, SQLITE3_INTEGER);
                    $notification_stmt->bindValue(':message', $message, SQLITE3_TEXT);
                    $notification_stmt->execute();
                }
            } catch (Exception $e) {
                // Log notification error but don't fail the post
                error_log("Notification creation error: " . $e->getMessage());
            }
            
            header('Location: thread.php?id=' . $thread_id);
            exit;
        } catch (Exception $e) {
            error_log("Thread post error: " . $e->getMessage());
        }
    }
}

// Get all posts for this thread (excluding deleted ones)
$stmt = $db->prepare('SELECT * FROM posts WHERE thread_id = :thread_id AND is_deleted = 0 ORDER BY created_at ASC');
$stmt->bindValue(':thread_id', $thread_id, SQLITE3_INTEGER);
$posts_result = $stmt->execute();

// Check if current user is admin
$is_current_user_admin = isset($_SESSION['user_id']) && isCurrentUserAdmin($db, $_SESSION['user_id']);
?>
<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($thread['title']) ?> - Xenon Forum</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .thread-header {
            background: rgba(0, 255, 225, 0.1);
            border: 1px solid #00ffe1;
            border-radius: 10px;
            padding: 1.5rem;
            margin: 2rem auto;
            max-width: 800px;
        }
        .thread-title {
            color: #00ffe1;
            margin: 0 0 0.5rem 0;
            font-size: 1.8rem;
        }
        .thread-meta {
            color: #ccc;
            font-size: 0.9rem;
        }
        .posts-container {
            max-width: 800px;
            margin: 2rem auto;
        }
        .post {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid #444;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }
        .post-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #333;
        }
        .post-author {
            color: #00ffe1;
            font-weight: bold;
        }
        .post-date {
            color: #888;
            font-size: 0.8rem;
        }
        .post-content {
            color: #ccc;
            line-height: 1.6;
            white-space: pre-wrap;
        }
        .reply-form {
            background: rgba(0, 255, 225, 0.1);
            border: 1px solid #00ffe1;
            border-radius: 10px;
            padding: 1.5rem;
            margin: 2rem auto;
            max-width: 800px;
        }
        .reply-form h3 {
            color: #00ffe1;
            margin-top: 0;
        }
        .reply-form textarea {
            width: 100%;
            min-height: 120px;
            padding: 1rem;
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid #00ffe1;
            border-radius: 5px;
            color: #fff;
            font-family: 'Arial', sans-serif;
            font-size: 14px;
            line-height: 1.5;
            resize: vertical;
            box-sizing: border-box;
        }
        .reply-form textarea:focus {
            outline: none;
            box-shadow: 0 0 10px rgba(0, 255, 225, 0.3);
        }
        .reply-form button {
            background: #00ffe1;
            color: #000;
            border: none;
            padding: 1rem 2.5rem;
            border-radius: 5px;
            font-family: 'Orbitron', sans-serif;
            font-weight: bold;
            font-size: 14px;
            cursor: pointer;
            transition: background 0.3s;
            margin-top: 1.5rem;
            display: block;
        }
        .reply-form button:hover {
            background: #00ccb8;
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
        .anonymous-note {
            color: #888;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }
        .admin-badge {
            background: #ff6b6b;
            color: white;
            padding: 0.2rem 0.5rem;
            border-radius: 3px;
            font-size: 0.7rem;
            font-weight: bold;
            margin-left: 0.5rem;
        }
        .delete-btn {
            background: #ff4444;
            color: white;
            border: none;
            padding: 0.3rem 0.6rem;
            border-radius: 3px;
            font-size: 0.7rem;
            cursor: pointer;
            margin-left: 0.5rem;
        }
        .delete-btn:hover {
            background: #cc0000;
        }
        .cooldown-error {
            color: #ff4444;
            background: rgba(255, 68, 68, 0.1);
            border: 1px solid #ff4444;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <!-- Headers will be loaded here -->
    <div id="headers"></div>
    
    <header>
        <h1>Xenon Forum</h1>
        <p>Thread Discussion</p>
    </header>

    <div style="text-align: center;">
        <a href="forum.php" class="back-link">← Back to Forum</a>
    </div>

    <div class="thread-header">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <h2 class="thread-title"><?= htmlspecialchars($thread['title']) ?></h2>
                <div class="thread-meta">
                    Started by <strong><?= htmlspecialchars($thread['username']) ?></strong>
                    on <?= date('M j, Y \a\t g:i A', strtotime($thread['created_at'])) ?>
                </div>
            </div>
            <?php if ($is_current_user_admin): ?>
                <form method="post" style="margin: 0;">
                    <button type="submit" name="delete_thread" value="1" class="delete-btn" onclick="return confirm('Are you sure you want to delete this entire thread? This action cannot be undone.')" style="background: #cc0000; padding: 0.5rem 1rem;">Delete Thread</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="posts-container">
        <?php while ($post = $posts_result->fetchArray(SQLITE3_ASSOC)): ?>
            <?php
            // Check if post author is admin
            $post_author_is_admin = false;
            if ($post['user_id']) {
                $author_admin_stmt = $db->prepare('SELECT is_admin FROM users WHERE id = :user_id');
                $author_admin_stmt->bindValue(':user_id', $post['user_id'], SQLITE3_INTEGER);
                $author_admin_result = $author_admin_stmt->execute();
                $author_admin_data = $author_admin_result->fetchArray(SQLITE3_ASSOC);
                $post_author_is_admin = $author_admin_data && $author_admin_data['is_admin'];
            }
            
            // Check if current user can delete this post
            $can_delete_post = false;
            $delete_button_text = 'Delete';
            $current_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            
            // Admin can delete any post
            if ($is_current_user_admin) {
                $can_delete_post = true;
                $delete_button_text = 'Delete (Admin)';
            }
            // Logged-in user can delete their own posts
            elseif (isset($_SESSION['user_id']) && $post['user_id'] == $_SESSION['user_id']) {
                $can_delete_post = true;
                $delete_button_text = 'Delete';
            }
            // Anonymous user can delete their own post from same IP within 10 minutes
            elseif (!isset($_SESSION['user_id']) && !$post['user_id'] && $post['ip_address'] == $current_ip) {
                $post_time = strtotime($post['created_at']);
                $current_time = time();
                $time_diff = $current_time - $post_time;
                
                if ($time_diff <= 600) { // 10 minutes = 600 seconds
                    $can_delete_post = true;
                    $remaining_minutes = ceil((600 - $time_diff) / 60);
                    $delete_button_text = "Delete ({$remaining_minutes}min left)";
                }
            }
            ?>
            <div class="post">
                <div class="post-header">
                    <span class="post-author">
                        <?= htmlspecialchars($post['username']) ?>
                        <?php if ($post_author_is_admin): ?>
                            <span class="admin-badge">ADMIN</span>
                        <?php endif; ?>
                    </span>
                    <div>
                        <span class="post-date"><?= date('M j, Y \a\t g:i A', strtotime($post['created_at'])) ?></span>
                        <?php if ($can_delete_post): ?>
                            <form method="post" style="display: inline;">
                                <button type="submit" name="delete_post" value="<?= $post['id'] ?>" class="delete-btn" onclick="return confirm('Are you sure you want to delete this post?')"><?= htmlspecialchars($delete_button_text) ?></button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="post-content"><?= htmlspecialchars($post['content']) ?></div>
            </div>
        <?php endwhile; ?>
    </div>

    <div class="reply-form">
        <h3>Reply to Thread</h3>
        <?php if (isset($_SESSION['user_id'])): ?>
            <?php
            // Check if current user is admin for display
            $current_user_admin_stmt = $db->prepare('SELECT is_admin FROM users WHERE id = :user_id');
            $current_user_admin_stmt->bindValue(':user_id', $_SESSION['user_id'], SQLITE3_INTEGER);
            $current_user_admin_result = $current_user_admin_stmt->execute();
            $current_user_admin_data = $current_user_admin_result->fetchArray(SQLITE3_ASSOC);
            $current_user_is_admin = $current_user_admin_data && $current_user_admin_data['is_admin'];
            ?>
            <p>Posting as: <strong style="color: #00ffe1;"><?= htmlspecialchars($_SESSION['username']) ?></strong>
            <?php if ($current_user_is_admin): ?>
                <span class="admin-badge">ADMIN</span>
                <span style="color: #4f4; font-size: 0.8rem; margin-left: 0.5rem;">(No cooldown)</span>
            <?php endif; ?>
            </p>
        <?php else: ?>
            <p class="anonymous-note">You are posting anonymously. <a href="auth.php" style="color: #00ffe1;">Login</a> to post with your username.</p>
        <?php endif; ?>
        
        <?php if (!empty($cooldown_error)): ?>
            <div class="cooldown-error"><?= htmlspecialchars($cooldown_error) ?></div>
        <?php endif; ?>
        
        <form method="post">
            <textarea name="content" placeholder="Write your reply here..." required></textarea>
            <br>
            <button type="submit">Post Reply</button>
        </form>
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