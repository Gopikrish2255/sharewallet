<?php
// Start the session and include necessary files at the very top
session_start();
define('INCLUDED', true);

// Database connection
require_once 'includes/database.php';
require_once 'includes/functions.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    redirect('login.php', 'Please log in to access this page.');
}

$userId = $_SESSION['user_id'];

// Initialize variables
$searchTerm = '';
$searchResults = [];
$linkedUsers = [];

// Get the user's group ID
$stmt = $conn->prepare("SELECT group_id FROM family_members WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$groupId = $row['group_id'] ?? null;
$stmt->close();

// Handle follow/unfollow actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['follow']) && $groupId) {
        $followId = (int)$_POST['follow'];

        // Avoid adding if already in group
        $check = $conn->prepare("SELECT * FROM family_members WHERE user_id = ? AND group_id = ?");
        $check->bind_param("ii", $followId, $groupId);
        $check->execute();
        $exists = $check->get_result()->num_rows > 0;
        $check->close();

        if (!$exists) {
            $stmt = $conn->prepare("INSERT INTO family_members (user_id, group_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $followId, $groupId);
            if ($stmt->execute()) {
                $_SESSION['flash_message'] = "Link request sent successfully.";
            } else {
                $_SESSION['flash_message'] = "Failed to send link request.";
            }
            $stmt->close();
        } else {
            $_SESSION['flash_message'] = "User is already linked.";
        }
    } elseif (isset($_POST['unfollow']) && $groupId) {
        $unfollowId = (int)$_POST['unfollow'];
        $stmt = $conn->prepare("DELETE FROM family_members WHERE user_id = ? AND group_id = ?");
        $stmt->bind_param("ii", $unfollowId, $groupId);
        if ($stmt->execute()) {
            $_SESSION['flash_message'] = "User unlinked successfully.";
        } else {
            $_SESSION['flash_message'] = "Failed to unlink user.";
        }
        $stmt->close();
    }
    redirect('linked_users.php');
}

// --- UPDATED: Handle search ---
if (isset($_GET['search']) && $groupId) {
    $searchTerm = sanitize($_GET['search']);
    if (!empty($searchTerm)) {
        $param = '%' . $searchTerm . '%';
        $stmt = $conn->prepare(
            "SELECT u.* 
             FROM tbluser u
             WHERE u.ID != ? 
               AND u.ID NOT IN (
                   SELECT user_id 
                   FROM family_members 
                   WHERE group_id = ?
               )
               AND (u.FirstName LIKE ? OR u.LastName LIKE ? OR u.Email LIKE ?)"
        );
        $stmt->bind_param("iisss", $userId, $groupId, $param, $param, $param);
        $stmt->execute();
        $searchResults = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}

// --- UPDATED: Get linked users ---
$stmt = $conn->prepare(
    "SELECT DISTINCT u.* 
     FROM tbluser u
     INNER JOIN family_members f 
       ON u.ID = f.user_id
     WHERE f.group_id = (
         SELECT group_id FROM family_members WHERE user_id = ?
     )
     AND u.ID != ?"
);
$stmt->bind_param("ii", $userId, $userId);

    $stmt->bind_param("ii", $groupId, $userId);
    $stmt->execute();
    $linkedUsers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
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
  --primary-color: #171338ff;
  --secondary-color: #1a0dc5ff;
  --text-color: #2d3436;
  --background-color: #f0f3f5;
  --glass-bg: rgba(255, 255, 255, 0.7);
  --glass-border: 1px solid rgba(255, 255, 255, 0.5);
  --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
  --button-background: var(--primary-color);
  --button-text: white;
  --dropdown-arrow: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%236c5ce7' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
}

