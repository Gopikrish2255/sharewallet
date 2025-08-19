<?php
// includes/summary_management.php

require_once 'database.php'; // Ensure this file contains your DB connection code

// Function to add a daily summary
function addDailySummary(int $userId, string $summaryDate, float $totalExpense): bool {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO tblsummary_daily (UserId, SummaryDate, TotalExpense) VALUES (:userId, :summaryDate, :totalExpense)");
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':summaryDate', $summaryDate, PDO::PARAM_STR);
        $stmt->bindParam(':totalExpense', $totalExpense, PDO::PARAM_STR);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Error adding daily summary: " . $e->getMessage());
        return false;
    }
}

// Function to generate daily summary for a user
function generateDailySummary(int $userId): bool {
    global $pdo;
    $today = date('Y-m-d');
    $totalExpense = 0;

    // Calculate total expenses for today
    $expenses = getExpenses($userId); // Assuming you have a function getExpenses
    foreach ($expenses as $expense) {
        if ($expense['ExpenseDate'] == $today) {
            $totalExpense += floatval($expense['ExpenseCost']);
        }
    }

    // Save the daily summary
    return addDailySummary($userId, $today, $totalExpense);
}

// Function to retrieve daily summary for a user
function getDailySummary(int $userId): array {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM tblsummary_daily WHERE UserId = :userId ORDER BY SummaryDate DESC");
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error retrieving daily summaries: " . $e->getMessage());
        return [];
    }
}
?>
