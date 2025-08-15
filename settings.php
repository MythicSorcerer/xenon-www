<?php
session_start();
require_once 'admin_config.php';
require_once 'db_init.php';

$db = getDatabaseConnection();
$message = '';
$error = '';
$is_logged_in = isset($_SESSION['user_id']);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $font_preference = $_POST['font_preference'] ?? null;
        $theme_preference = $_POST['theme_preference'] ?? null;
        
        // Validate preferences
        if ($font_preference && !in_array($font_preference, ['orbitron', 'arial', 'times'])) {
            throw new Exception('Invalid font preference');
        }
        if ($theme_preference && !in_array($theme_preference, ['dark', 'light'])) {
            throw new Exception('Invalid theme preference');
        }
        
        if ($is_logged_in) {
            // Save to database for logged-in users
            $updates = [];
            $params = [':user_id' => $_SESSION['user_id']];
            
            if ($font_preference) {
                $updates[] = 'font_preference = :font_preference';
                $params[':font_preference'] = $font_preference;
            }
            if ($theme_preference) {
                $updates[] = 'theme_preference = :theme_preference';
                $params[':theme_preference'] = $theme_preference;
            }
            
            if (!empty($updates)) {
                $sql = 'UPDATE users SET ' . implode(', ', $updates) . ' WHERE id = :user_id';
                $stmt = $db->prepare($sql);
                foreach ($params as $key => $value) {
                    $stmt->bindValue($key, $value, SQLITE3_TEXT);
                }
                $stmt->execute();
                
                $message = 'Preferences updated successfully!';
            }
        } else {
            // Save to cookies for anonymous users (expires in 1 year)
            if ($font_preference) {
                setcookie('font_preference', $font_preference, time() + (365 * 24 * 60 * 60), '/');
                $_COOKIE['font_preference'] = $font_preference; // Set for immediate use
            }
            if ($theme_preference) {
                setcookie('theme_preference', $theme_preference, time() + (365 * 24 * 60 * 60), '/');
                $_COOKIE['theme_preference'] = $theme_preference; // Set for immediate use
            }
            
            $message = 'Preferences saved! Your preferences will be remembered on this device.';
        }
    } catch (Exception $e) {
        $error = 'Error updating settings: ' . $e->getMessage();
    }
}

// Get current preferences
$current_font = 'orbitron'; // Default
$current_theme = 'dark'; // Default

