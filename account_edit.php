<?php  
session_start();
define('INCLUDED', true);
require_once __DIR__ . '/includes/functions.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    redirect('login.php', 'Please log in to access your account.');
}

$userId = $_SESSION['user_id'];
$userDetails = [];
$errorMessage = '';
$successMessage = '';

// Fetch user details
try {
    $userDetails = getUserDetails($userId);
    if (empty($userDetails)) {
        throw new Exception("Unable to retrieve user details.");
    }
} catch (Exception $e) {
    $errorMessage = "Error: " . $e->getMessage();
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = isset($_POST['first_name']) ? sanitize($_POST['first_name']) : '';
    $lastName = isset($_POST['last_name']) ? sanitize($_POST['last_name']) : '';
    $email = isset($_POST['email']) ? sanitize($_POST['email']) : '';
    $mobileNumber = isset($_POST['mobile_number']) ? sanitize($_POST['mobile_number']) : '';

    // Validate inputs
    if (empty($firstName) || empty($lastName) || empty($email)) {
        $errorMessage = "All fields are required.";
    } elseif (!validateEmail($email)) {
        $errorMessage = "Invalid email format.";
    } else {
        try {
            if (updateProfile($userId, $firstName, $lastName, $email, $mobileNumber)) {
                $successMessage = 'Profile updated successfully!';
                // Refresh user details after update
                $userDetails = getUserDetails($userId);
            } else {
                $errorMessage = 'Failed to update profile. Please try again.';
            }
        } catch (Exception $e) {
            $errorMessage = "Error updating profile: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Account - Elegant Expense Tracker</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
       @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap');

:root {
    --primary-color: rgba(108, 92, 231, 0.8);
    --secondary-color: rgba(162, 155, 254, 0.8);
    --text-color: #2d3436;
    --background-color: rgba(249, 249, 249, 0.4);
    --input-background: rgba(255, 255, 255, 0.2);
    --input-border: rgba(224, 224, 224, 0.3);
    --input-focus-shadow: rgba(108, 92, 231, 0.2);
}

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    background: linear-gradient(135deg, #a29bfe, #6c5ce7);
    font-family: 'Poppins', sans-serif;
    display: flex;
    min-height: 100vh;
    color: var(--text-color);
    overflow: hidden;
}

.main-content {
    flex-grow: 1;
    padding: 2rem;
    margin-left: 16rem;
    width: calc(100% - 16rem);
    overflow-y: auto;
    display: flex;
    justify-content: center;
    align-items: center;
    perspective: 1000px;
}

.container {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    padding: 2rem;
    width: 100%;
    max-width: 500px;
    opacity: 0;
    transform: translateY(20px) rotateX(10deg);
    animation: fadeInUp 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards;
}

h1 {
    font-size: 2rem;
    margin-bottom: 1.5rem;
    color: var(--text-color);
    text-align: center;
    position: relative;
}

h1::after {
    content: '';
    display: block;
    width: 50px;
    height: 3px;
    background: var(--primary-color);
    margin: 10px auto 0;
    border-radius: 2px;
    transform: scaleX(0);
    animation: expandLine 0.6s ease-out 0.4s forwards;
}

.form-group {
    margin-bottom: 1.5rem;
    position: relative;
    opacity: 0;
    transform: translateY(10px);
    animation: fadeInUp 0.6s ease-out forwards;
    animation-delay: calc(var(--delay) * 0.1s);
}

label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: var(--text-color);
    transition: color 0.3s ease, transform 0.3s ease;
}

input[type="text"], input[type="email"], input[type="tel"] {
    width: 100%;
    padding: 0.75rem;
    border: 2px solid var(--input-border);
    border-radius: 10px;
    transition: all 0.3s ease;
    font-size: 1rem;
    background: var(--input-background);
    color: var(--text-color);
}

input[type="text"]:focus, input[type="email"]:focus, input[type="tel"]:focus {
    border-color: var(--primary-color);
    outline: none;
    box-shadow: 0 0 0 4px var(--input-focus-shadow);
    transform: translateY(-2px);
}

.form-group:focus-within label {
    color: var(--primary-color);
    transform: translateY(-2px);
}

button {
    background: var(--primary-color);
    color: white;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 1rem;
    font-weight: 600;
    width: 100%;
    position: relative;
    overflow: hidden;
}

button::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    transform: translate(-50%, -50%);
    transition: width 0.6s ease, height 0.6s ease;
}

button:hover {
    background: var(--secondary-color);
    transform: translateY(-2px);
}

button:hover::before {
    width: 300px;
    height: 300px;
}

.alert {
    padding: 1rem;
    border-radius: 10px;
    margin-bottom: 1rem;
    font-weight: 600;
    opacity: 0;
    transform: translateY(-10px);
    animation: fadeInDown 0.4s ease-out forwards;
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(5px);
}

.alert-success {
    background-color: rgba(232, 245, 233, 0.5);
    color: #2e7d32;
    border: 1px solid rgba(165, 214, 167, 0.5);
}

.alert-danger {
    background-color: rgba(255, 235, 238, 0.5);
    color: #c62828;
    border: 1px solid rgba(239, 154, 154, 0.5);
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px) rotateX(10deg);
    }
    to {
        opacity: 1;
        transform: translateY(0) rotateX(0);
    }
}

@keyframes fadeInDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes expandLine {
    from {
        transform: scaleX(0);
    }
    to {
        transform: scaleX(1);
    }
}

@media (max-width: 768px) {
    body {
        flex-direction: column;
    }
    .main-content {
        margin-left: 0;
        width: 100%;
        padding: 1rem;
    }
    .container {
        padding: 1.5rem;
    }
}
    </style>
</head>
<body>

<?php 
// Include the navbar
if (file_exists('includes/navbar.php')) {
    include 'includes/navbar.php';
} else {
    echo "<!-- Navbar file not found -->";
}
?>

<div class="main-content">
    <div class="container">
        <h1>Edit Account</h1>

        <?php
        // Display error or success messages
        if (!empty($errorMessage)) {
            echo '<div class="alert alert-danger">' . safeOutput($errorMessage) . '</div>';
        }
        if (!empty($successMessage)) {
            echo '<div class="alert alert-success">' . safeOutput($successMessage) . '</div>';
        }
        ?>

        <!-- Profile Update Form -->
        <form action="" method="post">
            <div class="form-group">
                <label for="first_name">First Name</label>
                <input type="text" id="first_name" name="first_name" value="<?php echo safeOutput($userDetails['FirstName'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="last_name">Last Name</label>
                <input type="text" id="last_name" name="last_name" value="<?php echo safeOutput($userDetails['LastName'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo safeOutput($userDetails['Email'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="mobile_number">Mobile Number</label>
                <input type="tel" id="mobile_number" name="mobile_number" value="<?php echo safeOutput($userDetails['MobileNumber'] ?? ''); ?>">
            </div>

            <button type="submit">Update Profile</button>
        </form>
    </div>
</div>

<script>
    // Feather icons replacement
    feather.replace();
</script>

</body>
</html>
