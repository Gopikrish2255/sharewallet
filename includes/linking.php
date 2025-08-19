<?php
require_once 'database.php';

/**
 * Function to link a user account.
 * 
 * @param int $userId The ID of the user who is linking another user.
 * @param int $linkedUserId The ID of the user being linked.
 * @return bool Returns true on success, false on failure.
 */
function linkAccount($userId, $linkedUserId) {
    global $pdo;

    // Check if the link already exists
    $checkStmt = $pdo->prepare("SELECT * FROM linked_accounts WHERE user_id = ? AND linked_user_id = ?");
    $checkStmt->execute([$userId, $linkedUserId]);

    if ($checkStmt->rowCount() > 0) {
        return false; // Link already exists
    }

    $stmt = $pdo->prepare("INSERT INTO linked_accounts (user_id, linked_user_id, requested_at) VALUES (?, ?, NOW())");
    return $stmt->execute([$userId, $linkedUserId]);
}

/**
 * Function to get all linked accounts for a user.
 * 
 * @param int $userId The ID of the user.
 * @return array An associative array of linked accounts.
 */
function getLinkedAccounts($userId) {
    global $pdo;

    $stmt = $pdo->prepare("SELECT la.*, u.FirstName, u.LastName 
                            FROM linked_accounts la 
                            JOIN tbluser u ON la.linked_user_id = u.user_id 
                            WHERE la.user_id = ?");
    $stmt->execute([$userId]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Function to search for users based on their names.
 * 
 * @param string $searchTerm The name to search for.
 * @param int $currentUserId The ID of the currently logged-in user.
 * @return array An associative array of matching users.
 */
function searchUsers($searchTerm, $currentUserId) {
    global $pdo;

    $stmt = $pdo->prepare("SELECT user_id, FirstName, LastName 
                            FROM tbluser 
                            WHERE (FirstName LIKE ? OR LastName LIKE ?) 
                            AND user_id != ?");
    $searchTermWithWildcards = '%' . $searchTerm . '%';
    $stmt->execute([$searchTermWithWildcards, $searchTermWithWildcards, $currentUserId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
