<?php
// Database update script to add IP-based cooldown tracking
$db = new SQLite3('db.sqlite');

// Create IP cooldown tracking table
try {
    $db->exec('CREATE TABLE IF NOT EXISTS ip_cooldowns (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        ip_address TEXT NOT NULL,
        last_post_time DATETIME NOT NULL,
        UNIQUE(ip_address)
    )');
    echo "Created ip_cooldowns table.<br>";
} catch (Exception $e) {
    echo "ip_cooldowns table creation error: " . $e->getMessage() . "<br>";
}

echo "IP-based cooldown tracking added successfully!<br>";
echo "Anonymous users will now have 30-second cooldown based on IP address.<br>";

$db->close();
?>