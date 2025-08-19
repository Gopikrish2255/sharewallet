<?php
include 'db.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql = "DELETE FROM tblcategory WHERE ID = $id";
    if ($conn->query($sql) === TRUE) {
        echo "Category deleted successfully!";
    } else {
        echo "Error deleting category: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Delete Category</title>
</head>
<body>
    <h2>Category Deleted</h2>
    <p>The category has been deleted. <a href="categories.php">Go back to categories list</a>.</p>
</body>
</html>
