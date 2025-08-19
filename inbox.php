<?php
session_start();
define('INCLUDED', true);
include 'includes/database.php';
include 'includes/functions.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error_message = "";
$success_message = "";

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch link requests
$linkRequests = getLinkRequests($user_id);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed");
    }

    if (isset($_POST['action']) && isset($_POST['request_id'])) {
        $requestId = filter_input(INPUT_POST, 'request_id', FILTER_VALIDATE_INT);
        $action = $_POST['action'];

        if ($requestId === false) {
            $error_message = "Invalid request ID.";
        } else {
            if ($action === 'accept') {
                if (acceptLinkRequest($requestId, $user_id)) {
                    $success_message = "Link request accepted successfully.";
                } else {
                    $error_message = "Failed to accept link request.";
                }
            } elseif ($action === 'decline') {
                if (declineLinkRequest($requestId, $user_id)) {
                    $success_message = "Link request declined successfully.";
                } else {
                    $error_message = "Failed to decline link request.";
                }
            } else {
                $error_message = "Invalid action.";
            }
        }
    }

    // Refresh link requests after action
    $linkRequests = getLinkRequests($user_id);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inbox - Link Requests - Elegant Expense Tracker</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
       @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap');

:root {
    --primary-color: #6c5ce7;
    --secondary-color: #a29bfe;
    --text-color: #2d3436;
    --background-color: #f0f3f7;
    --glass-background: rgba(255, 255, 255, 0.25);
    --glass-border: 1px solid rgba(255, 255, 255, 0.18);
    --glass-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
}

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    background: var(--background-color);
    font-family: 'Poppins', sans-serif;
    display: flex;
    min-height: 100vh;
    color: var(--text-color);
}

.main-content {
    flex-grow: 1;
    padding: 2rem;
    margin-left: 16rem;
    width: calc(100% - 16rem);
    overflow-y: auto;
    display: flex;
    justify-content: center;
    align-items: flex-start;
}

.container {
    background: var(--glass-background);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    border: var(--glass-border);
    box-shadow: var(--glass-shadow);
    padding: 2rem;
    width: 100%;
    max-width: 800px;
    opacity: 0;
    transform: translateY(20px);
    animation: fadeInUp 0.6s ease-out forwards;
}

h1 {
    font-size: 2rem;
    margin-bottom: 1.5rem;
    color: var(--primary-color);
    text-align: center;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.message {
    padding: 1rem;
    border-radius: 10px;
    margin-bottom: 1rem;
    font-weight: 600;
    backdrop-filter: blur(5px);
}

.error-message {
    background-color: rgba(255, 235, 238, 0.7);
    color: #c62828;
    border: 1px solid rgba(239, 154, 154, 0.5);
}

.success-message {
    background-color: rgba(232, 245, 233, 0.7);
    color: #2e7d32;
    border: 1px solid rgba(165, 214, 167, 0.5);
}

table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 10px;
    margin-top: 1rem;
}

th, td {
    padding: 0.75rem;
    text-align: left;
}

th {
    background-color: rgba(108, 92, 231, 0.2);
    color: var(--primary-color);
    font-weight: 600;
    border-radius: 5px;
}

td {
    background-color: rgba(255, 255, 255, 0.1);
    border-radius: 5px;
}

.btn {
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 0.9rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
    position: relative;
    overflow: hidden;
}

.btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(
        120deg,
        transparent,
        rgba(255, 255, 255, 0.3),
        transparent
    );
    transition: all 0.4s;
}

.btn:hover::before {
    left: 100%;
}

.btn-accept {
    background-color: rgba(46, 204, 113, 0.8);
    color: white;
    box-shadow: 0 4px 6px rgba(46, 204, 113, 0.2);
}

.btn-decline {
    background-color: rgba(231, 76, 60, 0.8);
    color: white;
    box-shadow: 0 4px 6px rgba(231, 76, 60, 0.2);
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 8px rgba(0, 0, 0, 0.15);
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

@media (max-width: 768px) {
    body {
        flex-direction: column;
    }
    .main-content {
        margin-left: 0;
        width: 100%;
        padding: 1rem;
    }
    .container {
        padding: 1.5rem;
    }
}
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="main-content">
        <div class="container">
            <h1>Link Requests Inbox</h1>
            <?php if ($error_message): ?>
                <div class="message error-message"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            <?php if ($success_message): ?>
                <div class="message success-message"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>
            <?php if (empty($linkRequests)): ?>
                <p>You have no pending link requests.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>From</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($linkRequests as $request): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($request['FirstName'] . ' ' . $request['LastName']); ?></td>
                                <td><?php echo htmlspecialchars($request['request_date']); ?></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                        <button type="submit" name="action" value="accept" class="btn btn-accept">Accept</button>
                                        <button type="submit" name="action" value="decline" class="btn btn-decline">Decline</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <script>
        feather.replace();
    </script>
</body>
</html>