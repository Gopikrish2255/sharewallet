<?php
session_start();
define('INCLUDED', true);
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '/path/to/error.log'); // Adjust this path as needed

require_once 'includes/database.php';
require_once 'includes/functions.php';

// Ensure CSRF token is generated for every session
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}

// Handle adding a category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed. Please refresh the page and try again.");
    }

    // Sanitize input
    $categoryName = sanitize($_POST['category_name']);

    if ($categoryName) {
        if (addCategory($categoryName)) {
            $success_message = "Category added successfully.";
        } else {
            $error_message = "Failed to add category. Please try again.";
        }
    } else {
        $error_message = "Please enter a category name.";
    }
}

// Handle deleting a category
// Handle deleting a category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id']) && isset($_POST['csrf_token'])) {
    // Validate CSRF token
    if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed. Please try again.");
    }

    $delete_id = (int)$_POST['delete_id'];

    // Call deleteCategory function with user ID
    if (deleteCategory($delete_id, $_SESSION['user_id'])) { // Pass user ID as well
        $success_message = "Category deleted successfully.";
        header("Location: add_category.php"); // Redirect to the current page
        exit; // Stop further execution to prevent resubmission
    } else {
        $error_message = "Failed to delete category or insufficient permissions.";
    }
}

    

// Fetch existing categories
$categories = getCategories();
if ($categories === false) {
    $error_message = "Error fetching categories.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Category - Elegant Expense Tracker</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
        /* Your existing CSS styles */
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

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 10px;
            margin-top: 1rem;
        }

        th, td {
            padding: 1rem;
            text-align: left;
            background: rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
            color: #2c3e50;
        }

        th {
            background-color: rgba(108, 92, 231, 0.4);
            color: white;
            font-weight: 600;
        }

        tr {
            transition: all 0.3s ease;
        }

        tr:hover td {
            background: rgba(255, 255, 255, 0.5);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
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
            <h1>Add Category</h1>
            <?php if (isset($error_message)): ?>
                <div class="message error-message"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            <?php if (isset($success_message)): ?>
                <div class="message success-message"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <div class="form-group">
                    <label for="category_name">Category Name</label>
                    <input type="text" name="category_name" id="category_name" required>
                </div>
                <button type="submit" class="submit-btn" name="add_category">Add Category</button>
            </form>
        </div>

        <div class="container">
            <h2>Existing Categories</h2>
            <table>
                <thead>
                    <tr>
                        <th>Category Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $category): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($category['CategoryName']); ?></td>
                            <td>
                                <a href="edit_category.php?id=<?php echo $category['ID']; ?>" class="btn-edit"></a>
                                <div style="display: flex; gap: 10px;">
                                    <a href="edit_category.php?id=<?php echo $category['ID']; ?>" 
                                       style="background: rgba(108, 92, 231, 0.3);
                                              color: #2c3e50;
                                              padding: 0.5rem 1rem;
                                              border-radius: 8px;
                                              text-decoration: none;
                                              border: 1px solid rgba(255, 255, 255, 0.3);
                                              backdrop-filter: blur(5px);
                                              transition: all 0.3s ease;">Edit</a>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="delete_id" value="<?php echo $category['ID']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        <button type="submit" 
                                                onclick="return confirm('Are you sure you want to delete this category?');"
                                                style="background: rgba(255, 87, 87, 0.3);
                                                       color: #c0392b;
                                                       padding: 0.5rem 1rem;
                                                       border-radius: 8px;
                                                       border: 1px solid rgba(255, 87, 87, 0.3);
                                                       cursor: pointer;
                                                       backdrop-filter: blur(5px);
                                                       transition: all 0.3s ease;">Delete</button>
                                    </form>
                                </div></form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        feather.replace();

        // Add smooth scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // Add staggered animation to table rows
        const tableRows = document.querySelectorAll('tbody tr');
        tableRows.forEach((row, index) => {
            row.style.animation = `fadeInUp 0.5s ease-out ${index * 0.1}s forwards`;
            row.style.opacity = '0';
        });
    </script>
</body>
</html>