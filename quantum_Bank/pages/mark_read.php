<?php
include '../includes/db_connect.php';
include '../includes/session.php';
requireLogin();

$message_id = (int)($_GET['message_id'] ?? 0);
if ($message_id > 0) {
    mark_message_read($message_id, getUserId());
    echo 'success';
} else {
    echo 'error';
}
?>
