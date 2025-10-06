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

$userId = $_SESSION['user_id'];

// CSRF Protection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("CSRF token validation failed");
    }
}
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Handle adding or updating monthly budget
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_budget'])) {
    $newBudget = filter_var($_POST['monthly_budget'], FILTER_VALIDATE_FLOAT);
    if (setMonthlyBudget($userId, $newBudget)) {
        $success_message = "Monthly budget updated successfully.";

        // ✅ Always refresh from DB after update
        $currentBudget = getMonthlyBudget($userId);
        $currentMonthSummary = getCurrentMonthSummary($userId);

    } else {
        $error_message = "Failed to update monthly budget. Please try again.";
    }
}

// 🔄 Fetch current monthly budget only if not already refreshed
if (!isset($currentBudget)) {
    $currentBudget = getMonthlyBudget($userId);
}

// 🔄 Fetch current month summary only if not already refreshed
if (!isset($currentMonthSummary)) {
    $currentMonthSummary = getCurrentMonthSummary($userId);
}

// Fetch monthly expenses for the past 6 months
$monthlyExpenses = getMonthlyExpenses($userId);

// Get expense breakdown by category for the current month
$expensesByCategory = getCategoryExpenses($userId);

/* -----------------------------------------------------------------------
   ✅ NEW: Canonical Budget Summary (always correct & up-to-date)
   - Reads monthly budget from `monthly_budgets` for current month/year
   - Falls back to `tbluser.MonthlyBudget` if no monthly record
   - Computes this month’s total spent from `tblexpense`
   - Derives Remaining & Usage%
------------------------------------------------------------------------ */


$currentMonth = (int)date('n');
$currentYear  = (int)date('Y');

