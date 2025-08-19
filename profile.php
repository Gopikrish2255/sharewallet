<?php
session_start();
require_once 'includes/database.php'; // Ensure this contains your database connection code
require_once 'includes/functions.php'; // Ensure this contains your utility functions

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    redirect("login.php"); // Use the redirect function for cleaner code
}

$userId = $_SESSION['user_id'];

// Handle form submission for profile update
$message = ''; // Initialize message variable
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $firstName = sanitize($_POST['first_name']);
    $lastName = sanitize($_POST['last_name']);
    $email = sanitize($_POST['email']);

    // Call function to update user profile and capture success or error
    if (updateProfile($userId, $firstName, $lastName, $email)) {
        $message = "Profile updated successfully!";
        // Optionally, refresh user details after updating
        $userDetails = getUserDetails($userId);
    } else {
        $message = "Error updating profile. Please try again.";
    }
}

// Fetch user details for the logged-in user
$userDetails = getUserDetails($userId);

if (!$userDetails) {
    // Handle the case where user details cannot be fetched
    $message = "Unable to retrieve user details.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/main.css">
    <title>Edit Profile</title>
</head>
<body>
    <!-- Include Navbar -->
    <?php require_once 'includes/navbar.php'; ?>

    <div class="container">
        <form method="POST" action="">
            <h2>Edit Profile</h2>
            <?php if ($message): ?>
                <p class="message"><?php echo htmlspecialchars($message); ?></p>
            <?php endif; ?>

            <label for="first_name">First Name:</label>
            <input type="text" id="first_name" name="first_name" required value="<?php echo htmlspecialchars($userDetails['FirstName']); ?>">

            <label for="last_name">Last Name:</label>
            <input type="text" id="last_name" name="last_name" required value="<?php echo htmlspecialchars($userDetails['LastName']); ?>">

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($userDetails['Email']); ?>">

            <button type="submit">Update Profile</button>
        </form>

        <div>
            <a href="change_password.php">Change Password</a>
        </div>
    </div>
</body>
</html>
