<?php
// includes/category_management.php

require_once 'database.php'; 
session_start(); // IMPORTANT

// Function to add a new category (with UserID)
function addCategory($categoryName) {
    global $pdo;

    try {
        $userId = $_SESSION['user_id'];

        $stmt = $pdo->prepare("
            INSERT INTO tblcategory (CategoryName, UserID) 
            VALUES (:categoryName, :userId)
        ");
        $stmt->bindParam(':categoryName', $categoryName);
        $stmt->bindParam(':userId', $userId);

        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Error adding category: " . $e->getMessage());
        return false;
    }
}

// Function to retrieve only the logged-in user's categories
function getCategories() {
    global $pdo;

    try {
        $userId = $_SESSION['user_id'];

        $stmt = $pdo->prepare("
            SELECT * FROM tblcategory 
            WHERE UserID = :userId
            ORDER BY CategoryName
        ");
        $stmt->bindParam(':userId', $userId);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log("Error retrieving categories: " . $e->getMessage());
        return [];
    }
}
?>