body {
  font-family: 'Poppins', sans-serif;
  background: linear-gradient(135deg, #a29bfe, #6c5ce7);
  color: var(#292626);
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
  background: var(--glass-bg);
  backdrop-filter: blur(10px);
  border-radius: 20px;
  box-shadow: var(--card-shadow);
  border: var(--glass-border);
}

h1,
h2 {
  color: var(--primary-color);
  text-align: center;
  margin-bottom: 2rem;
  text-shadow: 1px 1px 2px rgba(255, 255, 255, 0.5);
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
  background: rgba(255, 255, 255, 0.8);
  color: var(#292626);
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

.search-results,
.linked-users {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1.5rem;
}

.user-card {
  background: var(--glass-bg);
  backdrop-filter: blur(10px);
  border-radius: 15px;
  padding: 1.5rem;
  box-shadow: var(--card-shadow);
  border: var(--glass-border);
  transition: all 0.3s ease;
  transform: translateY(0);
  position: relative;
  overflow: hidden;
}

.user-card:hover {
  transform: translateY(-10px);
  box-shadow: 0 12px 36px 0 rgba(31, 38, 135, 0.37);
}

.user-card::before {
  content: "";
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.3), rgba(255, 255, 255, 0));
  z-index: -1;
  animation: shine 2s ease-in-out infinite;
}

@keyframes shine {
  0% {
    transform: translateX(-100%);
  }
  100% {
    transform: translateX(100%);
  }
}

.user-card h3 {
  margin-top: 0;
  color: var(--primary-color);
  text-shadow: 1px 1px 2px rgba(255, 255, 255, 0.5);
}

.user-card p {
  margin-bottom: 1rem;
  text-shadow: 1px 1px 2px rgba(255, 255, 255, 0.5);
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
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Category Dropdown */
select {
  -webkit-appearance: none;
  -moz-appearance: none;
  appearance: none;
  width: 100%;
  padding: 0.75rem 1rem 0.75rem 0.75rem;
  background: rgba(255, 255, 255, 0.8);
  border: 1px solid rgba(108, 92, 231, 0.3);
  border-radius: 10px;
  color: var(--text-color);
  font-size: 1rem;
  cursor: pointer;
  box-shadow: inset 2px 2px 5px rgba(0, 0, 0, 0.1),
    inset -2px -2px 5px rgba(255, 255, 255, 0.1);
  background-image: var(--dropdown-arrow);
  background-repeat: no-repeat;
  background-position: right 1rem center;
  background-size: 1em;
  transition: all 0.3s ease;
}

select:focus {
  border-color: var(--primary-color);
  outline: none;
  box-shadow: 0 0 0 3px rgba(108, 92, 231, 0.2);
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

  .search-results,
  .linked-users {
    grid-template-columns: 1fr;
  }
}
    </style>
</head>
<body>
<?php include 'includes/navbar.php'; ?>
    <div class="mode-toggle">
        <div class="toggle">
            <div class="switch"></div>
        </div>
    </div>

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
                            <input type="hidden" name="follow" value="<?php echo $user['ID']; ?>">
                            <button type="submit">Follow</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php elseif (!empty($searchTerm)): ?>
            <p>No users found matching your search.</p>
        <?php endif; ?>

        <h2>Linked Users</h2>
        <?php if (!empty($linkedUsers)): ?>
            <div class="linked-users">
                <?php foreach ($linkedUsers as $user): ?>
                    <div class="user-card">
                        <h3><?php echo safeOutput($user['FirstName'] . ' ' . $user['LastName']); ?></h3>
                        <?php
                        $totalExpense = getTotalExpenses($user['ID']);
                        echo "<p>Total Expense: $" . number_format($totalExpense, 2) . "</p>";
                        ?>
                        <form action="" method="POST">
                            <input type="hidden" name="unfollow" value="<?php echo $user['ID']; ?>">
                            <button type="submit">Unfollow</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>You haven't linked with any users yet.</p>
        <?php endif; ?>
    </div>

    <script>
         // GSAP Animations
        gsap.registerPlugin(ScrollTrigger);

        // User cards animation
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

        // Micro-interactions for user cards
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

        toggle.addEventListener('click', () => {
            body.classList.toggle('dark-mode');
            toggle.classList.toggle('dark');
            localStorage.setItem('darkMode', body.classList.contains('dark-mode'));
        });

        // Check for saved dark mode preference
        if (localStorage.getItem('darkMode') === 'true') {
            body.classList.add('dark-mode');
            toggle.classList.add('dark');
        }
    </script>
</body>
</html>