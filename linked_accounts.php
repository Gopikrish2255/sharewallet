<?php
session_start();

// required DB / helper includes
require_once 'includes/database.php';
require_once 'includes/functions.php';

// If user not logged in, redirect (uses your existing redirect() if present)
if (!isset($_SESSION['user_id'])) {
    // fallback redirect if function missing
    if (function_exists('redirect')) {
        redirect('login.php', 'Please log in to access this page.');
    } else {
        header('Location: login.php');
        exit;
    }
}

$userId = (int)$_SESSION['user_id'];

// safe helper fallbacks (only defined if not present in includes/functions.php)
if (!function_exists('sanitize')) {
    function sanitize($s) {
        return htmlspecialchars(trim($s), ENT_QUOTES, 'UTF-8');
    }
}
if (!function_exists('safeOutput')) {
    function safeOutput($s) {
        return htmlspecialchars((string)($s ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

// initialize variables so HTML won't throw "undefined variable"
$searchTerm = '';
$searchResults = [];
$linkedUsers = [];

// Get the user's group_id from family_members (if any)
$groupId = null;
if ($conn) {
    $stmt = $conn->prepare("SELECT group_id FROM family_members WHERE user_id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $groupId = $row['group_id'] ?? null;
        $stmt->close();
    }
}

// Handle follow/unfollow actions (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // follow (add user to same group)
    if (isset($_POST['follow']) && $groupId) {
        $followId = (int)$_POST['follow'];

        // avoid duplicates
        $check = $conn->prepare("SELECT 1 FROM family_members WHERE user_id = ? AND group_id = ?");
        $check->bind_param("ii", $followId, $groupId);
        $check->execute();
        $exists = $check->get_result()->num_rows > 0;
        $check->close();

        if (!$exists) {
            $insert = $conn->prepare("INSERT INTO family_members (group_id, user_id) VALUES (?, ?)");
            $insert->bind_param("ii", $groupId, $followId);
            if ($insert->execute()) {
                $_SESSION['flash_message'] = "Link request sent successfully.";
            } else {
                $_SESSION['flash_message'] = "Failed to send link request.";
            }
            $insert->close();
        } else {
            $_SESSION['flash_message'] = "User is already linked.";
        }
    }

    // unfollow (remove user from group)
    if (isset($_POST['unfollow']) && $groupId) {
        $unfollowId = (int)$_POST['unfollow'];
        $del = $conn->prepare("DELETE FROM family_members WHERE user_id = ? AND group_id = ?");
        $del->bind_param("ii", $unfollowId, $groupId);
        if ($del->execute()) {
            $_SESSION['flash_message'] = "User unlinked successfully.";
        } else {
            $_SESSION['flash_message'] = "Failed to unlink user.";
        }
        $del->close();
    }

    // reload page to show flash and avoid resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle search (GET)
if (isset($_GET['search'])) {
    $searchTerm = sanitize($_GET['search']);
    if ($searchTerm !== '') {
        $param = '%' . $searchTerm . '%';

        if ($groupId) {
            // exclude self and already-linked users in same group
            $sql = "SELECT * FROM tbluser 
                    WHERE ID != ? 
                      AND ID NOT IN (SELECT user_id FROM family_members WHERE group_id = ?)
                      AND (FirstName LIKE ? OR LastName LIKE ? OR Email LIKE ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iisss", $userId, $groupId, $param, $param, $param);
        } else {
            // user has no group yet: search excluding only self
            $sql = "SELECT * FROM tbluser WHERE ID != ? AND (FirstName LIKE ? OR LastName LIKE ? OR Email LIKE ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isss", $userId, $param, $param, $param);
        }

        if ($stmt) {
            $stmt->execute();
            $searchResults = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }
    }
}

// ---- FIXED: Get linked users (find everyone who shares your group_id) ----
if ($conn) {
    $stmt = $conn->prepare(
        "SELECT DISTINCT u.*
         FROM tbluser u
         INNER JOIN family_members f ON u.ID = f.user_id
         WHERE f.group_id = (
             SELECT group_id FROM family_members WHERE user_id = ? LIMIT 1
         )
         AND u.ID != ?"
    );
    if ($stmt) {
        $stmt->bind_param("ii", $userId, $userId);
        $stmt->execute();
        $linkedUsers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } else {
        $linkedUsers = [];
    }
} else {
    $linkedUsers = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Link Users</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.9.1/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.9.1/ScrollTrigger.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap');

        :root {
            --primary-color: #6c5ce7;
            --secondary-color: #a29bfe;
            --text-color: #2d3436;
            --background-color: #f9f9f9;
            --card-background: rgba(255, 255, 255, 0.7);
            --card-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            --card-border: 1px solid rgba(255, 255, 255, 0.18);
            --input-background: rgba(255, 255, 255, 0.9);
            --button-background: var(--primary-color);
            --button-text: white;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--background-color);
            color: var(--text-color);
            transition: all 0.3s ease;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            border: var(--card-border);
        }

        h1, h2 {
            color: var(--primary-color);
            text-align: center;
            margin-bottom: 2rem;
        }

        .search-form {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
        }

        .search-form input {
            padding: 0.5rem 1rem;
            font-size: 1rem;
            border: none;
            border-radius: 25px 0 0 25px;
            background: var(--input-background);
            color: var(--text-color);
            box-shadow: inset 2px 2px 5px rgba(0, 0, 0, 0.1),
                        inset -2px -2px 5px rgba(255, 255, 255, 0.1);
        }

        .search-form button {
            padding: 0.5rem 1rem;
            font-size: 1rem;
            border: none;
            border-radius: 0 25px 25px 0;
            background: var(--button-background);
            color: var(--button-text);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .search-form button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .search-results, .linked-users {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .user-card {
            background: var(--card-background);
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            border: var(--card-border);
            transition: all 0.3s ease;
            transform: translateY(0);
        }

        .user-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 36px 0 rgba(31, 38, 135, 0.37);
        }

        .user-card h3 {
            margin-top: 0;
            color: var(--primary-color);
        }

        .user-card p {
            margin-bottom: 1rem;
        }

        .user-card form {
            display: flex;
            justify-content: center;
        }

        .user-card button {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
            border: none;
            border-radius: 25px;
            background: var(--button-background);
            color: var(--button-text);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .user-card button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .flash-message {
            background: var(--primary-color);
            color: white;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            text-align: center;
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .search-form {
                flex-direction: column;
                align-items: center;
            }

            .search-form input,
            .search-form button {
                width: 100%;
                border-radius: 25px;
                margin-bottom: 0.5rem;
            }

            .user-card {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Link Users</h1>

        <?php
        if (isset($_SESSION['flash_message'])) {
            echo "<p class='flash-message'>" . safeOutput($_SESSION['flash_message']) . "</p>";
            unset($_SESSION['flash_message']);
        }
        ?>

        <form action="" method="GET" class="search-form">
            <input type="text" name="search" placeholder="Search users" value="<?php echo safeOutput($searchTerm); ?>">
            <button type="submit">Search</button>
        </form>

        <?php if (!empty($searchResults)): ?>
            <h2>Search Results</h2>
            <div class="search-results">
                <?php foreach ($searchResults as $user): ?>
                    <div class="user-card">
                        <h3><?php echo safeOutput($user['FirstName'] . ' ' . $user['LastName']); ?></h3>
                        <form action="" method="POST">
                            <input type="hidden" name="follow" value="<?php echo (int)$user['ID']; ?>">
                            <button type="submit">Follow</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php elseif ($searchTerm !== ''): ?>
            <p>No users found matching your search.</p>
        <?php endif; ?>

        <h2>Linked Users</h2>
        <div class="linked-users">
            <?php foreach ($linkedUsers as $user): ?>
                <div class="user-card">
                    <h3><?php echo safeOutput($user['FirstName'] . ' ' . $user['LastName']); ?></h3>
                    <?php
                    // Use existing helper if available; if not, fallback to basic sum query
                    if (function_exists('getTotalExpenses')) {
                        $totalExpense = getTotalExpenses($user['ID']);
                    } else {
                        $totalExpense = 0;
                        if ($conn) {
                            $q = $conn->prepare("SELECT IFNULL(SUM(ExpenseCost),0) as total FROM tblexpense WHERE UserId = ?");
                            $q->bind_param("i", $user['ID']);
                            $q->execute();
                            $r = $q->get_result()->fetch_assoc();
                            $totalExpense = $r['total'] ?? 0;
                            $q->close();
                        }
                    }
                    echo "<p>Total Expense: $" . number_format((float)$totalExpense, 2) . "</p>";
                    ?>
                    <form action="" method="POST">
                        <input type="hidden" name="unfollow" value="<?php echo (int)$user['ID']; ?>">
                        <button type="submit">Unfollow</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        // GSAP Animations
        gsap.registerPlugin(ScrollTrigger);

        gsap.from('.user-card', {
            opacity: 0,
            y: 50,
            stagger: 0.1,
            duration: 0.8,
            ease: 'power3.out',
            scrollTrigger: {
                trigger: '.search-results, .linked-users',
                start: 'top 80%',
            }
        });

        document.querySelectorAll('.user-card').forEach(card => {
            card.addEventListener('mouseenter', () => {
                gsap.to(card, {
                    scale: 1.05,
                    duration: 0.3,
                    ease: 'power2.out'
                });
            });

            card.addEventListener('mouseleave', () => {
                gsap.to(card, {
                    scale: 1,
                    duration: 0.3,
                    ease: 'power2.out'
                });
            });

            card.addEventListener('click', () => {
                gsap.to(card, {
                    scale: 0.95,
                    duration: 0.1,
                    ease: 'power2.in',
                    yoyo: true,
                    repeat: 1
                });
            });
        });

        // Dark Mode Toggle
        const toggle = document.querySelector('.toggle');
        const body = document.body;

        toggle && toggle.addEventListener('click', () => {
            body.classList.toggle('dark-mode');
            toggle.classList.toggle('dark');
            localStorage.setItem('darkMode', body.classList.contains('dark-mode'));
        });

        // Check for saved dark mode preference
        if (localStorage.getItem('darkMode') === 'true') {
            body.classList.add('dark-mode');
            toggle && toggle.classList.add('dark');
        }
    </script>
</body>
</html>
