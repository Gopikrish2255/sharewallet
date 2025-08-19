<?php
session_start();
define('INCLUDED', true);
require_once 'includes/database.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}

$userId = (int)$_SESSION['user_id'];

// --- Fetch Monthly Budget ---
$stmt = $conn->prepare("SELECT COALESCE(MonthlyBudget,0) AS MonthlyBudget FROM tbluser WHERE ID = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
$userBudget = (float)$row['MonthlyBudget'];

// --- Fetch Current Month Expenses ---
$stmt = $conn->prepare("SELECT IFNULL(SUM(ExpenseCost),0) AS total FROM tblexpense 
                        WHERE UserId = ? AND YEAR(ExpenseDate)=YEAR(CURDATE()) 
                        AND MONTH(ExpenseDate)=MONTH(CURDATE())");
$stmt->bind_param("i", $userId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
$currentMonthTotal = (float)$row['total'];

$remainingBudget = $userBudget - $currentMonthTotal;
$percentageSpent = $userBudget > 0 ? ($currentMonthTotal / $userBudget) * 100 : 0;

// --- Fetch Category-wise breakdown ---
$stmt = $conn->prepare("
    SELECT COALESCE(c.CategoryName, 'Uncategorized') AS category, 
           IFNULL(SUM(e.ExpenseCost),0) AS total
    FROM tblexpense e
    LEFT JOIN tblcategory c ON c.ID = e.CategoryID
    WHERE e.UserId = ? AND YEAR(e.ExpenseDate)=YEAR(CURDATE()) 
          AND MONTH(e.ExpenseDate)=MONTH(CURDATE())
    GROUP BY category
    ORDER BY total DESC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$res = $stmt->get_result();

$categoryLabels = [];
$categoryValues = [];
while ($r = $res->fetch_assoc()) {
    $categoryLabels[] = $r['category'];
    $categoryValues[] = (float)$r['total'];
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Budget Management</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background: linear-gradient(135deg, #74b9ff, #a29bfe);
            font-family: 'Poppins', sans-serif;
            margin: 0;
            color: #fff;
        }
        .main-content { margin-left: 250px; padding: 2rem; }
        .container { max-width: 1000px; margin: auto; }
        .card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(12px);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        h1 { text-align: center; margin-bottom: 2rem; text-shadow: 2px 2px 5px rgba(0,0,0,0.2); }
        .progress-bar { background: #dfe6e9; border-radius: 10px; overflow: hidden; margin: 1rem 0; height: 20px; }
        .progress { height: 100%; background: linear-gradient(90deg, #55efc4, #00cec9); width: <?= min(100, $percentageSpent) ?>%; transition: width 0.5s ease-in-out; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; background: transparent; }
        th, td { padding: 10px; text-align: right; }
        th { background: #6c5ce7; color: #fff; text-align: left; }
        tr:nth-child(even) { background: rgba(255,255,255,0.05); }
        .btn { display: inline-block; padding: 10px 20px; background: #406ff3; color: #fff; border-radius: 8px; text-decoration: none; transition: 0.3s; }
        .btn:hover { background: #2c4ed3; }
        .chart-container { width: 100%; max-width: 500px; margin: 20px auto; }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    <div class="main-content">
        <div class="container">
            <h1>Budget Management</h1>

            <div class="card">
                <h2>Budget Overview</h2>
                <table>
                    <tr><th>Monthly Budget</th><td>₹<?= number_format($userBudget,2) ?></td></tr>
                    <tr><th>Spent This Month</th><td>₹<?= number_format($currentMonthTotal,2) ?></td></tr>
                    <tr><th>Remaining Budget</th><td>₹<?= number_format($remainingBudget,2) ?></td></tr>
                    <tr><th>% Spent</th><td><?= number_format($percentageSpent,2) ?>%</td></tr>
                </table>
                <div class="progress-bar"><div class="progress"></div></div>
            </div>

            <?php if (!empty($categoryLabels)): ?>
            <div class="card">
                <h2>Category-wise Expenses</h2>
                <div class="chart-container">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
            <?php endif; ?>

            <div style="text-align:center; margin-top:2rem;">
                <a href="dashboard.php" class="btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            </div>
        </div>
    </div>

    <script>
        const ctx = document.getElementById('categoryChart');
        if(ctx){
            new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: <?= json_encode($categoryLabels) ?>,
                    datasets: [{
                        data: <?= json_encode($categoryValues) ?>,
                        backgroundColor: [
                            '#6c5ce7', '#00b894', '#fd79a8', '#0984e3',
                            '#e17055', '#d63031', '#fab1a0', '#55efc4'
                        ]
                    }]
                },
                options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
            });
        }
    </script>
</body>
</html>
