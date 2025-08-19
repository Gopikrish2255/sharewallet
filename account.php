<?php
session_start();
require_once 'includes/database.php';

if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}

// Handle profile updates, change password, etc.
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Profile</title>
    <link rel="stylesheet" href="css/main.css">
    <script src="js/account.js"></script>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    <div class="account-profile">
        <h1>Your Profile</h1>
        <!-- Profile form and display goes here -->
    </div>
</body>
</html>
