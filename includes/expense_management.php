<?php
// includes/expense_management.php

require_once 'database.php'; // Ensure this file contains your DB connection code

// Function to add a new expense
function addExpense(int $userId, string $expenseDate, string $expenseItem, float $expenseCost, int $categoryId): bool {
    global $mysqli;
    $stmt = $mysqli->prepare("INSERT INTO tblexpense (UserId, ExpenseDate, ExpenseItem, ExpenseCost, CategoryID) VALUES (?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("issdi", $userId, $expenseDate, $expenseItem, $expenseCost, $categoryId);
        if ($stmt->execute()) {
            return true;
        } else {
            error_log("Error adding expense: " . $mysqli->error);
            return false;
        }
    } else {
        error_log("Error preparing statement: " . $mysqli->error);
        return false;
    }
}

// Function to retrieve expenses for a user

// Function to retrieve all categories

// Function to retrieve daily summary of expenses for a user
function getDailySummary(int $userId): array {
    global $mysqli;
    $result = $mysqli->query("SELECT SUM(ExpenseCost) AS TotalExpense FROM tblexpense WHERE UserId = $userId AND DATE(ExpenseDate) = CURDATE()");
    if ($result) {
        $summary = $result->fetch_assoc();
        return $summary;
    } else {
        error_log("Error retrieving daily summary: " . $mysqli->error);
        return [];
    }
}

// Function to update an existing expense
function updateExpense(int $expenseId, int $userId, string $expenseDate, string $expenseItem, float $expenseCost, int $categoryId): bool {
    global $mysqli;
    $stmt = $mysqli->prepare("UPDATE tblexpense SET ExpenseDate = ?, ExpenseItem = ?, ExpenseCost = ?, CategoryID = ? WHERE ID = ? AND UserId = ?");
    if ($stmt) {
        $stmt->bind_param("ssdii", $expenseDate, $expenseItem, $expenseCost, $categoryId, $expenseId, $userId);
        if ($stmt->execute()) {
            return true;
        } else {
            error_log("Error updating expense: " . $mysqli->error);
            return false;
        }
    } else {
        error_log("Error preparing statement: " . $mysqli->error);
        return false;
    }
}

// Function to delete an expense
function deleteExpense(int $expenseId, int $userId): bool {
    global $mysqli;
    $stmt = $mysqli->prepare("DELETE FROM tblexpense WHERE ID = ? AND UserId = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $expenseId, $userId);
        if ($stmt->execute()) {
            return true;
        } else {
            error_log("Error deleting expense: " . $mysqli->error);
            return false;
        }
    } else {
        error_log("Error preparing statement: " . $mysqli->error);
        return false;
    }
}
?>