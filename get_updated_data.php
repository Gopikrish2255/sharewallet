<?php
session_start();
require_once 'includes/database.php';
require_once 'includes/functions.php';
require_once 'includes/analytics.php';

$userId = $_SESSION['user_id'];

$data = [
    'monthly_expenses' => getMonthlyExpenses($userId),
    'category_expenses' => getCategoryExpenses($userId),
    'total_expenses' => getTotalExpenses($userId),
    'last_7_days' => getLastSevenDaysExpenses($userId),
    'today_expense' => getTodayExpenses($userId),
    'monthly_budget' => getMonthlyBudget($userId)
];

header('Content-Type: application/json');
echo json_encode($data);