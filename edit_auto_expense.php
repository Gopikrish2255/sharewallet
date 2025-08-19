<?php
session_start();
require_once 'includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['userid'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['userid'];

// Check if ID is provided
if (!isset($_GET['id'])) {
    header("Location: auto_expenses.php");
    exit();
}

$expenseId = intval($_GET['id']);

// Get the auto expense details
$autoExpenses = getAutoExpenses($userId);
$expense = array_filter($autoExpenses, function($e) use ($expenseId) {
    return $e['ID'] == $expenseId;
});

if (empty($expense)) {
    header("Location: auto_expenses.php");
    exit();
}

$expense = reset($expense);

// Get categories for the dropdown
$categories = getCategories();

// Include header
include_once('header.php');
?>

<h2>Edit Auto Expense</h2>

<form method="post" action="auto_expenses.php">
    <input type="hidden" name="id" value="<?php echo $expense['ID']; ?>">
    <input type="text" name="expense_item" value="<?php echo $expense['ExpenseItem']; ?>" required>
    <input type="number" name="expense_cost" step="0.01" value="<?php echo $expense['ExpenseCost']; ?>" required>
    <select name="category" required>
        <?php foreach ($categories as $category): ?>
            <option value="<?php echo $category['ID']; ?>" <?php echo ($category['ID'] == $expense['CategoryID']) ? 'selected' : ''; ?>>
                <?php echo $category['CategoryName']; ?>
            </option>
        <?php endforeach; ?>
    </select>
    <input type="date" name="next_due_date" value="<?php echo $expense['NextDueDate']; ?>" required>
    <select name="frequency" required>
        <option value="monthly" <?php echo ($expense['Frequency'] == 'monthly') ? 'selected' : ''; ?>>Monthly</option>
        <option value="quarterly" <?php echo ($expense['Frequency'] == 'quarterly') ? 'selected' : ''; ?>>Quarterly</option>
        <option value="yearly" <?php echo ($expense['Frequency'] == 'yearly') ? 'selected' : ''; ?>>Yearly</option>
    </select>
    <input type="submit" name="edit" value="Update Auto Expense">
</form>

<a href="auto_expenses.php">Back to Auto Expenses</a>

<?php
// Include footer
include_once('footer.php');
?>