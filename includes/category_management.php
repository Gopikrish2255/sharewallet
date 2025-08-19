<?php
// includes/category_management.php

require_once 'database.php'; // Ensure this file contains your DB connection code

// Function to add a new category
function addCategory($categoryName) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO tblcategory (CategoryName) VALUES (:categoryName)");
        $stmt->bindParam(':categoryName', $categoryName);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Error adding category: " . $e->getMessage());
        return false;
    }
}

// Function to retrieve all categories
function getCategories() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM tblcategory");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error retrieving categories: " . $e->getMessage());
        return [];
    }
}
?>
