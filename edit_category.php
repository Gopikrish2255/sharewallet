<?php
session_start();
define('INCLUDED', true);
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '/path/to/error.log'); // Adjust this path as needed

require_once 'includes/database.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}

// Fetch the category to edit
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $sql = "SELECT * FROM tblcategory WHERE ID = $id";
    $result = $conn->query($sql);
    $category = $result->fetch_assoc();

    if (!$category) {
        die("Category not found.");
    }
}

// Handle updating the category
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $categoryName = sanitize($_POST['categoryName']);
    $sql = "UPDATE tblcategory SET CategoryName = '$categoryName' WHERE ID = $id";
    if ($conn->query($sql) === TRUE) {
        $success_message = "Category updated successfully!";
    } else {
        $error_message = "Error updating category: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Category - Elegant Expense Tracker</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap');

        :root {
            --primary-color: #6c5ce7;
            --secondary-color: #a29bfe;
            --text-color: #2d3436;
            --background-color: #f0f3f5;
            --glass-bg: rgba(255, 255, 255, 0.25);
            --glass-border: 1px solid rgba(255, 255, 255, 0.35);
            --input-bg: rgba(255, 255, 255, 0.5);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background: linear-gradient(135deg, #74b9ff, #a29bfe);
            font-family: 'Poppins', sans-serif;
            display: flex;
            min-height: 100vh;
            color: var(--text-color);
        }

        .main-content {
            flex-grow: 1;
            padding: 2rem;
            margin-left: 250px;
            width: calc(100% - 250px);
            overflow-y: auto;
        }

        .container {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            border: var(--glass-border);
            padding: 2rem;
            margin-bottom: 2rem;
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 0.6s ease-out forwards;
            transition: all 0.3s ease;
        }

        .container:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px 0 rgba(31, 38, 135, 0.45);
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

        h1, h2 {
            color: #2c3e50;
            margin-bottom: 1.5rem;
            text-shadow: 1px 1px 2px rgba(255, 255, 255, 0.1);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #34495e;
            text-shadow: 1px 1px 2px rgba(255, 255, 255, 0.1);
        }

        input[type="text"] {
            width: 100%;
            padding: 0.75rem;
            background: var(--input-bg);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 10px;
            color: #2c3e50;
            transition: all 0.3s ease;
            font-size: 1rem;
        }

        input[type="text"]::placeholder {
            color: rgba(44, 62, 80, 0.7);
        }

        input[type="text"]:focus {
            background: rgba(255, 255, 255, 0.6);
            border-color: rgba(108, 92, 231, 0.5);
            outline: none;
            box-shadow: 0 0 15px rgba(108, 92, 231, 0.2);
        }

        .submit-btn {
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
        }

        .submit-btn:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .message {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            font-weight: 600;
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .error-message {
            background-color: rgba(255, 87, 87, 0.3);
            color: #c0392b;
            border: 1px solid rgba(255, 87, 87, 0.5);
        }

        .success-message {
            background-color: rgba(46, 204, 113, 0.3);
            color: #f3f6f4;
            border: 1px solid rgba(46, 204, 113, 0.5);
        }

        @media (max-width: 768px) {
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
    <?php include 'includes/navbar.php'; ?>

    <div class="main-content">
        <div class="container">
            <h2>Edit Category</h2>
            <?php if (isset($error_message)): ?>
                <div class="message error-message"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            <?php if (isset($success_message)): ?>
                <div class="message success-message"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="categoryName">Category Name:</label>
                    <input type="text" id="categoryName" name="categoryName" value="<?php echo htmlspecialchars($category['CategoryName']); ?>" required>
                </div>
                <button type="submit" class="submit-btn">Update</button>
            </form>
        </div>
    </div>

    <script>
        feather.replace();
    </script>
</body>
</html>
