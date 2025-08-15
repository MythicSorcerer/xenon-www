<?php
session_start();
require_once 'admin_config.php';
require_once 'db_init.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isCurrentUserAdmin(getDatabaseConnection(), $_SESSION['user_id'])) {
    header('Location: auth.php');
    exit;
}

$db = getDatabaseConnection();
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'edit_user':
                    $stmt = $db->prepare('UPDATE users SET username = :username, email = :email WHERE id = :id');
                    $stmt->bindValue(':username', $_POST['username'], SQLITE3_TEXT);
                    $stmt->bindValue(':email', $_POST['email'], SQLITE3_TEXT);
                    $stmt->bindValue(':id', $_POST['id'], SQLITE3_INTEGER);
                    $stmt->execute();
                    $message = 'User updated successfully';
                    break;
                    
                case 'delete_user':
                    $stmt = $db->prepare('DELETE FROM users WHERE id = :id');
                    $stmt->bindValue(':id', $_POST['id'], SQLITE3_INTEGER);
                    $stmt->execute();
                    $message = 'User deleted successfully';
                    break;
                    
                case 'edit_thread':
                    $stmt = $db->prepare('UPDATE threads SET title = :title, username = :username WHERE id = :id');
                    $stmt->bindValue(':title', $_POST['title'], SQLITE3_TEXT);
                    $stmt->bindValue(':username', $_POST['username'], SQLITE3_TEXT);
                    $stmt->bindValue(':id', $_POST['id'], SQLITE3_INTEGER);
                    $stmt->execute();
                    $message = 'Thread updated successfully';
                    break;
                    
                case 'delete_thread':
                    // Permanently delete thread and all related data
                    $db->exec('BEGIN TRANSACTION');
                    
                    // Delete notifications related to this thread
                    $stmt = $db->prepare('DELETE FROM notifications WHERE thread_id = :id');
                    $stmt->bindValue(':id', $_POST['id'], SQLITE3_INTEGER);
                    $stmt->execute();
                    
                    // Delete post tags for posts in this thread
                    $stmt = $db->prepare('DELETE FROM post_tags WHERE post_id IN (SELECT id FROM posts WHERE thread_id = :id)');
                    $stmt->bindValue(':id', $_POST['id'], SQLITE3_INTEGER);
                    $stmt->execute();
                    
                    // Delete posts in this thread
                    $stmt = $db->prepare('DELETE FROM posts WHERE thread_id = :id');
                    $stmt->bindValue(':id', $_POST['id'], SQLITE3_INTEGER);
                    $stmt->execute();
                    
                    // Delete thread tags
                    $stmt = $db->prepare('DELETE FROM thread_tags WHERE thread_id = :id');
                    $stmt->bindValue(':id', $_POST['id'], SQLITE3_INTEGER);
                    $stmt->execute();
                    
                    // Delete the thread itself
                    $stmt = $db->prepare('DELETE FROM threads WHERE id = :id');
                    $stmt->bindValue(':id', $_POST['id'], SQLITE3_INTEGER);
                    $stmt->execute();
                    
                    $db->exec('COMMIT');
                    $message = 'Thread permanently deleted successfully';
                    break;
                    
                case 'delete_multiple_threads':
                    if (!empty($_POST['selected_threads'])) {
                        $db->exec('BEGIN TRANSACTION');
                        $deleted_count = 0;
                        
                        foreach ($_POST['selected_threads'] as $thread_id) {
                            $thread_id = (int)$thread_id;
                            
                            // Delete notifications related to this thread
                            $stmt = $db->prepare('DELETE FROM notifications WHERE thread_id = :id');
                            $stmt->bindValue(':id', $thread_id, SQLITE3_INTEGER);
                            $stmt->execute();
                            
                            // Delete post tags for posts in this thread
                            $stmt = $db->prepare('DELETE FROM post_tags WHERE post_id IN (SELECT id FROM posts WHERE thread_id = :id)');
                            $stmt->bindValue(':id', $thread_id, SQLITE3_INTEGER);
                            $stmt->execute();
                            
                            // Delete posts in this thread
                            $stmt = $db->prepare('DELETE FROM posts WHERE thread_id = :id');
                            $stmt->bindValue(':id', $thread_id, SQLITE3_INTEGER);
                            $stmt->execute();
                            
                            // Delete thread tags
                            $stmt = $db->prepare('DELETE FROM thread_tags WHERE thread_id = :id');
                            $stmt->bindValue(':id', $thread_id, SQLITE3_INTEGER);
                            $stmt->execute();
                            
                            // Delete the thread itself
                            $stmt = $db->prepare('DELETE FROM threads WHERE id = :id');
                            $stmt->bindValue(':id', $thread_id, SQLITE3_INTEGER);
                            $stmt->execute();
                            
                            $deleted_count++;
                        }
                        
                        $db->exec('COMMIT');
                        $message = "Successfully deleted {$deleted_count} threads permanently";
                    } else {
                        $error = 'No threads selected for deletion';
                    }
                    break;
                    
                case 'edit_post':
                    $stmt = $db->prepare('UPDATE posts SET content = :content, username = :username WHERE id = :id');
                    $stmt->bindValue(':content', $_POST['content'], SQLITE3_TEXT);
                    $stmt->bindValue(':username', $_POST['username'], SQLITE3_TEXT);
                    $stmt->bindValue(':id', $_POST['id'], SQLITE3_INTEGER);
                    $stmt->execute();
                    $message = 'Post updated successfully';
                    break;
                    
                case 'delete_post':
                    // Permanently delete post and related data
                    $db->exec('BEGIN TRANSACTION');
                    
                    // Delete notifications related to this post
                    $stmt = $db->prepare('DELETE FROM notifications WHERE post_id = :id');
                    $stmt->bindValue(':id', $_POST['id'], SQLITE3_INTEGER);
                    $stmt->execute();
                    
                    // Delete post tags
                    $stmt = $db->prepare('DELETE FROM post_tags WHERE post_id = :id');
                    $stmt->bindValue(':id', $_POST['id'], SQLITE3_INTEGER);
                    $stmt->execute();
                    
                    // Delete the post itself
                    $stmt = $db->prepare('DELETE FROM posts WHERE id = :id');
                    $stmt->bindValue(':id', $_POST['id'], SQLITE3_INTEGER);
                    $stmt->execute();
                    
                    $db->exec('COMMIT');
                    $message = 'Post permanently deleted successfully';
                    break;
                    
                case 'clear_notifications':
                    $stmt = $db->prepare('DELETE FROM notifications WHERE user_id = :user_id');
                    $stmt->bindValue(':user_id', $_POST['user_id'], SQLITE3_INTEGER);
                    $stmt->execute();
                    $message = 'Notifications cleared for user';
                    break;
            }
        }
    } catch (Exception $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}

