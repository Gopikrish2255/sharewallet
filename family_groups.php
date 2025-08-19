<?php
session_start();
require_once 'includes/database.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = (int)$_SESSION['user_id'];
$selectedGroupId = isset($_GET['group_id']) ? (int)$_GET['group_id'] : null;

// Get all groups current user is part of
$groups = [];
$stmt = $conn->prepare("
    SELECT g.id, g.group_name, g.admin_user_id 
    FROM family_groups g
    INNER JOIN family_members m ON g.id = m.group_id
    WHERE m.user_id = ?
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$groups = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Handle create group
if (isset($_POST['create_group'])) {
    $groupName = trim($_POST['group_name']);
    if ($groupName === '') {
        $groupName = "Family Group of User $userId";
    }
    $stmt = $conn->prepare("INSERT INTO family_groups (group_name, admin_user_id, created_at) VALUES (?, ?, NOW())");
    $stmt->bind_param("si", $groupName, $userId);
    if ($stmt->execute()) {
        $newGroupId = $stmt->insert_id;
        $stmt->close();
        $insert = $conn->prepare("INSERT INTO family_members (group_id, user_id) VALUES (?, ?)");
        $insert->bind_param("ii", $newGroupId, $userId);
        $insert->execute();
        $insert->close();
        $_SESSION['flash_message'] = "Group created successfully.";
        header("Location: family_groups.php?group_id=$newGroupId");
        exit;
    } else {
        die("Group insert failed: " . $stmt->error);
    }
}

// Determine if current user is admin of selected group
$isAdmin = false;
if ($selectedGroupId) {
    $stmt = $conn->prepare("SELECT admin_user_id FROM family_groups WHERE id = ?");
    $stmt->bind_param("i", $selectedGroupId);
    $stmt->execute();
    $stmt->bind_result($adminId);
    if ($stmt->fetch() && $adminId == $userId) {
        $isAdmin = true;
    }
    $stmt->close();
}

// Handle delete group (admin only)
if (isset($_POST['delete_group']) && $isAdmin && $selectedGroupId) {
    $stmt = $conn->prepare("DELETE FROM family_members WHERE group_id = ?");
    $stmt->bind_param("i", $selectedGroupId);
    $stmt->execute();
    $stmt->close();
    $stmt = $conn->prepare("DELETE FROM family_groups WHERE id = ?");
    $stmt->bind_param("i", $selectedGroupId);
    $stmt->execute();
    $stmt->close();
    $_SESSION['flash_message'] = "Group deleted successfully.";
    header("Location: family_groups.php");
    exit;
}

// Remove member
if (isset($_POST['remove_member']) && $selectedGroupId) {
    $memberId = (int)$_POST['remove_member'];
    $stmt = $conn->prepare("DELETE FROM family_members WHERE user_id = ? AND group_id = ?");
    $stmt->bind_param("ii", $memberId, $selectedGroupId);
    $stmt->execute();
    $stmt->close();
    $_SESSION['flash_message'] = "Member removed.";
    header("Location: family_groups.php?group_id=$selectedGroupId");
    exit;
}

// Add member
if (isset($_POST['add_member']) && $selectedGroupId) {
    $memberId = (int)$_POST['add_member'];
    $stmt = $conn->prepare("INSERT INTO family_members (group_id, user_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $selectedGroupId, $memberId);
    $stmt->execute();
    $stmt->close();
    $_SESSION['flash_message'] = "Member added.";
    header("Location: family_groups.php?group_id=$selectedGroupId");
    exit;
}

// Search users to add
$searchResults = [];
if (isset($_GET['search']) && $selectedGroupId) {
    $searchTerm = "%" . trim($_GET['search']) . "%";
    $stmt = $conn->prepare("
        SELECT * FROM tbluser 
        WHERE ID != ? 
        AND ID NOT IN (SELECT user_id FROM family_members WHERE group_id = ?)
        AND (FirstName LIKE ? OR LastName LIKE ? OR Email LIKE ?)
    ");
    $stmt->bind_param("iisss", $userId, $selectedGroupId, $searchTerm, $searchTerm, $searchTerm);
    $stmt->execute();
    $searchResults = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Get selected group details
$groupName = null;
$members = [];
if ($selectedGroupId) {
    $stmt = $conn->prepare("SELECT group_name FROM family_groups WHERE id = ?");
    $stmt->bind_param("i", $selectedGroupId);
    $stmt->execute();
    $stmt->bind_result($groupName);
    $stmt->fetch();
    $stmt->close();

    $stmt = $conn->prepare("
        SELECT u.* FROM tbluser u
        INNER JOIN family_members f ON u.ID = f.user_id
        WHERE f.group_id = ?
    ");
    $stmt->bind_param("i", $selectedGroupId);
    $stmt->execute();
    $members = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Family Groups</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
<style>
    body { font-family: 'Poppins', sans-serif; background: #f5f5f5; margin: 0; padding: 0; }
    .container { max-width: 1000px; margin: 2rem auto; padding: 2rem; background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px); border-radius: 20px; box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37); }
    h1, h2, h3 { color: #6c5ce7; text-align: center; }
    .flash-message { background: #6c5ce7; color: white; padding: 1rem; border-radius: 10px; margin-bottom: 1rem; text-align: center; }
    form { display: flex; justify-content: center; margin-bottom: 1.5rem; }
    input[type=text] { padding: 0.5rem 1rem; border-radius: 25px 0 0 25px; border: none; flex: 1; }
    button { padding: 0.5rem 1rem; border: none; border-radius: 0 25px 25px 0; background: #6c5ce7; color: white; cursor: pointer; }
    .card-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1.5rem; }
    .user-card { background: rgba(255, 255, 255, 0.7); border-radius: 15px; padding: 1.5rem; text-align: center; box-shadow: 0 8px 32px rgba(31, 38, 135, 0.2); }
    .user-card button { background: #e67e22; border: none; padding: 0.4rem 1rem; border-radius: 8px; color: white; cursor: pointer; }
    .back-btn { display: inline-block; padding: 0.5rem 1rem; background: #3498db; color: white; border-radius: 8px; text-decoration: none; margin-bottom: 1rem; }
</style>
</head>
<body>
<div class="container">
    <a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    <h1>Family Group Management</h1>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="flash-message"><?= htmlspecialchars($_SESSION['flash_message']); unset($_SESSION['flash_message']); ?></div>
    <?php endif; ?>

    <h2>Your Groups</h2>
    <ul>
        <?php foreach ($groups as $g): ?>
            <li>
                <?= htmlspecialchars($g['group_name']); ?> 
                <a href="?group_id=<?= $g['id']; ?>">Manage</a>
            </li>
        <?php endforeach; ?>
    </ul>

    <h2>Create a New Group</h2>
    <form method="POST">
        <input type="text" name="group_name" placeholder="Enter Group Name">
        <button type="submit" name="create_group">Create Group</button>
    </form>

    <?php if ($selectedGroupId): ?>
        <h2>Group: <?= htmlspecialchars($groupName); ?></h2>

        <?php if ($isAdmin): ?>
            <form method="POST" onsubmit="return confirm('Delete this group? This cannot be undone.');" style="justify-content:center;">
                <button type="submit" name="delete_group" style="background:#e74c3c; border-radius:8px;">Delete Group</button>
            </form>
        <?php endif; ?>

        <h3>Members</h3>
        <div class="card-list">
            <?php foreach ($members as $m): ?>
                <div class="user-card">
                    <h4><?= htmlspecialchars($m['FirstName'] . ' ' . $m['LastName']); ?></h4>
                    <?php if ($m['ID'] != $userId): ?>
                        <form method="POST">
                            <input type="hidden" name="remove_member" value="<?= $m['ID']; ?>">
                            <button type="submit">Remove</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <h3>Add Members</h3>
        <form method="GET">
            <input type="hidden" name="group_id" value="<?= $selectedGroupId; ?>">
            <input type="text" name="search" placeholder="Search users">
            <button type="submit">Search</button>
        </form>
        <?php if (!empty($searchResults)): ?>
            <div class="card-list">
                <?php foreach ($searchResults as $user): ?>
                    <div class="user-card">
                        <h4><?= htmlspecialchars($user['FirstName'] . ' ' . $user['LastName']); ?></h4>
                        <form method="POST">
                            <input type="hidden" name="add_member" value="<?= $user['ID']; ?>">
                            <button type="submit" style="background:#2ecc71;">Add</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
</body>
</html>
