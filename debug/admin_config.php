<?php
// Admin Configuration File
// Add usernames to this array to grant admin privileges

$admin_usernames = [
    'admin',
    'modmaster'
];

// Function to check if a username is admin
function is_admin($username) {
    global $admin_usernames;
    return in_array($username, $admin_usernames);
}

// Function to check if current user is admin by user ID
function isCurrentUserAdmin($db, $user_id) {
    if (!$user_id) return false;
    
    $stmt = $db->prepare('SELECT username FROM users WHERE id = :user_id');
    $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $user = $result->fetchArray(SQLITE3_ASSOC);
    
    return $user && is_admin($user['username']);
}
?>