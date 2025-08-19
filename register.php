<?php
session_start();
require_once 'includes/database.php';
require_once 'includes/functions.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$firstName = $lastName = $email = $mobileNumber = $password = $confirmPassword = $gender = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['firstName'], $_POST['lastName'], $_POST['email'], $_POST['mobileNumber'], $_POST['password'], $_POST['confirmPassword'], $_POST['gender'])) {
        $firstName = sanitize($_POST['firstName']);
        $lastName = sanitize($_POST['lastName']);
        $email = sanitize($_POST['email']);
        $mobileNumber = sanitize($_POST['mobileNumber']);
        $password = $_POST['password'];
        $confirmPassword = $_POST['confirmPassword'];
        $gender = sanitize($_POST['gender']);

        if (empty($firstName) || empty($lastName) || empty($email) || empty($mobileNumber) || empty($password) || empty($confirmPassword) || empty($gender)) {
            $error = "Please fill in all required fields.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format.";
        } elseif (!preg_match('/^[0-9]{10}$/', $mobileNumber)) {
            $error = "Mobile number must be 10 digits.";
        } elseif ($password !== $confirmPassword) {
            $error = "Passwords do not match.";
        } elseif (strlen($password) < 8) {
            $error = "Password must be at least 8 characters long.";
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $error = "Password must contain at least one uppercase letter.";
        } elseif (!preg_match('/[0-9]/', $password)) {
            $error = "Password must contain at least one number.";
        } elseif (!preg_match('/[\W_]/', $password)) {
            $error = "Password must contain at least one special character.";
        } else {
            $stmt = $conn->prepare("SELECT * FROM tbluser WHERE Email = ?");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 0) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO tbluser (FirstName, LastName, Email, MobileNumber, Password, Gender) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param('ssssss', $firstName, $lastName, $email, $mobileNumber, $hashedPassword, $gender);

                if ($stmt->execute()) {
                    $_SESSION['user_id'] = $conn->insert_id;
                    header("Location: dashboard.php");
                    exit();
                } else {
                    $error = "There was an error registering your account: " . $stmt->error;
                }
            } else {
                $error = "Email already registered. Please use a different email.";
            }
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&family=Roboto+Mono:wght@400;700&display=swap" rel="stylesheet">
    <title>Register</title>
    <style>
        * {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

body {
  background: linear-gradient(135deg, #2e1a47, #1e0e2f);
  font-family: 'Montserrat', sans-serif;
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  padding: 20px;
}

.container {
  background: rgba(255, 255, 255, 0.08);
  backdrop-filter: blur(15px);
  border-radius: 20px;
  padding: 40px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.25);
  border: 1px solid rgba(255, 255, 255, 0.1);
  width: 100%;
  max-width: 450px;
  animation: fadeInUp 0.6s ease-out forwards;
  transition: transform 0.3s ease;
  color: #fff;
}

.container:hover {
  transform: translateY(-5px);
}

h2 {
  margin-bottom: 20px;
  font-size: 28px;
  font-weight: 600;
  text-align: center;
  color: #fff;
}

.input-group {
  position: relative;
  margin-bottom: 20px;
}

.input-group input,
.input-group select {
  width: 100%;
  padding: 12px 12px 12px 40px;
  border: none;
  border-radius: 10px;
  background: rgba(255, 255, 255, 0.15);
  color: #fff;
  font-size: 16px;
}

.input-group input:focus,
.input-group select:focus {
  outline: none;
  border: 1px solid #4bc0c0;
  box-shadow: 0 0 5px rgba(75, 192, 192, 0.5);
}

.input-group i {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: #4bc0c0;
  pointer-events: none;
}

.password-group input {
  padding-right: 100px;
}

.password-strength {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  font-family: 'Roboto Mono', monospace;
  font-size: 12px;
  font-weight: 700;
  transition: all 0.3s ease;
  opacity: 0;
  pointer-events: none;
}

.password-strength.visible {
  opacity: 1;
}

.strength-too-short,
.strength-weak {
  color: #ff6b6b;
}

.strength-medium {
  color: #ffa502;
}

.strength-strong {
  color: #4caf50;
}

button {
  background-color: #4bc0c0;
  color: white;
  padding: 14px 20px;
  border: none;
  border-radius: 10px;
  font-size: 18px;
  cursor: pointer;
  transition: all 0.3s ease;
  width: 100%;
  margin-top: 10px;
}

button:hover {
  background-color: #36a2a2;
}

p {
  margin-top: 20px;
  font-size: 14px;
  text-align: center;
  color: #ccc;
}

a {
  color: #4bc0c0;
  text-decoration: none;
  transition: color 0.3s ease;
}

a:hover {
  text-decoration: underline;
}

.error {
  color: #ff6b6b;
  font-size: 14px;
  margin-bottom: 15px;
  text-align: center;
  animation: shake 0.5s ease;
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes shake {
  0%, 100% { transform: translateX(0); }
  10%, 30%, 50%, 70%, 90% { transform: translateX(-10px); }
  20%, 40%, 60%, 80% { transform: translateX(10px); }
}

@keyframes pulse {
  0% { transform: translateY(-50%) scale(1); }
  50% { transform: translateY(-50%) scale(1.05); }
  100% { transform: translateY(-50%) scale(1); }
}
.logo-bar {
  position: absolute;
  top: 30px;
  left: 10%;
  transform: translateX(-50%);
  text-align: center;
}

.logo-text {
  font-size: 36px;
  font-weight: 700;
  color: #ffffff;
  font-family: 'Poppins', sans-serif;
  letter-spacing: 1px;
}

.logo-text span {
  color: #4bc0c0;
}

    </style>
</head>
<body><div class="logo-bar">
  <div class="logo-text">Share<span>Wallet</span></div>
</div>

    <div class="container">
        <form method="POST" action="" id="registerForm">
            <h2>Create Account</h2>
            <?php if ($error): ?>
                <div id="errorMessage" class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <div class="input-group">
                <i class="fas fa-user"></i>
                <input type="text" name="firstName" required placeholder="First Name" value="<?php echo htmlspecialchars($firstName ?? ''); ?>">
            </div>
            <div class="input-group">
                <i class="fas fa-user"></i>
                <input type="text" name="lastName" required placeholder="Last Name" value="<?php echo htmlspecialchars($lastName ?? ''); ?>">
            </div>
            <div class="input-group">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" required placeholder="Email" value="<?php echo htmlspecialchars($email ?? ''); ?>">
            </div>
            <div class="input-group">
                <i class="fas fa-phone"></i>
                <input type="tel" name="mobileNumber" required placeholder="Mobile Number" value="<?php echo htmlspecialchars($mobileNumber ?? ''); ?>" pattern="[0-9]{10}" title="Mobile number must be 10 digits.">
            </div>
            <div class="input-group password-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" id="password" required placeholder="Password" minlength="8" title="Password must be at least 8 characters long.">
                <div class="password-strength" id="passwordStrength"></div>
            </div>
            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="confirmPassword" required placeholder="Confirm Password" minlength="8" title="Password must be at least 8 characters long.">
            </div>
            <div class="input-group">
                <i class="fas fa-venus-mars"></i>
                <select name="gender" required>
                    <option value="">Select Gender</option>
                    <option value="Male" <?php if ($gender === 'Male') echo 'selected'; ?>>Male</option>
                    <option value="Female" <?php if ($gender === 'Female') echo 'selected'; ?>>Female</option>
                </select>
            </div>
            <button type="submit">Register</button>
            <p>Already have an account? <a href="login.php">Login here</a></p>
        </form>
    </div>

    <script>
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthIndicator = document.getElementById('passwordStrength');
            let strength = '';

            if (password.length < 8) {
                strength = 'Too short';
                strengthIndicator.className = 'password-strength strength-too-short';
            } else if (password.length < 10) {
                strength = 'Weak';
                strengthIndicator.className = 'password-strength strength-weak';
            } else if (password.match(/[A-Z]/) && password.match(/[0-9]/) && password.match(/[\W_]/)) {
                strength = 'Strong';
                strengthIndicator.className = 'password-strength strength-strong';
            } else {
                strength = 'Medium';
                strengthIndicator.className = 'password-strength strength-medium';
            }

            strengthIndicator.textContent = strength;
            strengthIndicator.classList.add('visible');
            strengthIndicator.style.animation = 'pulse 0.5s ease-in-out';
            setTimeout(() => {
                strengthIndicator.style.animation = '';
            }, 500);
        });

        document.querySelectorAll('input, select').forEach(input => {
            input.addEventListener('input', function() {
                if (this.value.trim() === '') {
                    this.style.borderColor = '#ff4757';
                } else {
                    this.style.borderColor = 'rgba(255, 255, 255, 0.5)';
                }
            });
        });
    </script>
</body>
</html>

