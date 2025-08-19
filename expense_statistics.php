<?php
session_start();
require_once 'includes/database.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];

if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Database connection (\$conn) not found.");
}

// Fetch groups for this user
$groups = [];
$stmt = $conn->prepare("
    SELECT g.id AS group_id, g.group_name, g.admin_user_id, g.GroupBudget
    FROM family_groups g
    JOIN family_members m ON m.group_id = g.id
    WHERE m.user_id = ?
    GROUP BY g.id
    ORDER BY g.group_name
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) {
    $groups[] = $r;
}
$stmt->close();

$selectedGroupId = null;
if (isset($_GET['group_id'])) {
    $selectedGroupId = (int)$_GET['group_id'];
} elseif (!empty($groups)) {
    $selectedGroupId = (int)$groups[0]['group_id'];
}
if ($selectedGroupId) {
    $allowedIds = array_column($groups, 'group_id');
    if (!in_array($selectedGroupId, $allowedIds, true)) {
        $selectedGroupId = !empty($groups) ? (int)$groups[0]['group_id'] : null;
    }
}

$groupInfo = null;
$members = [];
if ($selectedGroupId) {
    $stmt = $conn->prepare("SELECT * FROM family_groups WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $selectedGroupId);
    $stmt->execute();
    $groupInfo = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $stmt = $conn->prepare("
        SELECT u.ID, u.FirstName, u.LastName, COALESCE(u.MonthlyBudget,0) AS MonthlyBudget
        FROM tbluser u
        JOIN family_members fm ON fm.user_id = u.ID
        WHERE fm.group_id = ?
        ORDER BY u.FirstName, u.LastName
    ");
    $stmt->bind_param("i", $selectedGroupId);
    $stmt->execute();
    $members = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Stats variables
$monthlyLabels = $monthlyValues = [];
$categoryLabels = $categoryValues = [];
$last7Labels = $last7Values = [];
$memberStats = [];
$groupBudget = 0;
$currentMonthTotal = 0;
$remainingBudget = 0;
$budgetPercentage = 0;

if ($selectedGroupId) {
    // --- Monthly chart ---
    $monthsKeys = [];
    $dt = new DateTime('first day of this month');
    for ($i = 11; $i >= 0; $i--) {
        $m = clone $dt;
        $m->modify("-{$i} months");
        $key = $m->format('Y-m');
        $monthsKeys[$key] = $m->format('M Y');
    }
    $monthlyLabels = array_values($monthsKeys);
    $startDate = (new DateTime(reset(array_keys($monthsKeys)) . '-01'))->format('Y-m-d');
    $endDate = (new DateTime(end(array_keys($monthsKeys)) . '-01'))->modify('last day of this month')->format('Y-m-d');

    $stmt = $conn->prepare("
        SELECT YEAR(ExpenseDate) AS yr, MONTH(ExpenseDate) AS mon, IFNULL(SUM(ExpenseCost),0) AS total
        FROM tblexpense
        WHERE (GroupID = ? OR GroupID IS NULL) AND ExpenseDate BETWEEN ? AND ?
        GROUP BY yr, mon
        ORDER BY yr, mon
    ");
    $stmt->bind_param("iss", $selectedGroupId, $startDate, $endDate);
    $stmt->execute();
    $res = $stmt->get_result();
    $monthlyMap = [];
    while ($r = $res->fetch_assoc()) {
        $k = sprintf('%04d-%02d', $r['yr'], $r['mon']);
        $monthlyMap[$k] = (float)$r['total'];
    }
    $stmt->close();
    foreach (array_keys($monthsKeys) as $key) {
        $monthlyValues[] = $monthlyMap[$key] ?? 0;
    }

    // --- Category chart ---
    $stmt = $conn->prepare("
        SELECT COALESCE(c.CategoryName, 'Uncategorized') AS name, IFNULL(SUM(e.ExpenseCost),0) AS total
        FROM tblexpense e
        LEFT JOIN tblcategory c ON c.ID = e.CategoryID
        WHERE (e.GroupID = ? OR e.GroupID IS NULL)
        GROUP BY name
        ORDER BY total DESC
        LIMIT 20
    ");
    $stmt->bind_param("i", $selectedGroupId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $categoryLabels[] = $r['name'];
        $categoryValues[] = (float)$r['total'];
    }
    $stmt->close();

    // --- Last 7 days chart ---
    $dates7 = [];
    for ($i = 6; $i >= 0; $i--) {
        $d = (new DateTime())->modify("-{$i} days");
        $dates7[$d->format('Y-m-d')] = $d->format('d M');
    }
    $last7Labels = array_values($dates7);
    $start7 = array_keys($dates7)[0];
    $end7 = array_keys($dates7)[6];

    $stmt = $conn->prepare("
        SELECT ExpenseDate, IFNULL(SUM(ExpenseCost),0) AS total
        FROM tblexpense
        WHERE (GroupID = ? OR GroupID IS NULL) AND ExpenseDate BETWEEN ? AND ?
        GROUP BY ExpenseDate
        ORDER BY ExpenseDate ASC
    ");
    $stmt->bind_param("iss", $selectedGroupId, $start7, $end7);
    $stmt->execute();
    $res = $stmt->get_result();
    $map7 = [];
    while ($r = $res->fetch_assoc()) {
        $map7[$r['ExpenseDate']] = (float)$r['total'];
    }
    $stmt->close();
    foreach (array_keys($dates7) as $k) {
        $last7Values[] = $map7[$k] ?? 0;
    }

    // --- Member stats ---
    $groupBudget = (float)$groupInfo['GroupBudget'];
    if ($groupBudget <= 0) {
        foreach ($members as $m) {
            $groupBudget += (float)$m['MonthlyBudget'];
        }
    }

    $y = (int)date('Y');
    $m = (int)date('m');

    if (!empty($members)) {
        $stmt = $conn->prepare("
            SELECT UserId, IFNULL(SUM(ExpenseCost),0) AS total
            FROM tblexpense
            WHERE (GroupID = ? OR GroupID IS NULL) AND YEAR(ExpenseDate) = ? AND MONTH(ExpenseDate) = ?
            GROUP BY UserId
        ");
        $stmt->bind_param("iii", $selectedGroupId, $y, $m);
        $stmt->execute();
        $res = $stmt->get_result();
        $perUserTotals = [];
        while ($r = $res->fetch_assoc()) {
            $perUserTotals[(int)$r['UserId']] = (float)$r['total'];
        }
        $stmt->close();

        foreach ($members as $mem) {
            $id = (int)$mem['ID'];
            $memberStats[] = [
                'ID' => $id,
                'name' => trim($mem['FirstName'] . ' ' . $mem['LastName']),
                'monthlyBudget' => (float)$mem['MonthlyBudget'],
                'thisMonthTotal' => $perUserTotals[$id] ?? 0
            ];
        }
    }

    $stmt = $conn->prepare("
        SELECT IFNULL(SUM(ExpenseCost),0) AS total
        FROM tblexpense
        WHERE (GroupID = ? OR GroupID IS NULL) AND YEAR(ExpenseDate) = ? AND MONTH(ExpenseDate) = ?
    ");
    $stmt->bind_param("iii", $selectedGroupId, $y, $m);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $currentMonthTotal = (float)($row['total'] ?? 0);
    $stmt->close();

    $remainingBudget = $groupBudget - $currentMonthTotal;
    $budgetPercentage = $groupBudget > 0 ? ($currentMonthTotal / $groupBudget) * 100 : 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Group Statistics</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.chart-container { width: 500px; height: 300px; display: inline-block; margin: 20px; }
</style>
</head>
<body>
    <!-- Back to Dashboard Button -->
<div style="margin-bottom:20px;">
    <a href="dashboard.php" 
       style="display:inline-block; padding:10px 20px; background:#0984e3; color:#fff; text-decoration:none; border-radius:5px;">
       ← Back to Dashboard
    </a>
</div>
<h1>Group Statistics</h1>


<form method="get" style="margin-bottom:20px;">
    <label>Select Group: </label>
    <select name="group_id" onchange="this.form.submit()">
        <?php foreach ($groups as $g): ?>
            <option value="<?= $g['group_id'] ?>" <?= $g['group_id'] == $selectedGroupId ? 'selected' : '' ?>>
                <?= htmlspecialchars($g['group_name']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</form>




<?php if ($selectedGroupId): ?>
<div class="chart-container"><canvas id="monthlyChart"></canvas></div>
<div class="chart-container"><canvas id="categoryChart"></canvas></div>
<div class="chart-container"><canvas id="last7Chart"></canvas></div>
<?php if (!empty($memberStats)): ?>
<div class="members" style="margin-top: 30px;">
    <h3>Member-Wise Statistics (Current Month)</h3>
    <div style="overflow-x:auto;">
        <table style="width:100%; border-collapse:collapse; background:#fff; border-radius:8px; overflow:hidden;">
            <thead style="background:#6c5ce7; color:#fff;">
                <tr>
                    <th style="padding:10px; text-align:left;">Member</th>
                    <th style="padding:10px; text-align:right;">Monthly Budget (₹)</th>
                    <th style="padding:10px; text-align:right;">This Month's Expense (₹)</th>
                    <th style="padding:10px; text-align:right;">Remaining Budget (₹)</th>
                    <th style="padding:10px; text-align:right;">% Spent</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($memberStats as $ms): 
                    $remaining = $ms['monthlyBudget'] - $ms['thisMonthTotal'];
                    $percSpent = $ms['monthlyBudget'] > 0 ? ($ms['thisMonthTotal'] / $ms['monthlyBudget'] * 100) : 0;
                ?>
                <tr style="border-bottom:1px solid #f0f0f0;">
                    <td style="padding:10px;"><?= safeOutput($ms['name']) ?></td>
                    <td style="padding:10px; text-align:right;"><?= number_format($ms['monthlyBudget'],2) ?></td>
                    <td style="padding:10px; text-align:right;"><?= number_format($ms['thisMonthTotal'],2) ?></td>
                    <td style="padding:10px; text-align:right;"><?= number_format($remaining,2) ?></td>
                    <td style="padding:10px; text-align:right;"><?= number_format($percSpent,2) ?>%</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<script>
new Chart(document.getElementById('monthlyChart'), {
    type: 'line',
    data: { labels: <?= json_encode($monthlyLabels) ?>,
            datasets: [{ label: 'Monthly Expense', data: <?= json_encode($monthlyValues) ?>, borderColor: 'blue', fill: false }] }
});
new Chart(document.getElementById('categoryChart'), {
    type: 'bar',
    data: { labels: <?= json_encode($categoryLabels) ?>,
            datasets: [{ label: 'Category Expense', data: <?= json_encode($categoryValues) ?>, backgroundColor: 'green' }] }
});
new Chart(document.getElementById('last7Chart'), {
    type: 'bar',
    data: { labels: <?= json_encode($last7Labels) ?>,
            datasets: [{ label: 'Last 7 Days', data: <?= json_encode($last7Values) ?>, backgroundColor: 'orange' }] }
});
</script>
<?php endif; ?>
</body>
</html>
