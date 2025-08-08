<?php
session_start();
header('Content-Type: application/json');

$response = array('count' => 0);

if (isset($_SESSION['user_id'])) {
    // Use absolute path for database to work with Apache
    $db_path = __DIR__ . '/db.sqlite';
    
    try {
        $db = new SQLite3($db_path);
        $stmt = $db->prepare('SELECT COUNT(*) as count FROM notifications WHERE user_id = :user_id AND is_read = 0');
        $stmt->bindValue(':user_id', $_SESSION['user_id'], SQLITE3_INTEGER);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        $response['count'] = (int)$row['count'];
        $db->close();
    } catch (Exception $e) {
        // If database fails, return 0 count
        error_log("Notification count error: " . $e->getMessage());
    }
}

echo json_encode($response);
?>