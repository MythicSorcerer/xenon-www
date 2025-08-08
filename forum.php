<?php
session_start();
require_once 'admin_config.php';

// Use absolute path for database to work with Apache
$db_path = __DIR__ . '/db.sqlite';

try {
    $db = new SQLite3($db_path);
} catch (Exception $e) {
    die("Database connection failed. Please ensure the database file exists and is writable.");
}

// Create new thread
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['title'])) {
    $title = trim($_POST['title']);
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Anonymous';
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $cooldown_error = '';
    
    // Check cooldown for registered users (admins are exempt)
    if ($user_id) {
        $user_stmt = $db->prepare('SELECT last_post_time FROM users WHERE id = :user_id');
        $user_stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
        $user_result = $user_stmt->execute();
        $user_data = $user_result->fetchArray(SQLITE3_ASSOC);
        
        if ($user_data && !is_admin($username)) {
            // Check if 30 seconds have passed since last post
            if ($user_data['last_post_time']) {
                $last_post = strtotime($user_data['last_post_time']);
                $current_time = time();
                $time_diff = $current_time - $last_post;
                
                if ($time_diff < 30) {
                    $remaining = 30 - $time_diff;
                    $cooldown_error = "Please wait {$remaining} seconds before creating a thread.";
                }
            }
        }
    } else {
        // Check cooldown for anonymous users based on IP address
        $ip_stmt = $db->prepare('SELECT last_post_time FROM ip_cooldowns WHERE ip_address = :ip_address');
        $ip_stmt->bindValue(':ip_address', $ip_address, SQLITE3_TEXT);
        $ip_result = $ip_stmt->execute();
        $ip_data = $ip_result->fetchArray(SQLITE3_ASSOC);
        
        if ($ip_data) {
            $last_action = strtotime($ip_data['last_post_time']);
            $current_time = time();
            $time_diff = $current_time - $last_action;
            
            if ($time_diff < 30) {
                $remaining = 30 - $time_diff;
                $cooldown_error = "Please wait {$remaining} seconds before creating a thread. (Anonymous users have a 30-second cooldown)";
            }
        }
    }
    
    if (!empty($title) && empty($cooldown_error)) {
        try {
            $stmt = $db->prepare('INSERT INTO threads (title, user_id, username, ip_address) VALUES (:title, :user_id, :username, :ip_address)');
            if (!$stmt) {
                throw new Exception("Database prepare failed");
            }
            
            $stmt->bindValue(':title', $title, SQLITE3_TEXT);
            $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
            $stmt->bindValue(':username', $username, SQLITE3_TEXT);
            $stmt->bindValue(':ip_address', $ip_address, SQLITE3_TEXT);
            
            $result = $stmt->execute();
            if (!$result) {
                throw new Exception("Database execute failed");
            }
            
            // Update last post time for registered users
            if ($user_id) {
                $update_time_stmt = $db->prepare('UPDATE users SET last_post_time = CURRENT_TIMESTAMP WHERE id = :user_id');
                $update_time_stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
                $update_time_stmt->execute();
            } else {
                // Update or insert IP cooldown for anonymous users
                $ip_check_stmt = $db->prepare('SELECT id FROM ip_cooldowns WHERE ip_address = :ip_address');
                $ip_check_stmt->bindValue(':ip_address', $ip_address, SQLITE3_TEXT);
                $ip_check_result = $ip_check_stmt->execute();
                $ip_exists = $ip_check_result->fetchArray(SQLITE3_ASSOC);
                
                if ($ip_exists) {
                    $ip_update_stmt = $db->prepare('UPDATE ip_cooldowns SET last_post_time = CURRENT_TIMESTAMP WHERE ip_address = :ip_address');
                    $ip_update_stmt->bindValue(':ip_address', $ip_address, SQLITE3_TEXT);
                    $ip_update_stmt->execute();
                } else {
                    $ip_insert_stmt = $db->prepare('INSERT INTO ip_cooldowns (ip_address, last_post_time) VALUES (:ip_address, CURRENT_TIMESTAMP)');
                    $ip_insert_stmt->bindValue(':ip_address', $ip_address, SQLITE3_TEXT);
                    $ip_insert_stmt->execute();
                }
            }
            
            header('Location: forum.php');
            exit;
        } catch (Exception $e) {
            // Log error or display user-friendly message
            error_log("Forum post error: " . $e->getMessage());
        }
    }
}

