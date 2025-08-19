<?php
require_once 'includes/database.php';
require_once 'includes/functions.php';

// Ensure only admin users can access this page
if (!isAdminLoggedIn()) {
    header("Location: login.php");
    exit();
}

// Fetch users from the database
$users = getAllUsers();

if (isset($_POST['query'])) {
    $query = $_POST['query'];
    $users = searchUsers($searchTerm, $currentUserId);
}

if (isset($_POST['userId'])) {
    $userId = $_POST['userId'];
    $user = getUserDetails($userId);
    $monthlyExpenses = getUserMonthlyExpenses($userId);
    $yearlyExpenses = getUserYearlyExpenses($userId);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f4f4f4;
        }

        .user-card {
            transition: all 0.3s ease;
        }

        .user-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .card-img-top {
            height: 200px;
            object-fit: cover;
        }

        /* Dark mode styles */
        @media (prefers-color-scheme: dark) {
            body {
                background-color: #333;
                color: #fff;
            }

            .card {
                background-color: #444;
                color: #fff;
            }

            .modal-content {
                background-color: #444;
                color: #fff;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <h1 class="text-center my-4">Admin Panel</h1>
        <div class="row">
            <div class="col-md-6 offset-md-3">
                <div class="input-group mb-3">
                    <input type="text" class="form-control" placeholder="Search users" id="searchInput">
                    <div class="input-group-append">
                        <button class="btn btn-primary" type="button" id="searchButton">Search</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="row" id="userList">
            <?php foreach ($users as $user): ?>
                <div class="col-md-4 mb-4">
                    <div class="card user-card">
                        <img src="<?php echo getUserPhoto($user['id']); ?>" class="card-img-top" alt="User Photo">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo $user['name']; ?></h5>
                            <p class="card-text">Email: <?php echo $user['email']; ?></p>
                            <p class="card-text">Total Expense: $<?php echo getUserTotalExpense($user['id']); ?></p>
                            <button class="btn btn-primary btn-sm view-details" data-userid="<?php echo $user['id']; ?>">View Details</button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Modal for user details -->
    <div class="modal fade" id="userDetailsModal" tabindex="-1" role="dialog" aria-labelledby="userDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="userDetailsModalLabel">User Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="userDetailsContent">
                    <?php if (isset($user)): ?>
                        <h4><?php echo $user['name']; ?></h4>
                        <p>Email: <?php echo $user['email']; ?></p>
                        <p>Mobile: <?php echo $user['mobile']; ?></p>

                        <h5>Monthly Expenses</h5>
                        <ul>
                            <?php foreach ($monthlyExpenses as $expense): ?>
                                <li><?php echo $expense['month']; ?>: $<?php echo $expense['total']; ?></li>
                            <?php endforeach; ?>
                        </ul>

                        <h5>Yearly Expenses</h5>
                        <ul>
                            <?php foreach ($yearlyExpenses as $expense): ?>
                                <li><?php echo $expense['year']; ?>: $<?php echo $expense['total']; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        $(document).ready(function() {
            // Search functionality
            $('#searchButton').click(function() {
                searchUsers();
            });

            $('#searchInput').keypress(function(e) {
                if (e.which == 13) {
                    searchUsers();
                }
            });

            // View user details
            $('.view-details').click(function() {
                var userId = $(this).data('userid');
                loadUserDetails(userId);
            });

            function searchUsers() {
                var query = $('#searchInput').val();
                $.ajax({
                    url: '',
                    method: 'POST',
                    data: { query: query },
                    success: function(response) {
                        $('#userList').html(response);
                    }
                });
            }

            function loadUserDetails(userId) {
                $.ajax({
                    url: '',
                    method: 'POST',
                    data: { userId: userId },
                    success: function(response) {
                        $('#userDetailsContent').html(response);
                        $('#userDetailsModal').modal('show');
                    }
                });
            }
        });
    </script>
</body>
</html>

<?php
function getAllUsers() {
    global $conn;
    $sql = "SELECT * FROM tbluser";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getUserPhoto($userId) {
    // Implement this function to return the user's photo URL
    // You may need to adjust this based on how you store user photos
    return "path/to/user/photos/{$userId}.jpg";
}

function getUserTotalExpense($userId) {
    global $conn;
    $sql = "SELECT SUM(total_expense) as total FROM tblsummary_yearly WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['total'] ?? 0;
}

searchUsers($searchTerm, $currentUserId);

getUserDetails($userId);
function getUserMonthlyExpenses($userId) {
    global $conn;
    $sql = "SELECT month, total_expense as total FROM tblsummary_monthly WHERE user_id = ? ORDER BY month DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getUserYearlyExpenses($userId) {
    global $conn;
    $sql = "SELECT year, total_expense as total FROM tblsummary_yearly WHERE user_id = ? ORDER BY year DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function isAdminLoggedIn() {
    // Implement this function to check if the current user is an admin
    // You may need to adjust this based on your authentication system
    return true;
}
?>