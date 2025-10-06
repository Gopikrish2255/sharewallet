<?php
session_start();
define('INCLUDED', true);
require_once 'includes/database.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}

$userId = (int)$_SESSION['user_id'];

// Get budget and expense data
$stmt = $conn->prepare("SELECT COALESCE(MonthlyBudget, 0) AS MonthlyBudget FROM tbluser WHERE ID = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
$userBudget = (float)$row['MonthlyBudget'];

$stmt = $conn->prepare("SELECT IFNULL(SUM(ExpenseCost),0) AS total FROM tblexpense 
    WHERE UserId = ? AND YEAR(ExpenseDate)=YEAR(CURDATE()) AND MONTH(ExpenseDate)=MONTH(CURDATE())");
$stmt->bind_param("i", $userId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
$currentMonthTotal = (float)$row['total'];

$remainingBudget = $userBudget - $currentMonthTotal;
$percentageSpent = $userBudget > 0 ? ($currentMonthTotal / $userBudget) * 100 : 0;

// Category-wise breakdown
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

$topCategory = $categoryLabels[0] ?? 'N/A';
$topAmount = $categoryValues[0] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Budget Analysis</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap');
    :root {
      --primary-color: #6c5ce7;
      --secondary-color: #a29bfe;
      --accent-color: #00cec9;
      --text-color: #2d3436;
      --background-color: #f9f9f9;
      --card-background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
      --chart-background: rgba(255, 255, 255, 0.95);
      --shadow-color: rgba(108, 92, 231, 0.2);
    }
    body {
      margin: 0;
      font-family: 'Poppins', sans-serif;
      background: var(--background-color);
      color: var(--text-color);
    }
    .main-content {
      padding: 2rem;
      margin-left: 250px;
    }
    .container {
      max-width: 1000px;
      margin: auto;
      background: var(--chart-background);
      border-radius: 15px;
      box-shadow: 0 10px 30px var(--shadow-color);
      padding: 2rem;
    }
    h1 {
      text-align: center;
      color: var(--primary-color);
      margin-bottom: 2rem;
    }
    .dashboard-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 1.5rem;
      margin-bottom: 2rem;
    }
    .dashboard-card {
      background: var(--card-background);
      color: white;
      padding: 1.5rem;
      border-radius: 15px;
      box-shadow: 0 4px 20px var(--shadow-color);
    }
    .dashboard-card h3 {
      font-size: 1rem;
      margin-bottom: 0.5rem;
    }
    .dashboard-card p {
      font-size: 1.4rem;
      font-weight: bold;
    }
    .chart-container {
      background: var(--chart-background);
      border-radius: 15px;
      padding: 2rem;
      box-shadow: 0 5px 20px var(--shadow-color);
    }
    .chart-container h2 {
      font-size: 1.4rem;
      color: var(--primary-color);
      margin-bottom: 1rem;
      text-align: center;
    }
    canvas {
      max-width: 100%;
    }
  </style>
</head>
<body>
  <?php include 'includes/navbar.php'; ?>

  <div class="main-content">
    <div class="container">
      <h1>Budget Analysis</h1>

      <div class="dashboard-grid">
        <div class="dashboard-card">
          <h3>Total Spent This Month</h3>
          <p>₹<?= number_format($currentMonthTotal, 2) ?></p>
        </div>
        <div class="dashboard-card">
          <h3>Monthly Budget</h3>
          <p>₹<?= number_format($userBudget, 2) ?></p>
        </div>
        <div class="dashboard-card">
          <h3>Remaining Budget</h3>
          <p>₹<?= number_format($remainingBudget, 2) ?></p>
        </div>
        <div class="dashboard-card">
          <h3>% Spent</h3>
          <p><?= number_format($percentageSpent, 2) ?>%</p>
        </div>
        <div class="dashboard-card">
          <h3>Top Category</h3>
          <p><?= htmlspecialchars($topCategory) ?> (₹<?= number_format($topAmount, 2) ?>)</p>
        </div>
      </div>

      <div class="chart-container">
        <h2>Category-wise Spending</h2>
        <canvas id="categoryChart"></canvas>
      </div>
    </div>
  </div>

  <script>
    const ctx = document.getElementById('categoryChart').getContext('2d');
    new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels: <?= json_encode($categoryLabels) ?>,
        datasets: [{
          data: <?= json_encode($categoryValues) ?>,
          backgroundColor: [
            '#6c5ce7', '#00b894', '#fd79a8', '#0984e3',
            '#e17055', '#d63031', '#fab1a0', '#55efc4',
            '#ffeaa7', '#81ecec', '#a29bfe', '#fdcb6e'
          ]
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              color: '#2d3436'
            }
          }
        }
      }
    });
  </script>
</body>
</html>
