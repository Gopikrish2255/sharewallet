<?php
session_start();
require_once 'includes/database.php';
require_once 'includes/category_management.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Handle adding a category
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoryName = sanitize($_POST['category_name']);
    addCategory($categoryName);
    header("Location: manage_categories.php"); // Redirect to avoid form resubmission
    exit();
}

// Fetch all categories
$categories = getCategories();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/main.css">
    <title>Manage Categories</title>
</head>
<body>
    <form method="POST" action="">
        <h2>Add Category</h2>
        <input type="text" name="category_name" required placeholder="Category Name">
        <button type="submit">Add Category</button>
    </form>

    <h2>Categories</h2>
    <ul>
        <?php foreach ($categories as $category): ?>
            <li><?php echo htmlspecialchars($category['CategoryName']); ?></li>
        <?php endforeach; ?>
    </ul>
</body>
</html>
