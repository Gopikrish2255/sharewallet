<?php
session_start();
require_once 'includes/database.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    exit('User not logged in');
}

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $monthlyBudget = $_POST['monthly_budget'];
    $currentDate = date('Y-m-d');
    
    $stmt = $pdo->prepare("UPDATE tbluser SET monthly_budget = ?, last_budget_entry = ? WHERE ID = ?");
    $stmt->execute([$monthlyBudget, $currentDate, $userId]);
    
    $month = date('n');
    $year = date('Y');
    
    $stmt = $pdo->prepare("INSERT INTO tblmonthly_expenses (user_id, month, year, total_expense, budget) VALUES (?, ?, ?, 0, ?)");
    $stmt->execute([$userId, $month, $year, $monthlyBudget]);
    
    exit('Budget updated successfully');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Monthly Budget</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .popup { 
            background: white; 
            padding: 20px; 
            border-radius: 5px; 
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        input, button { margin: 10px 0; padding: 5px; width: 100%; }
        button { background: #6c5ce7; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <div class="popup">
        <h2>Set Monthly Budget</h2>
        <form id="budgetForm">
            <input type="number" id="monthlyBudget" name="monthly_budget" required placeholder="Enter monthly budget">
            <button type="submit">Set Budget</button>
        </form>
    </div>
    <script>
        document.getElementById('budgetForm').addEventListener('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            fetch('monthly_budget_popup.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(result => {
                alert(result);
                window.parent.location.reload();
            });
        });
    </script>
</body>
</html>