try {
    $threads = $db->query('SELECT * FROM threads ORDER BY created_at DESC');
    if (!$threads) {
        throw new Exception("Failed to fetch threads");
    }
} catch (Exception $e) {
    error_log("Forum query error: " . $e->getMessage());
    $threads = false;
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Xenon Forum</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <!-- Headers will be loaded here -->
  <div id="headers"></div>
  
  <header>
    <h1>Xenon Forum</h1>
    <p>Discuss and Share</p>
  </header>

  <div class="new-thread-box">
    <?php if (isset($_SESSION['user_id'])): ?>
      <?php
      // Check if current user is admin for display
      $forum_is_admin = is_admin($_SESSION['username']);
      ?>
      <p style="color: #ccc; margin-bottom: 1rem;">Creating thread as: <strong style="color: #00ffe1;"><?= htmlspecialchars($_SESSION['username']) ?></strong>
      <?php if ($forum_is_admin): ?>
        <span style="background: #ff6b6b; color: white; padding: 0.2rem 0.4rem; border-radius: 3px; font-size: 0.7rem; font-weight: bold; margin-left: 0.3rem;">ADMIN</span>
        <span style="color: #4f4; font-size: 0.8rem; margin-left: 0.5rem;">(No cooldown)</span>
      <?php endif; ?>
      </p>
    <?php else: ?>
      <p style="color: #888; margin-bottom: 1rem; font-size: 0.9rem;">You are creating a thread anonymously. <a href="auth.php" style="color: #00ffe1;">Login</a> to post with your username.</p>
    <?php endif; ?>
    
    <?php if (!empty($cooldown_error)): ?>
      <div style="color: #ff4444; background: rgba(255, 68, 68, 0.1); border: 1px solid #ff4444; padding: 1rem; border-radius: 5px; margin-bottom: 1rem; text-align: center;">
        <?= htmlspecialchars($cooldown_error) ?>
      </div>
    <?php endif; ?>
    
    <form method="post" style="display: flex; flex-direction: column; gap: 1rem;">
      <input name="title" type="text" placeholder="New thread title" required style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.5); border: 1px solid #00ffe1; border-radius: 5px; color: #fff; font-family: 'Arial', sans-serif; font-size: 14px; box-sizing: border-box;">
      <button type="submit" style="width: 200px; padding: 1rem 2rem; background: #00ffe1; color: #000; border: none; border-radius: 5px; font-family: 'Orbitron', sans-serif; font-weight: bold; font-size: 14px; cursor: pointer; align-self: center; transition: background 0.3s;">Create Thread</button>
    </form>
  </div>

  <section class="features">
    <?php while ($row = $threads->fetchArray(SQLITE3_ASSOC)): ?>
      <?php
      // Check if thread author is admin
      $thread_author_is_admin = is_admin($row['username']);
      ?>
      <div class="feature">
        <h3>
          <a href="thread.php?id=<?= $row['id'] ?>" style="color:#00ffe1">
            <?= htmlspecialchars($row['title']) ?>
          </a>
        </h3>
        <p>Started by <strong><?= htmlspecialchars($row['username']) ?></strong>
        <?php if ($thread_author_is_admin): ?>
          <span style="background: #ff6b6b; color: white; padding: 0.2rem 0.4rem; border-radius: 3px; font-size: 0.7rem; font-weight: bold; margin-left: 0.3rem;">ADMIN</span>
        <?php endif; ?>
        </p>
        <p style="font-size: 0.8rem; color: #888;">Created: <?= date('M j, Y \a\t g:i A', strtotime($row['created_at'])) ?></p>
      </div>
    <?php endwhile; ?>
  </section>

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

