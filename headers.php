<?php
session_start();
require_once 'admin_config.php';

// Use absolute path for database
$db_path = __DIR__ . '/db.sqlite';
$notification_count = 0;

// Get notification count if user is logged in
if (isset($_SESSION['user_id'])) {
    try {
        $db = new SQLite3($db_path);
        $stmt = $db->prepare('SELECT COUNT(*) as count FROM notifications WHERE user_id = :user_id AND is_read = 0');
        $stmt->bindValue(':user_id', $_SESSION['user_id'], SQLITE3_INTEGER);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        $notification_count = (int)$row['count'];
        $db->close();
    } catch (Exception $e) {
        // If database fails, keep count at 0
        error_log("Notification count error: " . $e->getMessage());
    }
}
?>
<div class="top-bar">
  <span id="notifications-info">
    <?php if (isset($_SESSION['user_id'])): ?>
      <?php if ($notification_count > 0): ?>
        <a href="notifications.php" style="color: #00ffe1; text-decoration: none;">🔔 (<?= $notification_count ?>)</a>
      <?php else: ?>
        <a href="notifications.php" style="color: #ccc; text-decoration: none;">🔔</a>
      <?php endif; ?>
    <?php else: ?>
      <span style="color: #666;">🔔</span>
    <?php endif; ?>
  </span>
  <span id="user-info">
    <?php if (isset($_SESSION['user_id']) && isset($_SESSION['username'])): ?>
      <?php
      // Check if current user is admin using admin config
      $header_is_admin = is_admin($_SESSION['username']);
      ?>
      👤 <strong style="color: #00ffe1;"><?= htmlspecialchars($_SESSION['username']) ?></strong>
      <?php if ($header_is_admin): ?>
        <span style="background: #ff6b6b; color: white; padding: 0.2rem 0.4rem; border-radius: 3px; font-size: 0.7rem; font-weight: bold; margin-left: 0.3rem;">ADMIN</span>
      <?php endif; ?>
      | <a href="auth.php?logout=1" style="color: #ff6b6b; text-decoration: none;">Logout</a>
    <?php else: ?>
      👤 <a href="auth.php" style="color: #00ffe1; text-decoration: none;">Login</a>
    <?php endif; ?>
  </span>
</div>

<nav>
  <a href="index.html">Info</a>
  <a href="news.php">News</a>
  <a href="events.php">Events</a>
  <a href="forum.php">Forum</a>
  <a href="faq.php">FAQ</a>
  <a href="support.php">Support</a>
  <a href="rules.php">Rules</a>
  <a href="status.php">Server Status</a>
</nav>