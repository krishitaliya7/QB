<?php
include 'session.php';
include 'db_connect.php';

header('Content-Type: application/json');

echo json_encode([
    'logged_in' => isLoggedIn(),
    'username' => getUsername(),
    'user_id' => getUserId()
]);
?>
