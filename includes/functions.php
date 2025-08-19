<?php
// Robust functions.php for user profile and expense management

// Error reporting and database connection
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '/path/to/error.log'); // Adjust this path as needed

require_once 'database.php'; // Ensure this file exists and establishes a mysqli connection

// Custom exception for database errors
class DatabaseException extends Exception {}

// Redirect function with optional flash message
function redirect($url, $flashMessage = '') {
    if (!empty($flashMessage)) {
        $_SESSION['flash_message'] = $flashMessage;
    }
    header("Location: $url");
    exit();
}

// Sanitize function to clean user input
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(stripslashes(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Validate email function
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Function to update the user profile
function updateProfile(int $userId, string $firstName, string $lastName, string $email, string $mobileNumber): bool {
    global $conn;

    try {
        if (!validateEmail($email)) {
            throw new InvalidArgumentException("Invalid email format");
        }

        $stmt = $conn->prepare("UPDATE tbluser SET FirstName = ?, LastName = ?, Email = ?, MobileNumber = ? WHERE ID = ?");
        if (!$stmt) {
            throw new DatabaseException("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param('ssssi', $firstName, $lastName, $email, $mobileNumber, $userId);
        if (!$stmt->execute()) {
            throw new DatabaseException("Execute failed: " . $stmt->error);
        }

        return $stmt->affected_rows > 0;
    } catch (Exception $e) {
        error_log("Error updating profile: " . $e->getMessage());
        return false;
    }
}

function editCategory($categoryId, $newCategoryName) {
    global $pdo;

    // Get the logged-in user's ID
    $userId = $_SESSION['user_id'];

    // Prepare and execute the query to update the category name for the logged-in user
    $stmt = $pdo->prepare("
        UPDATE tblcategory 
        SET CategoryName = :categoryName 
        WHERE ID = :categoryId 
        AND UserId = :userId
    ");
    $stmt->bindParam(':categoryName', $newCategoryName);
    $stmt->bindParam(':categoryId', $categoryId);
    $stmt->bindParam(':userId', $userId);

    // Execute the query and return true if successful, otherwise false
    return $stmt->execute();
}





function deleteCategory($categoryId, $userId) {
    global $conn;

    try {
        // Validate inputs
        if (!is_numeric($categoryId) || !is_numeric($userId) || $userId <= 0 || $categoryId <= 0) {
            throw new Exception("Invalid category ID or user ID");
        }

        // Start transaction to ensure data consistency
        $conn->begin_transaction();

        // First, check if the category exists and belongs to the user
        $checkStmt = $conn->prepare("SELECT ID FROM tblcategory WHERE ID = ? AND UserId = ?");
        if (!$checkStmt) {
            throw new Exception("Error preparing check statement");
        }
        $checkStmt->bind_param("ii", $categoryId, $userId);
        if (!$checkStmt->execute()) {
            throw new Exception("Error executing check statement");
        }
        $result = $checkStmt->get_result();

        if ($result->num_rows === 0) {
            throw new Exception("Category not found or insufficient permissions");
        }

        // Check if there are any expenses using this category
        $expenseCheckStmt = $conn->prepare("SELECT COUNT(*) as count FROM tblexpense WHERE CategoryID = ?");
        $expenseCheckStmt->bind_param("i", $categoryId);
        $expenseCheckStmt->execute();
        $expenseResult = $expenseCheckStmt->get_result();
        $expenseCount = $expenseResult->fetch_assoc()['count'];

        if ($expenseCount > 0) {
            throw new Exception("Cannot delete category: There are expenses linked to this category");
        }

        // Delete the category
        $deleteStmt = $conn->prepare("DELETE FROM tblcategory WHERE ID = ? AND UserId = ?");
        if (!$deleteStmt) {
            throw new Exception("Error preparing delete statement");
        }

        $deleteStmt->bind_param("ii", $categoryId, $userId);
        if (!$deleteStmt->execute()) {
            throw new Exception("Error executing delete statement");
        }

        if ($deleteStmt->affected_rows === 0) {
            throw new Exception("No category was deleted");
        }

        $conn->commit();
        return true;

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error deleting category: " . $e->getMessage());
        return false;
    }
}

// Function to retrieve user details
function getUserDetails(int $userId): array {
    global $conn;

    try {
        $stmt = $conn->prepare("SELECT FirstName, LastName, Email, MobileNumber, Gender, MonthlyBudget FROM tbluser WHERE ID = ?");
        if (!$stmt) {
            throw new DatabaseException("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param('i', $userId);
        if (!$stmt->execute()) {
            throw new DatabaseException("Execute failed: " . $stmt->error);
        }

        $result = $stmt->get_result();
        $userDetails = $result->fetch_assoc();

        return $userDetails ?: [];
    } catch (Exception $e) {
        error_log("Error retrieving user details: " . $e->getMessage());
        return [];
    }
}

// Function to get user analytics
function getUserAnalytics(int $userId): ?array {
    // This function is not applicable as the tbluser_analytics table doesn't exist in the provided schema
    return null;
}

// Function to get total expenses for a user
function getTotalExpenses(int $userId): float {
    global $conn;

    try {
        $stmt = $conn->prepare("SELECT SUM(ExpenseCost) AS TotalExpense FROM tblexpense WHERE UserId = ?");
        if (!$stmt) {
            throw new DatabaseException("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("i", $userId);
        if (!$stmt->execute()) {
            throw new DatabaseException("Execute failed: " . $stmt->error);
        }

        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return (float)($row['TotalExpense'] ?? 0);
    } catch (Exception $e) {
        error_log("Error calculating total expenses: " . $e->getMessage());
        return 0;
    }
}

// Function to get monthly expenses for a user
function getMonthlyExpenses(int $userId): array {
    global $conn;

    try {
        $query = "SELECT 
                    SUM(CASE WHEN MONTH(ExpenseDate) = 1 THEN ExpenseCost ELSE 0 END) AS january,
                    SUM(CASE WHEN MONTH(ExpenseDate) = 2 THEN ExpenseCost ELSE 0 END) AS february,
                    SUM(CASE WHEN MONTH(ExpenseDate) = 3 THEN ExpenseCost ELSE 0 END) AS march,
                    SUM(CASE WHEN MONTH(ExpenseDate) = 4 THEN ExpenseCost ELSE 0 END) AS april,
                    SUM(CASE WHEN MONTH(ExpenseDate) = 5 THEN ExpenseCost ELSE 0 END) AS may,
                    SUM(CASE WHEN MONTH(ExpenseDate) = 6 THEN ExpenseCost ELSE 0 END) AS june,
                    SUM(CASE WHEN MONTH(ExpenseDate) = 7 THEN ExpenseCost ELSE 0 END) AS july,
                    SUM(CASE WHEN MONTH(ExpenseDate) = 8 THEN ExpenseCost ELSE 0 END) AS august,
                    SUM(CASE WHEN MONTH(ExpenseDate) = 9 THEN ExpenseCost ELSE 0 END) AS september,
                    SUM(CASE WHEN MONTH(ExpenseDate) = 10 THEN ExpenseCost ELSE 0 END) AS october,
                    SUM(CASE WHEN MONTH(ExpenseDate) = 11 THEN ExpenseCost ELSE 0 END) AS november,
                    SUM(CASE WHEN MONTH(ExpenseDate) = 12 THEN ExpenseCost ELSE 0 END) AS december
                FROM tblexpense
                WHERE UserId = ? AND YEAR(ExpenseDate) = YEAR(CURRENT_DATE())
                GROUP BY UserId";

        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new DatabaseException("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("i", $userId);
        if (!$stmt->execute()) {
            throw new DatabaseException("Execute failed: " . $stmt->error);
        }

        $result = $stmt->get_result();
        return $result->fetch_assoc() ?: array_fill_keys(['january', 'february', 'march', 'april', 'may', 'june', 'july', 'august', 'september', 'october', 'november', 'december'], 0);
    } catch (Exception $e) {
        error_log("Error retrieving monthly expenses: " . $e->getMessage());
        return [];
    }
}

// Function to get expenses for a user
function getExpenses(int $userId): array {
    global $conn;

    try {
        $stmt = $conn->prepare("SELECT e.*, c.CategoryName 
                                 FROM tblexpense e 
                                 JOIN tblcategory c ON e.CategoryID = c.ID 
                                 WHERE e.UserId = ? 
                                 ORDER BY e.ExpenseDate DESC");
        if (!$stmt) {
            throw new DatabaseException("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("i", $userId);
        if (!$stmt->execute()) {
            throw new DatabaseException("Execute failed: " . $stmt->error);
        }

        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        error_log("Error retrieving expenses: " . $e->getMessage());
        return [];
    }
}

// Function to get category expenses for a user
function getCategoryExpenses(int $userId): array {
    global $conn;

    $categoryExpenses = [];

    try {
        // Define the SQL query
        $query = "
            SELECT 
                c.CategoryName,
                COALESCE(SUM(e.ExpenseCost), 0) AS TotalExpense
            FROM tblcategory c
            LEFT JOIN tblexpense e 
                ON c.ID = e.CategoryID AND e.UserId = ?
            WHERE c.UserID = ? -- Ensures only categories created by the logged-in user
            GROUP BY c.ID, c.CategoryName
            ORDER BY c.CategoryName";

        // Prepare the statement
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Statement preparation failed: " . $conn->error);
        }

        // Bind parameters
        $stmt->bind_param("ii", $userId, $userId);

        // Execute the statement
        if (!$stmt->execute()) {
            throw new Exception("Statement execution failed: " . $stmt->error);
        }

        // Fetch the results
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $categoryExpenses[$row['CategoryName']] = (float)$row['TotalExpense'];
        }

        // Free result set
        $result->free();

    } catch (Exception $e) {
        // Log the error for debugging
        error_log("Error in getCategoryExpenses: " . $e->getMessage());
    } finally {
        // Close the prepared statement
        if (isset($stmt) && $stmt instanceof mysqli_stmt) {
            $stmt->close();
        }
    }

    return $categoryExpenses;
}

// Function to get linked users for a user
function getLinkedUsers(int $userId): array {
    global $conn;

    try {
        $query = "SELECT u.ID, u.FirstName, u.LastName 
                  FROM linked_accounts l 
                  JOIN tbluser u ON l.linked_user_id = u.ID 
                  WHERE l.user_id = ?";

        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new DatabaseException("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("i", $userId);
        if (!$stmt->execute()) {
            throw new DatabaseException("Execute failed: " . $stmt->error);
        }

        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        error_log("Error retrieving linked users: " . $e->getMessage());
        return [];
    }
}

// Function to add a new expense
function addExpense(int $userId, float $expenseCost, string $expenseItem, string $expenseDate, int $categoryId): bool {
    global $conn;

    try {
        // Validate and format the date
        if (!validateDate($expenseDate)) {
            throw new Exception("Invalid date format. Expected YYYY-MM-DD.");
        }

        // Prepare the SQL statement
        $stmt = $conn->prepare("INSERT INTO tblexpense (UserId, ExpenseCost, ExpenseItem, ExpenseDate, CategoryID) VALUES (?, ?, ?, ?, ?)");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        // Bind parameters
        $stmt->bind_param("idssi", $userId, $expenseCost, $expenseItem, $expenseDate, $categoryId);

        // Execute the statement
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        return $stmt->affected_rows > 0; // Return true if at least one row was affected
    } catch (Exception $e) {
        error_log("Error adding expense: " . $e->getMessage());
        return false; // Return false on failure
    }
}

function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}



// Function to get categories from the database
function getCategories($userId = null): array {
    global $conn;
    try {
        // Use the logged-in user's ID if not provided
        if ($userId === null) {
            $userId = $_SESSION['user_id']; // Get the logged-in user's ID from the session
        }

        // Prepare the query to select categories for the logged-in user
        $query = "SELECT ID, UserId, CategoryName FROM tblcategory WHERE UserId = ? ORDER BY CategoryName";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new DatabaseException("Prepare failed: " . $conn->error);
        }

        // Bind the logged-in user's ID to the query
        $stmt->bind_param("i", $userId);

        // Execute the query and check for success
        if (!$stmt->execute()) {
            throw new DatabaseException("Execute failed: " . $stmt->error);
        }

        // Fetch and return the results as an associative array
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        // Log any errors and return an empty array
        error_log("Error retrieving categories: " . $e->getMessage());
        return [];
    }
}

// Function to safely output HTML
function safeOutput($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Function to generate a select element for categories
function generateCategorySelect($selectedId = null, $userId = null) {
    // Fetch categories filtered by UserID (assuming getCategories accepts a userId parameter)
    $categories = getCategories($userId);
    
    $output = '<select name="category" id="category">';
    foreach ($categories as $category) {
        $selected = ($selectedId == $category['ID']) ? 'selected' : '';
        $output .= '<option value="' . safeOutput($category['ID']) . '" ' . $selected . '>' . 
                   safeOutput($category['CategoryName']) . '</option>';
    }
    $output .= '</select>';
    
    return $output;
}


// Function to get expenses for the last 7 days
function getLastSevenDaysExpenses(int $userId): float {
    global $conn;

    try {
        $stmt = $conn->prepare("SELECT SUM(ExpenseCost) AS TotalExpense 
                                 FROM tblexpense 
                                 WHERE UserId = ? 
                                 AND ExpenseDate >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
        if (!$stmt) {
            throw new DatabaseException("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("i", $userId);
        if (!$stmt->execute()) {
            throw new DatabaseException("Execute failed: " . $stmt->error);
        }

        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return (float)($row['TotalExpense'] ?? 0);
    } catch (Exception $e) {
        error_log("Error calculating last 7 days expenses: " . $e->getMessage());
        return 0;
    }
}

// Function to get today's expenses
function getTodayExpenses(int $userId): float {
    global $conn;

    try {
        $stmt = $conn->prepare("SELECT SUM(ExpenseCost) AS TotalExpense 
                                 FROM tblexpense 
                                 WHERE UserId = ? 
                                 AND DATE(ExpenseDate) = CURDATE()");
        if (!$stmt) {
            throw new DatabaseException("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("i", $userId);
        if (!$stmt->execute()) {
            throw new DatabaseException("Execute failed: " . $stmt->error);
        }

        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return (float)($row['TotalExpense'] ?? 0);
    } catch (Exception $e) {
        error_log("Error calculating today's expenses: " . $e->getMessage());
        return 0;
    }
}

// Function to get monthly budget

// Function to update an existing expense
function updateExpense(int $expenseId, int $userId, string $expenseDate, string $expenseItem, float $expenseCost, int $categoryId): bool {
    global $conn;
    try {
        $stmt = $conn->prepare("UPDATE tblexpense 
                                SET ExpenseDate = ?, ExpenseItem = ?, ExpenseCost = ?, CategoryID = ? 
                                WHERE ID = ? AND UserId = ?");
        if (!$stmt) {
            throw new DatabaseException("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("ssddii", $expenseDate, $expenseItem, $expenseCost, $categoryId, $expenseId, $userId);
        if (!$stmt->execute()) {
            throw new DatabaseException("Execute failed: " . $stmt->error);
        }

        return $stmt->affected_rows > 0;
    } catch (Exception $e) {
        error_log("Error updating expense: " . $e->getMessage());
        return false;
    }
}

// Function to delete an expense
function deleteExpense(int $expenseId, int $userId): bool {
    global $conn;

    try {
        $stmt = $conn->prepare("DELETE FROM tblexpense WHERE ID = ? AND UserId = ?");
        if (!$stmt) {
            throw new DatabaseException("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("ii", $expenseId, $userId);
        if (!$stmt->execute()) {
            throw new DatabaseException("Execute failed: " . $stmt->error);
        }

        return $stmt->affected_rows > 0;
    } catch (Exception $e) {
        error_log("Error deleting expense: " . $e->getMessage());
        return false;
    }
}

// Function to get expenses for the last 7 days
function getLast7DaysExpenses(int $userId): array {
    global $conn;

    try {
        $query = "SELECT 
                    DATE(ExpenseDate) as date,
                    SUM(ExpenseCost) as total
                FROM tblexpense
                WHERE UserId = ? AND ExpenseDate >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                GROUP BY DATE(ExpenseDate)
                ORDER BY DATE(ExpenseDate)";

        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new DatabaseException("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("i", $userId);
        if (!$stmt->execute()) {
            throw new DatabaseException("Execute failed: " . $stmt->error);
        }

        $result = $stmt->get_result();
        $dailyExpenses = [];
        while ($row = $result->fetch_assoc()) {
            $dailyExpenses[$row['date']] = (float)$row['total'];
        }

        // Fill in any missing days with 0
        $last7Days = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $last7Days[$date] = $dailyExpenses[$date] ?? 0;
        }

        return $last7Days;
    } catch (Exception $e) {
        error_log("Error retrieving last 7 days expenses: " . $e->getMessage());
        return [];
    }
}

// Function to set monthly budget


// Function to get link requests for a user
function getLinkRequests(int $userId): array {
    global $conn;

    try {
        $query = "SELECT lr.id, lr.sender_id, u.FirstName, u.LastName, lr.request_date
                  FROM link_requests lr
                  JOIN tbluser u ON lr.sender_id = u.ID
                  WHERE lr.receiver_id = ? AND lr.status = 'pending'";

        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new DatabaseException("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("i", $userId);
        if (!$stmt->execute()) {
            throw new DatabaseException("Execute failed: " . $stmt->error);
        }

        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        error_log("Error retrieving link requests: " . $e->getMessage());
        return [];
    }
}

// Function to send a link request
function sendLinkRequest(int $senderId, int $receiverId): bool {
    global $conn;

    try {
        $stmt = $conn->prepare("INSERT INTO link_requests (sender_id, receiver_id) VALUES (?, ?)");
        if (!$stmt) {
            throw new DatabaseException("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("ii", $senderId, $receiverId);
        if (!$stmt->execute()) {
            throw new DatabaseException("Execute failed: " . $stmt->error);
        }

        return $stmt->affected_rows > 0;
    } catch (Exception $e) {
        error_log("Error sending link request: " . $e->getMessage());
        return false;
    }
}

// Function to accept a link request
function acceptLinkRequest(int $requestId, int $userId): bool {
    global $conn;

    try {
        $conn->begin_transaction();

        // Update link request status
        $stmt = $conn->prepare("UPDATE link_requests SET status = 'accepted' WHERE id = ? AND receiver_id = ?");
        if (!$stmt) {
            throw new DatabaseException("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("ii", $requestId, $userId);
        if (!$stmt->execute()) {
            throw new DatabaseException("Execute failed: " . $stmt->error);
        }

        if ($stmt->affected_rows == 0) {
            throw new DatabaseException("No matching link request found");
        }

        // Get sender_id
        $stmt = $conn->prepare("SELECT sender_id FROM link_requests WHERE id = ?");
        $stmt->bind_param("i", $requestId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $senderId = $row['sender_id'];

        // Add to linked_accounts
        $stmt = $conn->prepare("INSERT INTO linked_accounts (user_id, linked_user_id) VALUES (?, ?), (?, ?)");
        $stmt->bind_param("iiii", $userId, $senderId, $senderId, $userId);
        if (!$stmt->execute()) {
            throw new DatabaseException("Execute failed: " . $stmt->error);
        }

        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error accepting link request: " . $e->getMessage());
        return false;
    }
}

// Function to decline a link request
function declineLinkRequest(int $requestId, int $userId): bool {
    global $conn;

    try {
        $stmt = $conn->prepare("UPDATE link_requests SET status = 'declined' WHERE id = ? AND receiver_id = ?");
        if (!$stmt) {
            throw new DatabaseException("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("ii", $requestId, $userId);
        if (!$stmt->execute()) {
            throw new DatabaseException("Execute failed: " . $stmt->error);
        }

        return $stmt->affected_rows > 0;
    } catch (Exception $e) {
        error_log("Error declining link request: " . $e->getMessage());
        return false;
    }
}

// Function to get summary data for the current month

function getcategoryName($categoryID) {
    // Assuming you have a database connection called $conn
    global $conn;

    // Prepare the SQL statement to get the category name
    $sql = "SELECT CategoryName FROM tblcategory WHERE ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $categoryID);
    $stmt->execute();
    
    // Fetch the result
    $result = $stmt->get_result();
    
    // Check if the category exists
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['CategoryName'];
    } else {
        return "Unknown"; // Return a default value if the category doesn't exist
    }
}
function searchUsers($searchTerm, $currentUserId) {
    global $conn;

    try {
        $searchTerm = "%$searchTerm%";
        $stmt = $conn->prepare("SELECT ID, FirstName, LastName 
                                FROM tbluser 
                                WHERE (FirstName LIKE ? OR LastName LIKE ?) 
                                AND ID != ? 
                                AND ID NOT IN (SELECT linked_user_id FROM linked_accounts WHERE user_id = ?)");
        if (!$stmt) {
            throw new DatabaseException("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("ssii", $searchTerm, $searchTerm, $currentUserId, $currentUserId);
        if (!$stmt->execute()) {
            throw new DatabaseException("Execute failed: " . $stmt->error);
        }

        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        error_log("Error searching users: " . $e->getMessage());
        return [];
    }
}
function unlinkUser($userId, $linkedUserId) {
    global $conn;

    try {
        $stmt = $conn->prepare("DELETE FROM linked_accounts 
                                WHERE (user_id = ? AND linked_user_id = ?) 
                                OR (user_id = ? AND linked_user_id = ?)");
        if (!$stmt) {
            throw new DatabaseException("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("iiii", $userId, $linkedUserId, $linkedUserId, $userId);
        if (!$stmt->execute()) {
            throw new DatabaseException("Execute failed: " . $stmt->error);
        }

        return $stmt->affected_rows > 0;
    } catch (Exception $e) {
        error_log("Error unlinking user: " . $e->getMessage());
        return false;
    }
}
// Add this function to your existing functions.php file


// Function to check if a new budget entry is needed

// Function to set monthly budget

// Function to get budget for a specific month

// Update getCurrentMonthSummary function

// Function to check if a new budget entry is needed
function needsBudgetEntry($userId) {
    global $conn;
    $stmt = $conn->prepare("SELECT LastBudgetEntry FROM tbluser WHERE ID = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user['LastBudgetEntry']) {
        return true;
    }

    $lastEntry = new DateTime($user['LastBudgetEntry']);
    $now = new DateTime();

    return $lastEntry->format('Y-m') !== $now->format('Y-m');
}

// Function to set monthly budget
function setMonthlyBudget($userId, $budget) {
    global $conn;
    $conn->begin_transaction();
    
    try {
        // Update current budget in tbluser
        $stmt = $conn->prepare("UPDATE tbluser SET MonthlyBudget = ?, LastBudgetEntry = CURRENT_DATE() WHERE ID = ?");
        $stmt->bind_param("di", $budget, $userId);
        $stmt->execute();
        
        // Insert into monthly_budgets table
        $year = date('Y');
        $month = date('n');
        $stmt = $conn->prepare("INSERT INTO monthly_budgets (UserId, Year, Month, Budget) VALUES (?, ?, ?, ?)
                                ON DUPLICATE KEY UPDATE Budget = ?");
        $stmt->bind_param("iiidd", $userId, $year, $month, $budget, $budget);
        $stmt->execute();
        
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error setting monthly budget: " . $e->getMessage());
        return false;
    }
}

// Function to get budget for a specific month
function getMonthlyBudget($userId) {
    global $conn;
    $stmt = $conn->prepare("SELECT MonthlyBudget FROM tbluser WHERE ID = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $budget = $result->fetch_assoc();
    return $budget ? $budget['MonthlyBudget'] : null;
}
// Update getCurrentMonthSummary function
function getCurrentMonthSummary($userId) {
    global $conn;
    $year = date('Y');
    $month = date('n');

    $query = "SELECT 
                COALESCE(SUM(e.ExpenseCost), 0) as TotalExpense,
                COALESCE(mb.Budget, u.MonthlyBudget) as MonthlyBudget
              FROM tbluser u
              LEFT JOIN tblexpense e ON u.ID = e.UserId AND YEAR(e.ExpenseDate) = ? AND MONTH(e.ExpenseDate) = ?
              LEFT JOIN monthly_budgets mb ON u.ID = mb.UserId AND mb.Year = ? AND mb.Month = ?
              WHERE u.ID = ?
              GROUP BY u.ID, mb.Budget, u.MonthlyBudget";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("iiiii", $year, $month, $year, $month, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $summary = $result->fetch_assoc();

    $summary['RemainingBudget'] = $summary['MonthlyBudget'] - $summary['TotalExpense'];
    $summary['BudgetPercentage'] = $summary['MonthlyBudget'] > 0 ? ($summary['TotalExpense'] / $summary['MonthlyBudget']) * 100 : 0;

    return $summary;
}

function checkBudgetUpdateReminder($userId) {
    // Check if it's the first day of the month
    if (date('j') === '1') {
        global $conn;
        
        // Get the last budget entry date
        $stmt = $conn->prepare("SELECT LastBudgetEntry FROM tbluser WHERE ID = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        // If LastBudgetEntry is not this month, return a reminder message
        if ($user && $user['LastBudgetEntry'] < date('Y-m-01')) {
            return "It's a new month! Please update your budget for " . date('F Y') . ".";
        }
    }
    return null;
}
// ... (previous functions remain the same)

// Function to add a new category
function addCategory($categoryName) {
    global $conn;
    try {
        // Retrieve the logged-in user's ID from the session
        $userId = $_SESSION['user_id'];

        // Prepare the SQL query to insert the category along with the UserId
        $stmt = $conn->prepare("INSERT INTO tblcategory (CategoryName, UserId) VALUES (?, ?)");
        if (!$stmt) {
            throw new DatabaseException("Prepare failed: " . $conn->error);
        }

        // Bind the parameters for category name and user ID
        $stmt->bind_param("si", $categoryName, $userId);

        // Execute the query and check for success
        if (!$stmt->execute()) {
            throw new DatabaseException("Execute failed: " . $stmt->error);
        }

        // Return true if the category was successfully added
        return $stmt->affected_rows > 0;
    } catch (Exception $e) {
        // Log any error that occurs during the process
        error_log("Error adding category: " . $e->getMessage());
        return false;
    }
}


// ... (remaining functions stay the same)


function editAutoExpense($expenseId, $expenseItem, $expenseCost, $categoryId, $frequency, $nextDueDate) {
    global $conn;
    $stmt = $conn->prepare("UPDATE auto_expenses SET ExpenseItem = ?, ExpenseCost = ?, CategoryID = ?, Frequency = ?, NextDueDate = ? WHERE ID = ?");
    $stmt->bind_param("sdissi", $expenseItem, $expenseCost, $categoryId, $frequency, $nextDueDate, $expenseId);
    return $stmt->execute();
}



function processAutoExpenses() {
    global $conn;
    $today = date('Y-m-d');
    
    // Fetch all auto expenses due today or earlier
    $stmt = $conn->prepare("SELECT * FROM auto_expenses WHERE NextDueDate <= ?");
    $stmt->bind_param("s", $today);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($expense = $result->fetch_assoc()) {
        // Add the expense to tblexpense
        $stmt = $conn->prepare("INSERT INTO tblexpense (UserId, ExpenseDate, ExpenseItem, ExpenseCost, CategoryID) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issdi", $expense['UserId'], $today, $expense['ExpenseItem'], $expense['ExpenseCost'], $expense['CategoryID']);
        $stmt->execute();
        
        // Update the NextDueDate for the auto expense
        $nextDueDate = calculateNextDueDate($expense['NextDueDate'], $expense['Frequency']);
        $stmt = $conn->prepare("UPDATE auto_expenses SET NextDueDate = ? WHERE ID = ?");
        $stmt->bind_param("si", $nextDueDate, $expense['ID']);
        $stmt->execute();
    }
}

function calculateNextDueDate($currentDueDate, $frequency) {
    $date = new DateTime($currentDueDate);
    
    if ($frequency === 'monthly') {
        $date->modify('+1 month');
    } elseif ($frequency === 'yearly') {
        $date->modify('+1 year');
    }
    
    return $date->format('Y-m-d');
}
?>