<?php
// ============================================================
// File: socialnet/friend_action.php
// Description: Handles adding and removing friends
// ============================================================

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /socialnet/signin.php');
    exit;
}

require_once '../db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $friend_id = intval($_POST['friend_id'] ?? 0);
    $owner = $_POST['owner'] ?? '';
    $user_id = $_SESSION['user_id'];

    if ($friend_id > 0 && $user_id !== $friend_id) {
        if ($action === 'add') {
            $stmt = $conn->prepare("INSERT IGNORE INTO friendship (user_id, friend_id) VALUES (?, ?)");
            $stmt->bind_param('ii', $user_id, $friend_id);
            $stmt->execute();
            $stmt->close();
        } elseif ($action === 'delete') {
            $stmt = $conn->prepare("DELETE FROM friendship WHERE user_id = ? AND friend_id = ?");
            $stmt->bind_param('ii', $user_id, $friend_id);
            $stmt->execute();
            $stmt->close();
        }
    }
}

$conn->close();

if (!empty($owner)) {
    header('Location: /socialnet/profile.php?owner=' . urlencode($owner));
} else {
    header('Location: /socialnet/index.php');
}
exit;