// Ensure $conn (mysqli) exists via includes/database.php
if (isset($conn) && $conn instanceof mysqli) {
    // 1) Try monthly_budgets
    if ($stmt = $conn->prepare("
        SELECT Budget FROM monthly_budgets
        WHERE UserId = ? AND Month = ? AND Year = ?
        LIMIT 1
    ")) {
        $stmt->bind_param("iii", $userId, $currentMonth, $currentYear);
        $stmt->execute();
        $stmt->bind_result($mb);
        if ($stmt->fetch()) {
            $budgetSummary['MonthlyBudget'] = (float)$mb;
        }
        $stmt->close();
    }

    // 2) Fallback to tbluser if monthly_budgets had no row
    if ($budgetSummary['MonthlyBudget'] === 0.0) {
        if ($stmt = $conn->prepare("SELECT MonthlyBudget FROM tbluser WHERE ID = ? LIMIT 1")) {
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $stmt->bind_result($ub);
            if ($stmt->fetch()) {
                $budgetSummary['MonthlyBudget'] = (float)$ub;
            }
            $stmt->close();
        }
    }

    // 3) Sum this month’s expenses
    if ($stmt = $conn->prepare("
        SELECT IFNULL(SUM(ExpenseCost), 0)
        FROM tblexpense
        WHERE UserId = ? AND MONTH(ExpenseDate) = ? AND YEAR(ExpenseDate) = ?
    ")) {
        $stmt->bind_param("iii", $userId, $currentMonth, $currentYear);
        $stmt->execute();
        $stmt->bind_result($spent);
        if ($stmt->fetch()) {
            $budgetSummary['TotalExpense'] = (float)$spent;
        }
        $stmt->close();
    }

    // 4) Derive remaining & usage
    $budgetSummary['RemainingBudget']  = $budgetSummary['MonthlyBudget'] - $budgetSummary['TotalExpense'];
    $budgetSummary['BudgetPercentage'] = ($budgetSummary['MonthlyBudget'] > 0)
        ? ($budgetSummary['TotalExpense'] / $budgetSummary['MonthlyBudget']) * 100
        : 0.0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monthly Budget and Expenses - Elegant Expense Tracker</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap');

        :root {
            --primary-color: #810562ff;
            --secondary-color: #3a054dff;
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

        h1, h2, h3 {
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

        input[type="number"] {
            width: 100%;
            padding: 0.75rem;
            background: var(--input-bg);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 10px;
            color: #2c3e50;
            transition: all 0.3s ease;
            font-size: 1rem;
        }

        input[type="number"]:focus {
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

        .summary-section {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
        }

        .summary-box {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            flex-basis: calc(50% - 1rem);
            border: var(--glass-border);
            transition: all 0.3s ease;
        }

        .summary-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
        }

        .summary-box h3 {
            color: #e67e22;
            margin-bottom: 1rem;
            text-shadow: 1px 1px 2px rgba(255, 255, 255, 0.1);
        }

        .summary-box p {
            color: #34495e;
            margin-bottom: 0.5rem;
            text-shadow: 1px 1px 2px rgba(255, 255, 255, 0.1);
        }

        .chart-container {
            width: 100%;
            height: 300px;
            margin-top: 2rem;
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
            .summary-box {
                flex-basis: 100%;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="main-content">
        <div class="container">
            <h1>Monthly Budget and Expenses</h1>
            <?php if (isset($error_message)): ?>
                <div class="message error-message"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            <?php if (isset($success_message)): ?>
                <div class="message success-message"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <div class="form-group">
                    <label for="monthly_budget">Monthly Budget (₹)</label>
                    <input type="number" name="monthly_budget" id="monthly_budget" required step="0.01" min="0" value="<?php echo $currentBudget; ?>">
                </div>
                <button type="submit" class="submit-btn" name="update_budget">Update Monthly Budget</button>
            </form>
        </div>

        <div class="container">
            <h2>Current Month Overview</h2>
            <div class="summary-section">
                <!-- ✅ REPLACED: Old summary (using $currentMonthSummary) with canonical summary card -->
                
                <div class="summary-box">
                    <h3>Expenses by Category</h3>
                    <ul>
                        <?php foreach ($expensesByCategory as $category => $amount): ?>
                            <li><?php echo htmlspecialchars($category); ?>: ₹<?php echo number_format($amount, 2); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <div class="chart-container">
                <div id="expenseLineChart"></div>
            </div>
        </div>

        <div class="container">
            <h2>Monthly Expenses History</h2>
            <div class="chart-container">
                <div id="monthlyExpensesChart"></div>
            </div>
        </div>
    </div>

    <script>
        // Line Chart for Expenses by Category
        const lineOptions = {
            chart: {
                type: 'line',
                height: 300,
                toolbar: {
                    show: false
                }
            },
            series: [{
                name: 'Expenses',
                data: <?php echo json_encode(array_values($expensesByCategory)); ?>
            }],
            xaxis: {
                categories: <?php echo json_encode(array_keys($expensesByCategory)); ?>,
                title: {
                    text: 'Categories'
                }
            },
            yaxis: {
                title: {
                    text: 'Expenses (₹)'
                },
                min: 0
            },
            title: {
                text: 'Expenses by Category',
                align: 'center',
                style: {
                    fontSize: '16px',
                    fontWeight: 'bold',
                    color: '#2c3e50'
                }
            },
            tooltip: {
                theme: 'dark',
                style: {
                    fontSize: '14px',
                    fontWeight: 'bold',
                    color: '#fff',
                },
                x: {
                    show: true,
                },
                y: {
                    formatter: function(val) {
                        return '₹' + val;
                    }
                }
            },
            colors: ['#FF6384'],
        };

        const lineChart = new ApexCharts(document.querySelector("#expenseLineChart"), lineOptions);
        lineChart.render();

        // Bar Chart for Monthly Expenses History
        const barOptions = {
            chart: {
                type: 'bar',
                height: 300,
                toolbar: {
                    show: false
                }
            },
            series: [{
                name: 'Monthly Expenses',
                data: <?php echo json_encode(array_values($monthlyExpenses)); ?>
            }],
            xaxis: {
                categories: <?php echo json_encode(array_keys($monthlyExpenses)); ?>,
                title: {
                    text: 'Month'
                }
            },
            yaxis: {
                title: {
                    text: 'Expenses (₹)'
                },
                min: 0
            },
            title: {
                text: 'Monthly Expenses History',
                align: 'center',
                style: {
                    fontSize: '16px',
                    fontWeight: 'bold',
                    color: '#2c3e50'
                }
            },
            tooltip: {
                theme: 'dark',
                style: {
                    fontSize: '14px',
                    fontWeight: 'bold',
                    color: '#fff',
                },
                x: {
                    show: true,
                },
                y: {
                    formatter: function(val) {
                        return '₹' + val;
                    }
                }
            },
            colors: ['#4BC0C0'],
            dataLabels: {
                enabled: false
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '55%',
                    endingShape: 'rounded'
                }
            }
        };

        const barChart = new ApexCharts(document.querySelector("#monthlyExpensesChart"), barOptions);
        barChart.render();
    </script>
</body>
</html>
