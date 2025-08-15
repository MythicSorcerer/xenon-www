<?php
session_start();
require_once 'admin_config.php';
require_once 'db_init.php';

// Initialize database with automatic table creation
$db = getDatabaseConnection();
$notification_count = 0;
$user_font_preference = 'orbitron'; // Default font
$user_theme_preference = 'dark'; // Default theme

// Get notification count, font preference, and theme preference
if (isset($_SESSION['user_id'])) {
    // Logged-in user: get from database
    try {
        $stmt = $db->prepare('SELECT COUNT(*) as count FROM notifications WHERE user_id = :user_id AND is_read = 0');
        $stmt->bindValue(':user_id', $_SESSION['user_id'], SQLITE3_INTEGER);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        $notification_count = (int)$row['count'];
        
        // Get user preferences from database
        $prefs_stmt = $db->prepare('SELECT font_preference, theme_preference FROM users WHERE id = :user_id');
        $prefs_stmt->bindValue(':user_id', $_SESSION['user_id'], SQLITE3_INTEGER);
        $prefs_result = $prefs_stmt->execute();
        $prefs_row = $prefs_result->fetchArray(SQLITE3_ASSOC);
        $user_font_preference = $prefs_row['font_preference'] ?? 'orbitron';
        $user_theme_preference = $prefs_row['theme_preference'] ?? 'dark';
    } catch (Exception $e) {
        // If database fails, keep defaults
        error_log("Header data error: " . $e->getMessage());
    }
} else {
    // Anonymous user: get preferences from cookies
    $user_font_preference = $_COOKIE['font_preference'] ?? 'orbitron';
    $user_theme_preference = $_COOKIE['theme_preference'] ?? 'dark';
}
?>
<?php if ($user_theme_preference === 'light'): ?>
<link rel="stylesheet" href="light-theme.css">
<?php endif; ?>
<style>
/* Dynamic font application based on user preference */
<?php if ($user_font_preference === 'arial'): ?>
* {
    font-family: 'Arial', sans-serif !important;
}
body, html, input, textarea, button, select, h1, h2, h3, h4, h5, h6, p, div, span, a, label {
    font-family: 'Arial', sans-serif !important;
}
.nav-btn, .action-btn, .save-btn, .delete-btn, .select-all-btn, .delete-selected-btn, .create-post-title-input, .create-post-tags-input {
    font-family: 'Arial', sans-serif !important;
}
<?php elseif ($user_font_preference === 'times'): ?>
* {
    font-family: 'Times New Roman', serif !important;
}
body, html, input, textarea, button, select, h1, h2, h3, h4, h5, h6, p, div, span, a, label {
    font-family: 'Times New Roman', serif !important;
}
.nav-btn, .action-btn, .save-btn, .delete-btn, .select-all-btn, .delete-selected-btn, .create-post-title-input, .create-post-tags-input {
    font-family: 'Times New Roman', serif !important;
}
<?php else: ?>
* {
    font-family: 'Orbitron', sans-serif !important;
}
body, html, input, textarea, button, select, h1, h2, h3, h4, h5, h6, p, div, span, a, label {
    font-family: 'Orbitron', sans-serif !important;
}
.nav-btn, .action-btn, .save-btn, .delete-btn, .select-all-btn, .delete-selected-btn, .create-post-title-input, .create-post-tags-input {
    font-family: 'Orbitron', sans-serif !important;
}
<?php endif; ?>
</style>
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
  <span id="admin-info">
    <?php if (isset($_SESSION['user_id']) && isCurrentUserAdmin($db, $_SESSION['user_id'])): ?>
      <a href="admin_dashboard.php" style="background: rgba(255, 107, 107, 0.2); border: 1px solid #ff6b6b; border-radius: 3px; color: #ff6b6b; text-decoration: none; padding: 0.2rem 0.4rem; font-size: 0.8rem; font-weight: bold;">⚙️ Admin Dashboard</a>
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
      | <a href="settings.php" style="color: #00ffe1; text-decoration: none;">Settings</a>
      | <a href="auth.php?logout=1" style="color: #ff6b6b; text-decoration: none;">Logout</a>
    <?php else: ?>
      👤 <a href="auth.php" style="color: #00ffe1; text-decoration: none;">Login</a>
      | <a href="settings.php" style="color: #00ffe1; text-decoration: none;">Settings</a>
    <?php endif; ?>
  </span>
</div>

<nav>
  <a href="index.html">Info</a>
  <a href="news.php">News</a>
  <a href="events.php">Events</a>
  <a href="forum.php">Forum</a>
  <!-- <a href="search.php">Search</a> -->
  <a href="faq.php">FAQ</a>
  <a href="support.php">Support</a>
  <a href="rules.php">Rules</a>
  <a href="status.php">Server Status</a>
</nav>