<?php
require_once 'database.php'; // Ensure this connects with mysqli
require_once 'functions.php'; // Use require_once to prevent redeclaration

// Function to get inbox requests
function getInboxRequests($userId) {
    global $conn; // Use the same mysqli connection

    // Prepare the query to retrieve inbox requests for linked accounts
    $sql = "SELECT 
                l.linked_user_id, 
                u.FirstName, 
                u.LastName, 
                l.requested_at
            FROM linked_accounts l
            JOIN tbluser u ON l.linked_user_id = u.ID
            WHERE l.user_id = ?";
    
    // Prepare and execute the statement
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch all results
    $inboxRequests = $result->fetch_all(MYSQLI_ASSOC);

    // Return inbox requests or an empty array if none found
    return $inboxRequests ?: [];
}

// Function to get monthly expenses for the current user

// Function to get categorized expenses

?>