// Get current view
$view = $_GET['view'] ?? 'users';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard - Xenon Forum</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="styles.css">
    <style>
        .admin-dashboard {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .dashboard-nav {
            background: rgba(0, 255, 225, 0.1);
            border: 1px solid #00ffe1;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 2rem;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .nav-btn {
            background: #00ffe1;
            color: #000;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            text-decoration: none;
            font-family: 'Orbitron', sans-serif;
            font-weight: bold;
            font-size: 0.9rem;
            cursor: pointer;
            transition: background 0.3s;
        }
        .nav-btn:hover, .nav-btn.active {
            background: #00ccb8;
        }
        .data-table {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid #444;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 2rem;
        }
        .table-header {
            background: rgba(0, 255, 225, 0.2);
            padding: 1rem;
            border-bottom: 1px solid #00ffe1;
        }
        .table-header h3 {
            color: #00ffe1;
            margin: 0;
        }
        .table-content {
            max-height: 600px;
            overflow-y: auto;
        }
        .data-row {
            border-bottom: 1px solid #333;
            padding: 1rem;
            display: grid;
            gap: 1rem;
            align-items: center;
        }
        .data-row:last-child {
            border-bottom: none;
        }
        .users-row {
            grid-template-columns: 50px 1fr 1fr 150px 100px 150px;
        }
        .threads-row {
            grid-template-columns: 50px 2fr 1fr 150px 100px;
        }
        .posts-row {
            grid-template-columns: 50px 3fr 1fr 150px 100px;
        }
        .notifications-row {
            grid-template-columns: 50px 1fr 2fr 150px 100px;
        }
        .data-field {
            color: #ccc;
            font-size: 0.9rem;
        }
        .data-field input, .data-field textarea {
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid #555;
            border-radius: 3px;
            color: #fff;
            padding: 0.3rem;
            font-size: 0.8rem;
            width: 100%;
            box-sizing: border-box;
        }
        .data-field textarea {
            min-height: 60px;
            resize: vertical;
        }
        .action-btn {
            background: #00ffe1;
            color: #000;
            border: none;
            padding: 0.3rem 0.6rem;
            border-radius: 3px;
            font-size: 0.7rem;
            cursor: pointer;
            margin: 0.1rem;
            font-family: 'Orbitron', sans-serif;
            font-weight: bold;
        }
        .action-btn:hover {
            background: #00ccb8;
        }
        .delete-btn {
            background: #ff4444;
            color: white;
        }
        .delete-btn:hover {
            background: #cc3333;
        }
        .message {
            background: rgba(0, 255, 0, 0.1);
            border: 1px solid #00ff00;
            color: #00ff00;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }
        .error {
            background: rgba(255, 0, 0, 0.1);
            border: 1px solid #ff0000;
            color: #ff0000;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: rgba(0, 255, 225, 0.1);
            border: 1px solid #00ffe1;
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
        }
        .stat-number {
            font-size: 2rem;
            color: #00ffe1;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        .stat-label {
            color: #ccc;
            font-size: 0.9rem;
        }
        .checkbox-field {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .checkbox-field input[type="checkbox"] {
            width: auto;
        }
        .multi-select-controls {
            background: rgba(255, 107, 107, 0.1);
            border: 1px solid #ff6b6b;
            border-radius: 5px;
            padding: 1rem;
            margin-bottom: 1rem;
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }
        .select-all-btn, .delete-selected-btn {
            background: #ff6b6b;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 3px;
            font-family: 'Orbitron', sans-serif;
            font-weight: bold;
            font-size: 0.8rem;
            cursor: pointer;
            transition: background 0.3s;
        }
        .select-all-btn:hover, .delete-selected-btn:hover {
            background: #cc3333;
        }
        .thread-checkbox {
            width: auto !important;
            margin-right: 0.5rem;
        }
    </style>
</head>
<body>
    <!-- Headers will be loaded here -->
    <div id="headers"></div>
    
    <header>
        <h1>Xenon Forum</h1>
        <p>Admin Dashboard</p>
    </header>

    <div class="admin-dashboard">
        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Navigation -->
        <div class="dashboard-nav">
            <a href="?view=stats" class="nav-btn <?= $view === 'stats' ? 'active' : '' ?>">Statistics</a>
            <a href="?view=users" class="nav-btn <?= $view === 'users' ? 'active' : '' ?>">Users</a>
            <a href="?view=threads" class="nav-btn <?= $view === 'threads' ? 'active' : '' ?>">Threads</a>
            <a href="?view=posts" class="nav-btn <?= $view === 'posts' ? 'active' : '' ?>">Posts</a>
            <a href="?view=notifications" class="nav-btn <?= $view === 'notifications' ? 'active' : '' ?>">Notifications</a>
            <a href="?view=tags" class="nav-btn <?= $view === 'tags' ? 'active' : '' ?>">Tags</a>
        </div>

        <?php if ($view === 'stats'): ?>
            <!-- Statistics View -->
            <div class="stats-grid">
                <?php
                $user_count = $db->querySingle('SELECT COUNT(*) FROM users');
                $thread_count = $db->querySingle('SELECT COUNT(*) FROM threads WHERE is_deleted = 0');
                $post_count = $db->querySingle('SELECT COUNT(*) FROM posts WHERE is_deleted = 0');
                $notification_count = $db->querySingle('SELECT COUNT(*) FROM notifications');
                $tag_count = $db->querySingle('SELECT COUNT(*) FROM tags');
                $deleted_threads = $db->querySingle('SELECT COUNT(*) FROM threads WHERE is_deleted = 1');
                $deleted_posts = $db->querySingle('SELECT COUNT(*) FROM posts WHERE is_deleted = 1');
                ?>
                <div class="stat-card">
                    <div class="stat-number"><?= $user_count ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $thread_count ?></div>
                    <div class="stat-label">Active Threads</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $post_count ?></div>
                    <div class="stat-label">Active Posts</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $notification_count ?></div>
                    <div class="stat-label">Notifications</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $tag_count ?></div>
                    <div class="stat-label">Tags</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $deleted_threads ?></div>
                    <div class="stat-label">Deleted Threads</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $deleted_posts ?></div>
                    <div class="stat-label">Deleted Posts</div>
                </div>
            </div>

        <?php elseif ($view === 'users'): ?>
            <!-- Users View -->
            <div class="data-table">
                <div class="table-header">
                    <h3>Users Management</h3>
                </div>
                <div class="table-content">
                    <?php
                    $users = $db->query('SELECT * FROM users ORDER BY created_at DESC');
                    while ($user = $users->fetchArray(SQLITE3_ASSOC)):
                    ?>
                    <form method="post" class="data-row users-row">
                        <input type="hidden" name="action" value="edit_user">
                        <input type="hidden" name="id" value="<?= $user['id'] ?>">
                        
                        <div class="data-field"><?= $user['id'] ?></div>
                        <div class="data-field">
                            <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
                        </div>
                        <div class="data-field">
                            <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>
                        <div class="data-field"><?= date('M j, Y g:i A', strtotime($user['created_at'])) ?></div>
                        <div class="data-field">
                            <?= is_admin($user['username']) ? '<span style="color: #ff6b6b;">ADMIN</span>' : 'USER' ?>
                        </div>
                        <div class="data-field">
                            <button type="submit" class="action-btn">Update</button>
                            <button type="submit" name="action" value="delete_user" class="action-btn delete-btn" onclick="return confirm('Delete this user?')">Delete</button>
                        </div>
                    </form>
                    <?php endwhile; ?>
                </div>
            </div>

        <?php elseif ($view === 'threads'): ?>
            <!-- Threads View -->
            <div class="data-table">
                <div class="table-header">
                    <h3>Threads Management</h3>
                </div>
                
                <!-- Multi-select controls -->
                <div class="multi-select-controls">
                    <button type="button" class="select-all-btn" onclick="toggleSelectAll()">Select All</button>
                    <form method="post" style="display: inline;" onsubmit="return confirmMultiDelete()">
                        <input type="hidden" name="action" value="delete_multiple_threads">
                        <button type="submit" class="delete-selected-btn">Delete Selected (Permanent)</button>
                        <span id="selected-count" style="color: #ccc; margin-left: 1rem;">0 selected</span>
                    </form>
                </div>
                
                <div class="table-content">
                    <?php
                    $threads = $db->query('SELECT * FROM threads ORDER BY created_at DESC');
                    while ($thread = $threads->fetchArray(SQLITE3_ASSOC)):
                    ?>
                    <div class="data-row threads-row" style="grid-template-columns: 30px 50px 2fr 1fr 150px 100px;">
                        <!-- Multi-select checkbox -->
                        <div class="data-field">
                            <input type="checkbox" class="thread-checkbox" name="selected_threads[]" value="<?= $thread['id'] ?>" onchange="updateSelectedCount()">
                        </div>
                        
                        <!-- Individual thread form -->
                        <form method="post" style="display: contents;">
                            <input type="hidden" name="action" value="edit_thread">
                            <input type="hidden" name="id" value="<?= $thread['id'] ?>">
                            
                            <div class="data-field"><?= $thread['id'] ?></div>
                            <div class="data-field">
                                <input type="text" name="title" value="<?= htmlspecialchars($thread['title']) ?>" required>
                            </div>
                            <div class="data-field">
                                <input type="text" name="username" value="<?= htmlspecialchars($thread['username']) ?>" required>
                            </div>
                            <div class="data-field"><?= date('M j, Y g:i A', strtotime($thread['created_at'])) ?></div>
                            <div class="data-field">
                                <button type="submit" class="action-btn">Update</button>
                                <button type="submit" name="action" value="delete_thread" class="action-btn delete-btn" onclick="return confirm('Permanently delete this thread and all its posts?')">Delete</button>
                            </div>
                        </form>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>

        <?php elseif ($view === 'posts'): ?>
            <!-- Posts View -->
            <div class="data-table">
                <div class="table-header">
                    <h3>Posts Management</h3>
                </div>
                <div class="table-content">
                    <?php
                    $posts = $db->query('SELECT p.*, t.title as thread_title FROM posts p LEFT JOIN threads t ON p.thread_id = t.id ORDER BY p.created_at DESC');
                    while ($post = $posts->fetchArray(SQLITE3_ASSOC)):
                    ?>
                    <form method="post" class="data-row posts-row">
                        <input type="hidden" name="action" value="edit_post">
                        <input type="hidden" name="id" value="<?= $post['id'] ?>">
                        
                        <div class="data-field"><?= $post['id'] ?></div>
                        <div class="data-field">
                            <textarea name="content" required><?= htmlspecialchars($post['content']) ?></textarea>
                            <small>Thread: <?= htmlspecialchars($post['thread_title'] ?? 'Unknown') ?></small>
                        </div>
                        <div class="data-field">
                            <input type="text" name="username" value="<?= htmlspecialchars($post['username']) ?>" required>
                        </div>
                        <div class="data-field"><?= date('M j, Y g:i A', strtotime($post['created_at'])) ?></div>
                        <div class="data-field">
                            <button type="submit" class="action-btn">Update</button>
                            <button type="submit" name="action" value="delete_post" class="action-btn delete-btn" onclick="return confirm('Permanently delete this post?')">Delete</button>
                        </div>
                    </form>
                    <?php endwhile; ?>
                </div>
            </div>

        <?php elseif ($view === 'notifications'): ?>
            <!-- Notifications View -->
            <div class="data-table">
                <div class="table-header">
                    <h3>Notifications Management</h3>
                </div>
                <div class="table-content">
                    <?php
                    $notifications = $db->query('SELECT n.*, u.username FROM notifications n LEFT JOIN users u ON n.user_id = u.id ORDER BY n.created_at DESC');
                    while ($notification = $notifications->fetchArray(SQLITE3_ASSOC)):
                    ?>
                    <div class="data-row notifications-row">
                        <div class="data-field"><?= $notification['id'] ?></div>
                        <div class="data-field"><?= htmlspecialchars($notification['username'] ?? 'Unknown User') ?></div>
                        <div class="data-field"><?= htmlspecialchars($notification['message']) ?></div>
                        <div class="data-field"><?= date('M j, Y g:i A', strtotime($notification['created_at'])) ?></div>
                        <div class="data-field">
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="action" value="clear_notifications">
                                <input type="hidden" name="user_id" value="<?= $notification['user_id'] ?>">
                                <button type="submit" class="action-btn delete-btn" onclick="return confirm('Clear all notifications for this user?')">Clear User</button>
                            </form>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>

        <?php elseif ($view === 'tags'): ?>
            <!-- Tags View -->
            <div class="data-table">
                <div class="table-header">
                    <h3>Tags Management</h3>
                </div>
                <div class="table-content">
                    <?php
                    $tags = $db->query('
                        SELECT t.*, 
                               COUNT(DISTINCT tt.thread_id) as thread_count,
                               COUNT(DISTINCT pt.post_id) as post_count
                        FROM tags t 
                        LEFT JOIN thread_tags tt ON t.id = tt.tag_id 
                        LEFT JOIN post_tags pt ON t.id = pt.tag_id 
                        GROUP BY t.id 
                        ORDER BY t.name
                    ');
                    while ($tag = $tags->fetchArray(SQLITE3_ASSOC)):
                    ?>
                    <div class="data-row" style="grid-template-columns: 50px 2fr 100px 100px 150px;">
                        <div class="data-field"><?= $tag['id'] ?></div>
                        <div class="data-field"><?= htmlspecialchars($tag['name']) ?></div>
                        <div class="data-field"><?= $tag['thread_count'] ?> threads</div>
                        <div class="data-field"><?= $tag['post_count'] ?> posts</div>
                        <div class="data-field"><?= date('M j, Y', strtotime($tag['created_at'])) ?></div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        <?php endif; ?>

        <div style="text-align: center; margin-top: 2rem;">
            <a href="forum.php" class="nav-btn">← Back to Forum</a>
        </div>
    </div>

    <footer>
        &copy; <?= date("Y") ?> Xenon Forum - Admin Dashboard
    </footer>

    <script>
        async function loadHeaders() {
            const res = await fetch('headers.php');
            const text = await res.text();
            document.getElementById('headers').innerHTML = text;
        }
        loadHeaders();
        
        // Multi-select functionality
        function toggleSelectAll() {
            const checkboxes = document.querySelectorAll('.thread-checkbox');
            const selectAllBtn = document.querySelector('.select-all-btn');
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            
            checkboxes.forEach(cb => {
                cb.checked = !allChecked;
            });
            
            selectAllBtn.textContent = allChecked ? 'Select All' : 'Deselect All';
            updateSelectedCount();
        }
        
        function updateSelectedCount() {
            const checkboxes = document.querySelectorAll('.thread-checkbox:checked');
            const count = checkboxes.length;
            const countElement = document.getElementById('selected-count');
            const deleteBtn = document.querySelector('.delete-selected-btn');
            
            if (countElement) {
                countElement.textContent = count + ' selected';
            }
            
            if (deleteBtn) {
                deleteBtn.disabled = count === 0;
                deleteBtn.style.opacity = count === 0 ? '0.5' : '1';
            }
            
            // Update select all button text
            const selectAllBtn = document.querySelector('.select-all-btn');
            const allCheckboxes = document.querySelectorAll('.thread-checkbox');
            const allChecked = Array.from(allCheckboxes).every(cb => cb.checked);
            
            if (selectAllBtn) {
                selectAllBtn.textContent = allChecked && allCheckboxes.length > 0 ? 'Deselect All' : 'Select All';
            }
        }
        
        function confirmMultiDelete() {
            const checkboxes = document.querySelectorAll('.thread-checkbox:checked');
            const count = checkboxes.length;
            
            if (count === 0) {
                alert('Please select at least one thread to delete.');
                return false;
            }
            
            return confirm(`Are you sure you want to permanently delete ${count} thread(s) and all their posts? This action cannot be undone.`);
        }
        
        // Add selected thread IDs to the form when submitting
        document.addEventListener('DOMContentLoaded', function() {
            const deleteForm = document.querySelector('form[onsubmit="return confirmMultiDelete()"]');
            if (deleteForm) {
                deleteForm.addEventListener('submit', function(e) {
                    // Remove existing hidden inputs for selected threads
                    const existingInputs = deleteForm.querySelectorAll('input[name="selected_threads[]"]');
                    existingInputs.forEach(input => input.remove());
                    
                    // Add current selections
                    const checkboxes = document.querySelectorAll('.thread-checkbox:checked');
                    checkboxes.forEach(cb => {
                        const hiddenInput = document.createElement('input');
                        hiddenInput.type = 'hidden';
                        hiddenInput.name = 'selected_threads[]';
                        hiddenInput.value = cb.value;
                        deleteForm.appendChild(hiddenInput);
                    });
                });
            }
            
            // Initialize count
            updateSelectedCount();
        });
    </script>
</body>
</html>