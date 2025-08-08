<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database path - use absolute path for Apache
$db_path = __DIR__ . '/db.sqlite';

try {
    $db = new SQLite3($db_path);
    echo "<!-- Database connection successful -->\n";
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Create new thread
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['title'])) {
    $title = trim($_POST['title']);
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Anonymous';
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    echo "<!-- Debug: Title='$title', User='$username', IP='$ip_address' -->\n";
    
    if (!empty($title)) {
        try {
            $stmt = $db->prepare('INSERT INTO threads (title, user_id, username, ip_address) VALUES (:title, :user_id, :username, :ip_address)');
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $db->lastErrorMsg());
            }
            
            $stmt->bindValue(':title', $title, SQLITE3_TEXT);
            $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
            $stmt->bindValue(':username', $username, SQLITE3_TEXT);
            $stmt->bindValue(':ip_address', $ip_address, SQLITE3_TEXT);
            
            $result = $stmt->execute();
            if (!$result) {
                throw new Exception("Execute failed: " . $db->lastErrorMsg());
            }
            
            echo "<!-- Debug: Thread created successfully with ID " . $db->lastInsertRowID() . " -->\n";
            header('Location: forum_debug.php');
            exit;
        } catch (Exception $e) {
            echo "<!-- Error creating thread: " . $e->getMessage() . " -->\n";
        }
    }
}

try {
    $threads = $db->query('SELECT * FROM threads ORDER BY created_at DESC');
    if (!$threads) {
        throw new Exception("Query failed: " . $db->lastErrorMsg());
    }
} catch (Exception $e) {
    die("Error fetching threads: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Xenon Forum - Debug Version</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <!-- Headers will be loaded here -->
  <div id="headers"></div>
  
  <header>
    <h1>Xenon Forum (Debug)</h1>
    <p>Discuss and Share</p>
  </header>

  <div class="new-thread-box">
    <?php if (isset($_SESSION['user_id'])): ?>
      <p style="color: #ccc; margin-bottom: 1rem;">Creating thread as: <strong style="color: #00ffe1;"><?= htmlspecialchars($_SESSION['username']) ?></strong></p>
    <?php else: ?>
      <p style="color: #888; margin-bottom: 1rem; font-size: 0.9rem;">You are creating a thread anonymously. <a href="auth.php" style="color: #00ffe1;">Login</a> to post with your username.</p>
    <?php endif; ?>
    
    <form method="post" style="display: flex; flex-direction: column; gap: 1rem;">
      <input name="title" type="text" placeholder="New thread title" required style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.5); border: 1px solid #00ffe1; border-radius: 5px; color: #fff; font-family: 'Arial', sans-serif; font-size: 14px; box-sizing: border-box;">
      <button type="submit" style="width: 200px; padding: 1rem 2rem; background: #00ffe1; color: #000; border: none; border-radius: 5px; font-family: 'Orbitron', sans-serif; font-weight: bold; font-size: 14px; cursor: pointer; align-self: center; transition: background 0.3s;">Create Thread</button>
    </form>
  </div>  

  <section class="features">
    <?php 
    $thread_count = 0;
    while ($row = $threads->fetchArray(SQLITE3_ASSOC)): 
        $thread_count++;
    ?>
      <div class="feature">
        <h3>
          <a href="thread.php?id=<?= $row['id'] ?>" style="color:#00ffe1">
            <?= htmlspecialchars($row['title']) ?>
          </a>
        </h3>
        <p>Started by <strong><?= htmlspecialchars($row['username']) ?></strong></p>
        <p style="font-size: 0.8rem; color: #888;">Created: <?= date('M j, Y \a\t g:i A', strtotime($row['created_at'])) ?></p>
        <p style="font-size: 0.7rem; color: #666;">IP: <?= htmlspecialchars($row['ip_address'] ?? 'N/A') ?></p>
      </div>
    <?php endwhile; ?>
    
    <?php if ($thread_count === 0): ?>
      <div class="feature">
        <h3>No threads yet</h3>
        <p>Be the first to create a thread!</p>
      </div>
    <?php endif; ?>
  </section>

  <!-- Debug info -->
  <div style="background: rgba(0,0,0,0.8); color: #ccc; padding: 1rem; margin: 2rem; border-radius: 5px; font-family: monospace; font-size: 12px;">
    <h4 style="color: #00ffe1;">Debug Information:</h4>
    <p>Database path: <?= $db_path ?></p>
    <p>Database exists: <?= file_exists($db_path) ? 'Yes' : 'No' ?></p>
    <p>Database writable: <?= is_writable($db_path) ? 'Yes' : 'No' ?></p>
    <p>Directory writable: <?= is_writable(__DIR__) ? 'Yes' : 'No' ?></p>
    <p>PHP SQLite3 loaded: <?= extension_loaded('sqlite3') ? 'Yes' : 'No' ?></p>
    <p>Thread count: <?= $thread_count ?></p>
    <p>Session ID: <?= session_id() ?></p>
    <p>User logged in: <?= isset($_SESSION['user_id']) ? 'Yes (' . $_SESSION['username'] . ')' : 'No' ?></p>
  </div>

  <footer>
    &copy; <?= date("Y") ?> Xenon Forum
  </footer>

  <script>
    async function loadHeaders() {
      const res = await fetch('headers.html');
      const text = await res.text();
      document.getElementById('headers').innerHTML = text;
    }
    loadHeaders();
  </script>

</body>
</html>