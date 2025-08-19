<?php
require_once 'includes/database.php';
require_once 'includes/functions.php';

function processAutoExpenses() {
    global $conn;
    $today = date('Y-m-d');
    
    // Fetch all auto expenses due today or earlier
    $stmt = $conn->prepare("SELECT * FROM auto_expenses WHERE NextDueDate <= ?");
    $stmt->bind_param("s", $today);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($expense = $result->fetch_assoc()) {
        // Add the expense to tblexpense
        $stmt = $conn->prepare("INSERT INTO tblexpense (UserId, ExpenseDate, ExpenseItem, ExpenseCost, CategoryID) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issdi", $expense['UserId'], $today, $expense['ExpenseItem'], $expense['ExpenseCost'], $expense['CategoryID']);
        $stmt->execute();
        
        // Update the NextDueDate for the auto expense
        $nextDueDate = calculateNextDueDate($expense['NextDueDate'], $expense['Frequency']);
        $stmt = $conn->prepare("UPDATE auto_expenses SET NextDueDate = ? WHERE ID = ?");
        $stmt->bind_param("si", $nextDueDate, $expense['ID']);
        $stmt->execute();
    }
}

function calculateNextDueDate($currentDueDate, $frequency) {
    $date = new DateTime($currentDueDate);
    
    if ($frequency === 'monthly') {
        $date->modify('+1 month');
    } elseif ($frequency === 'yearly') {
        $date->modify('+1 year');
    }
    
    return $date->format('Y-m-d');
}

processAutoExpenses();
echo "Auto expenses processed successfully.";