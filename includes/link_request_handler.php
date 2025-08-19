<?php
session_start();
require_once 'includes/link_requests.php';

if (isset($_POST['action'])) {
    $action = $_POST['action'];
    $userId = $_SESSION['user_id']; // Assume you store the user ID in session

    if ($action === 'send_request') {
        $receiverId = $_POST['receiver_id'];
        sendLinkRequest($userId, $receiverId);
    } elseif ($action === 'get_received_requests') {
        $requests = getReceivedRequests($userId);
        echo json_encode($requests);
    } elseif ($action === 'get_sent_requests') {
        $requests = getSentRequests($userId);
        echo json_encode($requests);
    }
}
?>
