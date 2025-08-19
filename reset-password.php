<?php
session_start();
require_once 'includes/database.php';
require_once 'includes/functions.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialize variables
$error = '';
$success = '';

// Check if user is authorized to reset password
if (!isset($_SESSION['reset_email']) || !isset($_SESSION['reset_mobile'])) {
    header("Location: forgot-password.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['newPassword'], $_POST['confirmPassword'])) {
        $newPassword = $_POST['newPassword'];
        $confirmPassword = $_POST['confirmPassword'];

        if ($newPassword === $confirmPassword) {
            if (strlen($newPassword) >= 8) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $email = $_SESSION['reset_email'];
                
                $stmt = $conn->prepare("UPDATE tbluser SET Password = ? WHERE Email = ?");
                $stmt->bind_param('ss', $hashedPassword, $email);
                
                if ($stmt->execute()) {
                    $success = "Your password has been successfully reset. You can now login with your new password.";
                    // Clear reset session variables
                    unset($_SESSION['reset_email']);
                    unset($_SESSION['reset_mobile']);
                } else {
                    $error = "An error occurred while resetting your password. Please try again.";
                }
            } else {
                $error = "Password must be at least 8 characters long.";
            }
        } else {
            $error = "Passwords do not match.";
        }
    } else {
        $error = "Please enter both password fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <title>Reset Password</title>
    <style>
         @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap');

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    font-family: 'Montserrat', sans-serif;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    margin: 0;
    padding: 20px;
}

.container {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
    text-align: center;
    width: 100%;
    max-width: 400px;
    transform: translateY(20px);
    opacity: 0;
    animation: fadeInUp 0.6s ease-out forwards;
}

h2 {
    color: #333;
    margin-bottom: 20px;
    font-size: 24px;
    font-weight: 600;
}

.input-group {
    position: relative;
    margin-bottom: 15px;
}

.input-group input {
    width: 100%;
    padding: 12px 12px 12px 40px;
    border: none;
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.2);
    box-shadow: inset 2px 2px 5px #BABECC, inset -5px -5px 10px #FFF;
    color: #333;
    font-size: 14px;
    transition: all 0.3s ease;
}

.input-group input:focus {
    outline: none;
    box-shadow: inset 1px 1px 2px #BABECC, inset -1px -1px 2px #FFF;
}

.input-group i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #4bc0c0;
}

button {
    background-color: #4bc0c0;
    color: white;
    padding: 12px 20px;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: -5px -5px 20px #FFF, 5px 5px 20px #BABECC;
    width: 100%;
    margin-top: 10px;
}

button:hover {
    background-color: #36a2a2;
    transform: translateY(-2px);
    box-shadow: -2px -2px 5px #FFF, 2px 2px 5px #BABECC;
}

.error {
    color: #ff4757;
    font-size: 14px;
    margin-bottom: 15px;
}

@keyframes fadeInUp {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
    </style>
</head>
<body>
    <div class="container">
        <?php if ($success): ?>
            <div id="successMessage" class="success"><?php echo htmlspecialchars($success); ?></div>
            <p><a href="login.php">Go to login page</a></p>
        <?php else: ?>
            <form method="POST" action="" id="resetPasswordForm">
                <h2>Reset Password</h2>
                <?php if ($error): ?>
                    <div id="errorMessage" class="error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="newPassword" required placeholder="New Password">
                </div>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="confirmPassword" required placeholder="Confirm New Password">
                </div>
                <button type="submit">Reset Password</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>