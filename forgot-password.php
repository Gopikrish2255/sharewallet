<?php
session_start();
require_once 'includes/database.php';
require_once 'includes/functions.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialize variables
$email = '';
$mobileNumber = '';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['email'], $_POST['mobileNumber'])) {
        $email = sanitize($_POST['email']);
        $mobileNumber = sanitize($_POST['mobileNumber']);

        // Check if email and mobile number exist in the database
        $stmt = $conn->prepare("SELECT * FROM tbluser WHERE Email = ? AND MobileNumber = ?");
        $stmt->bind_param('ss', $email, $mobileNumber);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Store email and mobile number in session for password reset
            $_SESSION['reset_email'] = $email;
            $_SESSION['reset_mobile'] = $mobileNumber;
            header("Location: reset-password.php");
            exit();
        } else {
            $error = "No account found with the provided email and mobile number.";
        }
    } else {
        $error = "Please enter both email and mobile number.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <title>Forgot Password</title>
    <style>
        /* Copy the styles from your registration page here */
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
        <form method="POST" action="" id="forgotPasswordForm">
            <h2>Forgot Password</h2>
            <?php if ($error): ?>
                <div id="errorMessage" class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <div class="input-group">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" required placeholder="Email" value="<?php echo htmlspecialchars($email); ?>">
            </div>
            <div class="input-group">
                <i class="fas fa-phone"></i>
                <input type="tel" name="mobileNumber" required placeholder="Mobile Number" value="<?php echo htmlspecialchars($mobileNumber); ?>">
            </div>
            <button type="submit">Verify</button>
            <p>Remember your password? <a href="login.php">Login here</a></p>
        </form>
    </div>
</body>
</html>