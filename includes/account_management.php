<?php
require_once 'database.php';

// Function to accept a linked account request
function acceptLinkedAccount($userId, $linkedUserId) {
    global $pdo;

    try {
        // Insert the link into the linked_accounts table
        $stmt = $pdo->prepare("INSERT INTO linked_accounts (user_id, linked_user_id) VALUES (?, ?)");
        $stmt->execute([$userId, $linkedUserId]);

        return true; // Return true if the operation was successful
    } catch (PDOException $e) {
        // Handle error appropriately
        return false; // Return false if there was an error
    }
}

// Function to decline a linked account request
function declineLinkedAccount($userId, $linkedUserId) {
    // In this case, since there is no pending requests table, you may want to implement
    // logic that matches your requirements, such as removing it from a UI list.
    return true; // For now, just return true
}

// Function to get linked accounts for a user
function getLinkedAccounts($userId) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("SELECT linked_user_id FROM linked_accounts WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC); // Return an array of linked user IDs
    } catch (PDOException $e) {
        // Handle error appropriately
        return []; // Return an empty array if there was an error
    }
}
?>