if ($is_logged_in) {
    // Get from database for logged-in users
    $stmt = $db->prepare('SELECT font_preference, theme_preference FROM users WHERE id = :user_id');
    $stmt->bindValue(':user_id', $_SESSION['user_id'], SQLITE3_INTEGER);
    $result = $stmt->execute();
    $user_settings = $result->fetchArray(SQLITE3_ASSOC);
    $current_font = $user_settings['font_preference'] ?? 'orbitron';
    $current_theme = $user_settings['theme_preference'] ?? 'dark';
} else {
    // Get from cookies for anonymous users
    $current_font = $_COOKIE['font_preference'] ?? 'orbitron';
    $current_theme = $_COOKIE['theme_preference'] ?? 'dark';
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>User Settings - Xenon Forum</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500&display=swap" rel="stylesheet" />
    <style>
        .settings-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .settings-card {
            background: rgba(0, 255, 225, 0.1);
            border: 1px solid #00ffe1;
            border-radius: 10px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .settings-card h3 {
            color: #00ffe1;
            margin-top: 0;
            margin-bottom: 1.5rem;
        }
        .setting-group {
            margin-bottom: 2rem;
        }
        .setting-label {
            color: #ccc;
            font-size: 1rem;
            margin-bottom: 0.5rem;
            display: block;
        }
        .setting-description {
            color: #888;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            line-height: 1.4;
        }
        .font-options {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .font-option {
            background: rgba(0, 0, 0, 0.3);
            border: 2px solid #444;
            border-radius: 8px;
            padding: 1.5rem;
            cursor: pointer;
            transition: all 0.3s;
            flex: 1;
            min-width: 200px;
            text-align: center;
        }
        .font-option:hover {
            border-color: #00ffe1;
            background: rgba(0, 255, 225, 0.1);
        }
        .font-option.selected {
            border-color: #00ffe1;
            background: rgba(0, 255, 225, 0.2);
        }
        .font-option input[type="radio"] {
            display: none;
        }
        .font-preview {
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
            color: #00ffe1;
        }
        .font-preview.orbitron {
            font-family: 'Orbitron', sans-serif;
        }
        .font-preview.arial {
            font-family: 'Arial', sans-serif;
        }
        .font-preview.times {
            font-family: 'Times New Roman', serif;
        }
        .theme-preview {
            width: 100%;
            height: 60px;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.5rem;
        }
        .dark-preview {
            background: #1a1a1a;
            border: 1px solid #00ffe1;
        }
        .light-preview {
            background: #ffffff;
            border: 1px solid #333;
        }
        .theme-sample {
            font-size: 1rem;
            font-weight: bold;
        }
        .dark-preview .theme-sample {
            color: #00ffe1;
        }
        .light-preview .theme-sample {
            color: #333;
        }
        .font-name {
            color: #ccc;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        .font-description {
            color: #888;
            font-size: 0.8rem;
        }
        .save-btn {
            background: #00ffe1;
            color: #000;
            border: none;
            padding: 1rem 2rem;
            border-radius: 5px;
            font-family: 'Orbitron', sans-serif;
            font-weight: bold;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.3s;
            margin-top: 1rem;
        }
        .save-btn:hover {
            background: #00ccb8;
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
        <h1>Xenon Settings</h1>
        <p>User Settings</p>
    </header>

    <div class="settings-container">
        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="settings-card">
            <h3>Font Preferences</h3>
            
            <form method="post">
                <div class="setting-group">
                    <label class="setting-label">Choose your preferred font</label>
                    <div class="setting-description">
                        Select the font family you'd like to use across the forum. This setting affects the display of text throughout the site.
                        <?php if (!$is_logged_in): ?>
                            <br><strong>Note:</strong> As an anonymous user, your preference will be saved locally on this device using cookies.
                        <?php endif; ?>
                    </div>
                    
                    <div class="font-options">
                        <label class="font-option <?= $current_font === 'orbitron' ? 'selected' : '' ?>" for="font-orbitron">
                            <input type="radio" name="font_preference" value="orbitron" id="font-orbitron" <?= $current_font === 'orbitron' ? 'checked' : '' ?>>
                            <div class="font-preview orbitron">XENON FORUM</div>
                            <div class="font-name">Orbitron</div>
                            <div class="font-description">Futuristic, sci-fi inspired font. Perfect for the cyberpunk theme.</div>
                        </label>
                        
                        <label class="font-option <?= $current_font === 'arial' ? 'selected' : '' ?>" for="font-arial">
                            <input type="radio" name="font_preference" value="arial" id="font-arial" <?= $current_font === 'arial' ? 'checked' : '' ?>>
                            <div class="font-preview arial">XENON FORUM</div>
                            <div class="font-name">Arial</div>
                            <div class="font-description">Clean, readable sans-serif font. Better for extended reading.</div>
                        </label>
                        
                        <label class="font-option <?= $current_font === 'times' ? 'selected' : '' ?>" for="font-times">
                            <input type="radio" name="font_preference" value="times" id="font-times" <?= $current_font === 'times' ? 'checked' : '' ?>>
                            <div class="font-preview times">XENON FORUM</div>
                            <div class="font-name">Times New Roman</div>
                            <div class="font-description">Classic serif font. Traditional and highly readable for long texts.</div>
                        </label>
                    </div>
                </div>
                
                <div class="setting-group">
                    <label class="setting-label">Choose your preferred theme</label>
                    <div class="setting-description">
                        Select the color scheme you'd like to use across the forum.
                        <?php if (!$is_logged_in): ?>
                            <br><strong>Note:</strong> As an anonymous user, your preference will be saved locally on this device using cookies.
                        <?php endif; ?>
                    </div>
                    
                    <div class="font-options">
                        <label class="font-option <?= $current_theme === 'dark' ? 'selected' : '' ?>" for="theme-dark">
                            <input type="radio" name="theme_preference" value="dark" id="theme-dark" <?= $current_theme === 'dark' ? 'checked' : '' ?>>
                            <div class="theme-preview dark-preview">
                                <div class="theme-sample">Sample Text</div>
                            </div>
                            <div class="font-name">Dark Theme</div>
                            <div class="font-description">Cyberpunk dark theme with cyan accents. Easy on the eyes.</div>
                        </label>
                        
                        <label class="font-option <?= $current_theme === 'light' ? 'selected' : '' ?>" for="theme-light">
                            <input type="radio" name="theme_preference" value="light" id="theme-light" <?= $current_theme === 'light' ? 'checked' : '' ?>>
                            <div class="theme-preview light-preview">
                                <div class="theme-sample">Sample Text</div>
                            </div>
                            <div class="font-name">Light Theme</div>
                            <div class="font-description">Clean black text on white background. High contrast for readability.</div>
                        </label>
                    </div>
                </div>
                
                <button type="submit" class="save-btn">Save Settings</button>
            </form>
        </div>

        <div style="text-align: center;">
            <a href="forum.php" class="back-link">← Back to Forum</a>
        </div>
    </div>

    <footer>
        &copy; <?= date("Y") ?> Xenon Forum - Settings
    </footer>

    <script>
        async function loadHeaders() {
            const res = await fetch('headers.php');
            const text = await res.text();
            document.getElementById('headers').innerHTML = text;
        }
        loadHeaders();
        
        // Handle font option selection
        document.querySelectorAll('.font-option').forEach(option => {
            option.addEventListener('click', function() {
                // Remove selected class from all options
                document.querySelectorAll('.font-option').forEach(opt => opt.classList.remove('selected'));
                // Add selected class to clicked option
                this.classList.add('selected');
                // Check the radio button
                this.querySelector('input[type="radio"]').checked = true;
            });
        });
    </script>
</body>
</html>