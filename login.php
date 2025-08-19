<?php
session_start();
include 'includes/database.php';

$error = '';
$username = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $sql = "SELECT * FROM tbluser WHERE Email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['Password'])) {
            $_SESSION['user_id'] = $user['ID'];
            $_SESSION['username'] = $user['Email'];
            header("Location: dashboard.php");
            exit();
        } else {
            $error = 'Invalid password. Please try again.';
        }
    } else {
        $error = 'No user found with that email.';
    }

    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login - ShareWallet</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap');

    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      padding: 0;
      font-family: 'Poppins', sans-serif;
      height: 100vh;
      background: linear-gradient(135deg, #2e1a47, #1e0e2f);
      display: flex;
      justify-content: center;
      align-items: center;
    }

    .container {
      background: rgba(255, 255, 255, 0.08);
      border: 1px solid rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(15px);
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.25);
      border-radius: 20px;
      padding: 40px 30px;
      width: 100%;
      max-width: 380px;
      color: #fff;
    }

    h2 {
      text-align: center;
      margin-bottom: 25px;
    }

    .input-group {
      position: relative;
      margin-bottom: 20px;
    }

    .input-group input {
      width: 100%;
      height: 48px;
      padding: 0 15px 0 45px;
      border-radius: 10px;
      border: none;
      background: rgba(255, 255, 255, 0.15);
      color: #fff;
      font-size: 15px;
    }

    .input-group i {
      position: absolute;
      top: 50%;
      left: 15px;
      transform: translateY(-50%);
      font-size: 16px;
      color: #4bc0c0;
    }

    .input-group input::placeholder {
      color: #ddd;
    }

    .error {
      color: #ff6b6b;
      font-size: 14px;
      margin-bottom: 10px;
      text-align: center;
    }

    button {
      width: 100%;
      padding: 12px;
      font-size: 16px;
      border: none;
      border-radius: 10px;
      background-color: #4bc0c0;
      color: white;
      cursor: pointer;
      transition: background 0.3s ease;
    }

    button:hover {
      background-color: #36a2a2;
    }

    .links {
      margin-top: 20px;
      font-size: 14px;
      text-align: center;
    }

    .links a {
      color: #4bc0c0;
      text-decoration: none;
    }

    .links a:hover {
      text-decoration: underline;
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
<body>
    <div class="logo-bar">
  <div class="logo-text">Share<span>Wallet</span></div>
</div>

  <div class="container">
    <form method="POST" action="">
      <h2>Login to ShareWallet</h2>
      <?php if (!empty($error)): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <div class="input-group">
        <i class="fas fa-envelope"></i>
        <input type="email" name="username" placeholder="Email" value="<?= htmlspecialchars($username ?? '') ?>" required>
      </div>
      <div class="input-group">
        <i class="fas fa-lock"></i>
        <input type="password" name="password" placeholder="Password" required>
      </div>
      <button type="submit">Login</button>
      <div class="links">
        <p><a href="register.php">Don't have an account? Register</a></p>
        <p><a href="forgot-password.php">Forgot Password?</a></p>
      </div>
    </form>
  </div>
</body>
</html>
