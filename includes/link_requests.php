<?php
require_once 'database.php';

// Function to send a link request
function sendLinkRequest($senderId, $receiverId) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO link_requests (sender_id, receiver_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $senderId, $receiverId);
    return $stmt->execute();
}

// Fetch all requests sent to a user
function getReceivedRequests($userId) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM link_requests WHERE receiver_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Fetch all requests sent by a user
function getSentRequests($userId) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM link_requests WHERE sender_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
