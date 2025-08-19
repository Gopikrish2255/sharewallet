<?php
session_start();
define('INCLUDED', true);
require_once 'includes/database.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}
$userId = $_SESSION['user_id'];

// Run auto-expense processor (to generate actual expenses when due)
processAutoExpenses();

// CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ------------------ POST HANDLING ------------------ //
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed");
    }

    $expenseItem = trim($_POST['expense_item']);
    $expenseCost = floatval($_POST['expense_cost']);
    $categoryId  = intval($_POST['category_id']);
    $frequency   = trim($_POST['frequency']);
    $nextDueDate = $_POST['next_due_date'];

    if (isset($_POST['add_auto_expense'])) {
        $ok = addAutoExpense($userId, $expenseItem, $expenseCost, $categoryId, $frequency, $nextDueDate);
        $_SESSION['flash_message'] = $ok ? "Auto expense added successfully!" : "Failed to add auto expense.";
    }
    if (isset($_POST['edit_auto_expense'])) {
        $expenseId = intval($_POST['expense_id']);
        $ok = editAutoExpense($expenseId, $expenseItem, $expenseCost, $categoryId, $frequency, $nextDueDate, $userId);
        $_SESSION['flash_message'] = $ok ? "Auto expense updated successfully!" : "Failed to update auto expense.";
    }
    header("Location: auto_expense.php");
    exit;
}

// ------------------ DELETE ------------------ //
if (isset($_GET['delete'], $_GET['csrf_token']) && $_GET['csrf_token'] === $_SESSION['csrf_token']) {
    $expenseId = intval($_GET['delete']);
    $ok = deleteAutoExpense($userId, $expenseId);
    $_SESSION['flash_message'] = $ok ? "Auto expense deleted successfully!" : "Failed to delete auto expense.";
    header("Location: auto_expense.php");
    exit;
}

// ------------------ DB FUNCTIONS ------------------ //
function addAutoExpense($userId, $item, $cost, $categoryId, $frequency, $nextDueDate) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO auto_expenses (UserId, ExpenseItem, ExpenseCost, CategoryID, Frequency, NextDueDate) 
                            VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isdiss", $userId, $item, $cost, $categoryId, $frequency, $nextDueDate);
    return $stmt->execute();
}

function editAutoExpense($id, $item, $cost, $categoryId, $frequency, $nextDueDate, $userId) {
    global $conn;
    $stmt = $conn->prepare("UPDATE auto_expenses 
                            SET ExpenseItem=?, ExpenseCost=?, CategoryID=?, Frequency=?, NextDueDate=? 
                            WHERE ID=? AND UserId=?");
    $stmt->bind_param("sdissii", $item, $cost, $categoryId, $frequency, $nextDueDate, $id, $userId);
    return $stmt->execute();
}

function deleteAutoExpense($userId, $id) {
    global $conn;
    $stmt = $conn->prepare("DELETE FROM auto_expenses WHERE ID=? AND UserId=?");
    $stmt->bind_param("ii", $id, $userId);
    return $stmt->execute();
}

function getAutoExpenses($userId) {
    global $conn;
    $stmt = $conn->prepare("SELECT ae.*, c.CategoryName 
                            FROM auto_expenses ae 
                            LEFT JOIN tblcategory c ON ae.CategoryID = c.ID 
                            WHERE ae.UserId=? ORDER BY ae.NextDueDate ASC");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// ------------------ FETCH ------------------ //
$autoExpenses = getAutoExpenses($userId);
$categories   = getCategories($userId); // now only from functions.php
$message      = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Auto Expenses - Elegant Expense Tracker</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
<style>
body { font-family: 'Poppins', sans-serif; background:#74b9ff; margin:0; }
.container { max-width: 1200px; margin: 0 auto; padding:2rem; }
h1 { text-align:center; color:white; }
.message { padding:1rem; margin:1rem 0; border-radius:5px; }
.success { background:#55efc4; }
.error { background:#ff7675; }
.auto-expenses-list { display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:1rem; }
.auto-expense-card { background:white; border-radius:10px; padding:1rem; }
</style>
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<div class="container">
    <h1>Auto Expenses</h1>

    <?php if ($message): ?>
        <div class="message success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <!-- Add Expense Form -->
    <form method="POST" style="margin-bottom:2rem;">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="text" name="expense_item" placeholder="Expense Item" required>
        <input type="number" step="0.01" name="expense_cost" placeholder="Cost" required>
        <select name="category_id" required>
            <?php foreach($categories as $c): ?>
                <option value="<?= $c['ID'] ?>"><?= htmlspecialchars($c['CategoryName']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="frequency" required>
            <option value="monthly">Monthly</option>
            <option value="yearly">Yearly</option>
        </select>
        <input type="date" name="next_due_date" required>
        <button type="submit" name="add_auto_expense">Add</button>
    </form>

    <!-- Expense Cards -->
    <div class="auto-expenses-list">
        <?php foreach ($autoExpenses as $exp): ?>
            <div class="auto-expense-card">
                <h3><?= htmlspecialchars($exp['ExpenseItem']) ?> (₹<?= number_format($exp['ExpenseCost'],2) ?>)</h3>
                <p>Category: <?= htmlspecialchars($exp['CategoryName']) ?></p>
                <p>Frequency: <?= ucfirst($exp['Frequency']) ?></p>
                <p>Next Due: <?= htmlspecialchars($exp['NextDueDate']) ?></p>
                <a href="?delete=<?= $exp['ID'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>" onclick="return confirm('Delete this?')">Delete</a>
            </div>
        <?php endforeach; ?>
    </div>
</div>
</body>
</html>
