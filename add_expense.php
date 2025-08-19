<?php   
session_start();
define('INCLUDED', true);
include 'includes/database.php';
include 'includes/functions.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$amount = $category = $description = $date = "";
$error_message = "";
$success_message = "";

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch categories from the database
$categories = getCategories(); 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed");
    }

    // Input validation and sanitization
    $amount = filter_var($_POST['amount'], FILTER_VALIDATE_FLOAT);
    $category = filter_input(INPUT_POST, 'category', FILTER_VALIDATE_INT);
    $description = strip_tags(trim($_POST['description'] ?? '')); // Remove HTML tags and trim whitespace
    $date = filter_input(INPUT_POST, 'date', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    // Debugging output
    error_log("Amount: $amount, Category: $category, Description: $description, Date: $date");

    if ($amount === false || $category === false || $description === '' || $date === '') {
        $error_message = "Invalid input. Please check your entries.";
    } else {
        // Date validation
        // Date validation
$expense_date = DateTime::createFromFormat('Y-m-d', $date);
$now = new DateTime('2000-01-01'); // Set the minimum allowed date to January 1, 2000
$max_date = new DateTime('2024-12-31'); // Set the maximum allowed date to December 31, 2024

if (!$expense_date || $expense_date < $now || $expense_date > $max_date) {
    $error_message = "Invalid date. Please select a valid date (between January 1, 2000, and December 31, 2024).";
}


        if (!$expense_date || $expense_date < $now || $expense_date > $max_date) {
            $error_message = "Invalid date. Please select a valid date (between today and December 31, 2024).";
        } else {
            // Call the function to add expense
            if (addExpense($user_id, $amount, $description, $date, $category)) {
                $success_message = "Expense added successfully!";
                // Clear form data on success
                $amount = $category = $description = $date = "";
            } else {
                $error_message = "Error adding expense. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Expense - Elegant Expense Tracker</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap');

        :root {
    --primary-color: #6c5ce7;
    --secondary-color: #a29bfe;
    --text-color: #2d3436;
    --background-color: #f9f9f9;
    --glass-bg: rgba(255, 255, 255, 0.7);
    --glass-border: 1px solid rgba(255, 255, 255, 0.3);
}

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    font-family: 'Poppins', sans-serif;
    display: flex;
    min-height: 100vh;
    color: var(--text-color);
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
}

.container {
    background: var(--glass-bg);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    border: var(--glass-border);
    padding: 2rem;
    width: 100%;
    max-width: 500px;
    opacity: 0;
    transform: translateY(20px);
    animation: fadeInUp 0.6s ease-out forwards;
}

h1 {
    font-size: 2rem;
    margin-bottom: 1.5rem;
    color: var(--primary-color);
    text-align: center;
    text-shadow: 1px 1px 2px rgba(255, 255, 255, 0.5);
}

.form-group {
    margin-bottom: 1.5rem;
}

label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: var(--text-color);
    text-shadow: 1px 1px 2px rgba(255, 255, 255, 0.5);
}

input[type="text"],
input[type="number"],
input[type="date"],
select {
    width: 100%;
    padding: 0.75rem;
    background: rgba(255, 255, 255, 0.8);
    border: 1px solid rgba(108, 92, 231, 0.3);
    border-radius: 10px;
    transition: border-color 0.3s, box-shadow 0.3s;
    font-size: 1rem;
    color: var(--text-color);
}

input[type="text"]:focus,
input[type="number"]:focus,
input[type="date"]:focus,
select:focus {
    border-color: var(--primary-color);
    outline: none;
    box-shadow: 0 0 0 3px rgba(108, 92, 231, 0.2);
}

select {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%236c5ce7' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 1rem center;
    background-size: 1em;
    padding-right: 2.5rem;
}

.submit-btn {
    background: var(--primary-color);
    color: white;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    transition: background 0.3s, transform 0.3s;
    font-size: 1rem;
    font-weight: 600;
    width: 100%;
}

.submit-btn:hover {
    background: var(--secondary-color);
    transform: translateY(-2px);
}

.message {
    padding: 1rem;
    border-radius: 10px;
    margin-bottom: 1rem;
    font-weight: 600;
    backdrop-filter: blur(5px);
}

.error-message {
    background-color: rgba(255, 235, 238, 0.9);
    color: #c62828;
    border: 1px solid rgba(239, 154, 154, 0.5);
}

.success-message {
    background-color: rgba(232, 245, 233, 0.9);
    color: #2e7d32;
    border: 1px solid rgba(165, 214, 167, 0.5);
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
    <?php include 'includes/navbar.php'; ?>

    <div class="main-content">
        <div class="container">
            <h1>Add Expense</h1>
            <?php if ($error_message): ?>
                <div class="message error-message"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            <?php if ($success_message): ?>
                <div class="message success-message"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <div class="form-group">
                    <label for="amount">Amount (₹)</label>
                    <input type="number" name="amount" id="amount" required placeholder="Enter amount" step="0.01" value="<?php echo htmlspecialchars($amount); ?>">
                </div>
                <div class="form-group">
                    <label for="category">Category</label>
                    <select name="category" id="category" required>
                        <option value="" disabled <?php echo empty($category) ? 'selected' : ''; ?>>Select category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat['ID'] ?? ''); ?>" <?php echo ($category == $cat['ID']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['CategoryName'] ?? ''); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <input type="text" name="description" id="description" required placeholder="Enter description" value="<?php echo htmlspecialchars($description); ?>">
                </div>
                <div class="form-group">
                    <label for="date">Date</label>
                    <input type="date" name="date" id="date" required value="<?php echo htmlspecialchars($date); ?>">
                </div>
                <button type="submit" class="submit-btn">Add Expense</button>
            </form>
        </div>
    </div>

    <script>
        feather.replace();
    </script>
</body>
</html>
