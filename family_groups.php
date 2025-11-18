<?php
session_start();
define('ACCESS_ALLOWED', true);

require_once 'includes/database.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = (int)$_SESSION['user_id'];
$selectedGroupId = isset($_GET['group_id']) ? (int)$_GET['group_id'] : null;

/* ============================================================
   FETCH USER GROUPS  (Fixed: DISTINCT prevents duplicates)
   ============================================================ */
$stmt = $conn->prepare("
    SELECT DISTINCT g.id, g.group_name, g.admin_user_id
    FROM family_groups g
    INNER JOIN family_members m ON g.id = m.group_id
    WHERE m.user_id = ?
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$groups = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();


/* ============================================================
   CREATE NEW GROUP
   ============================================================ */
// Create group
if (isset($_POST['create_group'])) {
    $groupName = trim($_POST['group_name']);
    if ($groupName === '') {
        $groupName = "Family Group of User $userId";
    }

    // Insert group
    $stmt = $conn->prepare("INSERT INTO family_groups (group_name, admin_user_id, created_at) VALUES (?, ?, NOW())");
    if (!$stmt) die("Group prepare failed: " . $conn->error);

    $stmt->bind_param("si", $groupName, $userId);
    if (!$stmt->execute()) die("Group insert failed: " . $stmt->error);

    $newGroupId = $stmt->insert_id;
    $stmt->close();

    // Insert member
    $insert = $conn->prepare("INSERT INTO family_members (group_id, user_id) VALUES (?, ?)");
    if (!$insert) die("Insert member prepare failed: " . $conn->error);

    $insert->bind_param("ii", $newGroupId, $userId);
    if (!$insert->execute()) die("Insert member failed: " . $insert->error);
    $insert->close();

    $_SESSION['flash_message'] = "Group created successfully.";
    header("Location: family_groups.php?group_id=$newGroupId");
    exit;
}




/* ============================================================
   CHECK ADMIN
   ============================================================ */
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


/* ============================================================
   DELETE GROUP (Fixed)
   ============================================================ */
if (isset($_POST['delete_group']) && $isAdmin && $selectedGroupId) {

    // DELETE MEMBERS
    $stmt = $conn->prepare("DELETE FROM family_members WHERE group_id = ?");
    $stmt->bind_param("i", $selectedGroupId);
    $stmt->execute();
    $stmt->close();

    // DELETE GROUP
    $stmt = $conn->prepare("DELETE FROM family_groups WHERE id = ?");
    $stmt->bind_param("i", $selectedGroupId);
    $stmt->execute();
    $stmt->close();

    $_SESSION['flash_message'] = "Group deleted successfully.";
    header("Location: family_groups.php");
    exit;
}


/* ============================================================
   REMOVE MEMBER
   ============================================================ */
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


/* ============================================================
   ADD MEMBER (Fixed: no duplicates)
   ============================================================ */
if (isset($_POST['add_member']) && $selectedGroupId) {
    $memberId = (int)$_POST['add_member'];

    // CHECK IF ALREADY EXISTS
    $stmt = $conn->prepare("
        SELECT 1 FROM family_members 
        WHERE group_id = ? AND user_id = ?
    ");
    $stmt->bind_param("ii", $selectedGroupId, $memberId);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    if ($exists) {
        $_SESSION['flash_message'] = "User is already in this group.";
        header("Location: family_groups.php?group_id=$selectedGroupId");
        exit;
    }

    // INSERT NEW MEMBER
    $stmt = $conn->prepare("INSERT INTO family_members (group_id, user_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $selectedGroupId, $memberId);
    $stmt->execute();
    $stmt->close();

    $_SESSION['flash_message'] = "Member added.";
    header("Location: family_groups.php?group_id=$selectedGroupId");
    exit;
}


/* ============================================================
   SEARCH USERS
   ============================================================ */
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

/* ============================================================
   GROUP DETAILS (fetch group name + members)
   ============================================================ */
$groupName = null;
$members = [];

if ($selectedGroupId) {
    // Fetch group name
    $stmt = $conn->prepare("SELECT group_name FROM family_groups WHERE id = ?");
    $stmt->bind_param("i", $selectedGroupId);
    $stmt->execute();
    $stmt->bind_result($groupName);
    $stmt->fetch();
    $stmt->close();

    // Fetch members
    $stmt = $conn->prepare("
        SELECT u.* FROM tbluser u
        INNER JOIN family_members f ON u.ID = f.user_id
        WHERE f.group_id = ?
        ORDER BY u.FirstName, u.LastName
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
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Font Awesome for icons -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">

  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap');

    :root{
      --primary: #6c5ce7;
      --secondary: #a29bfe;
      --accent: #00cec9;
      --muted: #7f8c8d;
      --bg: #f5f7fb;
      --card: #ffffff;
      --danger: #e74c3c;
      --success: #2ecc71;
      --shadow: rgba(16,24,40,0.08);
    }

    *{box-sizing:border-box}

    body{
      font-family: 'Poppins', sans-serif;
      background: var(--bg);
      margin:0;
      color:#222;
      -webkit-font-smoothing:antialiased;
    }

    .container{
      max-width:1100px;
      margin:30px auto;
      padding:24px;
    }

    .card{
      background:var(--card);
      border-radius:14px;
      padding:18px;
      box-shadow: 0 8px 30px var(--shadow);
    }

    header{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:16px;
      margin-bottom:18px;
    }

    h1{
      margin:0;
      color:var(--primary);
      font-size:1.6rem;
    }

    .flash{
      background: linear-gradient(90deg,var(--primary),var(--secondary));
      color: #fff;
      padding:10px 14px;
      border-radius:10px;
      text-align:center;
      margin-bottom:14px;
    }

    .row{
      display:flex;
      gap:18px;
      flex-wrap:wrap;
    }

    .col{
      flex:1;
      min-width:260px;
    }

    .groups-list{
      display:grid;
      grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
      gap:14px;
      margin-top:12px;
    }

    .group-card{
      padding:14px;
      border-radius:12px;
      background: linear-gradient(180deg, rgba(108,92,231,0.03), #fff);
      border:1px solid rgba(108,92,231,0.06);
      text-align:center;
    }

    .group-card h4{ margin:0 0 10px 0; color: #333; font-size:1rem; }
    .group-card a{ display:inline-block; text-decoration:none; padding:8px 12px; border-radius:8px; background:transparent; color:var(--primary); font-weight:600; border:1px dashed rgba(108,92,231,0.12); }

    form.inline{
      display:flex;
      gap:10px;
      align-items:center;
      justify-content:center;
      margin:8px 0;
      flex-wrap:wrap;
    }

    input[type="text"], input[type="search"]{
      padding:10px 12px;
      border-radius:10px;
      border:1.5px solid rgba(0,0,0,0.06);
      min-width:180px;
      outline:none;
    }

    button{
      padding:10px 14px;
      border-radius:10px;
      border:0;
      cursor:pointer;
      font-weight:600;
      background:var(--primary);
      color:white;
    }

    .btn-danger{ background:var(--danger); }
    .btn-success{ background:var(--success); }

    .card-grid{
      display:grid;
      grid-template-columns: repeat(auto-fit, minmax(220px,1fr));
      gap:14px;
      margin-top:12px;
    }

    .user-card{
      padding:14px;
      border-radius:12px;
      background:var(--card);
      border:1px solid rgba(0,0,0,0.03);
      text-align:center;
      box-shadow: 0 6px 18px rgba(16,24,40,0.03);
    }

    .user-card h4{ margin:6px 0; font-size:1rem; color:#222; }

    .small{
      font-size:0.9rem; color:var(--muted);
    }

    .back-btn{
      display:inline-block;
      text-decoration:none;
      padding:10px 12px;
      border-radius:10px;
      background:#3498db;
      color:white;
      font-weight:600;
    }

    .meta{
      display:flex;
      gap:10px;
      align-items:center;
      justify-content:center;
      margin-top:8px;
    }

    @media (max-width:680px){
      header{flex-direction:column; align-items:flex-start}
      .meta{flex-direction:column}
    }
  </style>
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<div class="container">
  <div class="card">
    <header>
      <div>
        <a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a>
        <h1>Family Group Management</h1>
        <p class="small">Manage your groups, invite family members, and keep expenses shared.</p>
      </div>
      <div class="meta">
        <div class="small">Logged in as: <strong><?= htmlspecialchars(getUserDisplayName($userId) ?? 'User '.$userId); ?></strong></div>
      </div>
    </header>

    <?php if (isset($_SESSION['flash_message'])): ?>
      <div class="flash"><?= htmlspecialchars($_SESSION['flash_message']); unset($_SESSION['flash_message']); ?></div>
    <?php endif; ?>

    <section>
      <h3 style="color:var(--primary); margin:0 0 8px 0;">Your Groups</h3>

      <?php if (empty($groups)): ?>
        <div class="small">You are not part of any groups yet. Create one below.</div>
      <?php endif; ?>

      <div class="groups-list">
        <?php foreach ($groups as $g): ?>
          <div class="group-card">
            <h4><?= htmlspecialchars($g['group_name']); ?></h4>
            <div style="margin-top:8px;">
              <a href="?group_id=<?= (int)$g['id']; ?>">Manage <i class="fas fa-cog"></i></a>
            </div>
            <div style="margin-top:10px;" class="small">Admin: <?= ($g['admin_user_id'] == $userId) ? 'You' : htmlspecialchars(getUserDisplayName($g['admin_user_id'])); ?></div>
          </div>
        <?php endforeach; ?>
      </div>

      <hr style="margin:18px 0; border:none; border-top:1px solid rgba(0,0,0,0.06)">

      <h3 style="color:var(--primary); margin:0 0 8px 0;">Create New Group</h3>
      <form method="POST" class="inline" autocomplete="off">
        <input type="text" name="group_name" placeholder="Enter group name">
        <button type="submit" name="create_group">Create</button>
      </form>
    </section>
  </div>

  <?php if ($selectedGroupId): ?>
    <div style="height:18px"></div>

    <div class="card">
      <div style="display:flex; justify-content:space-between; align-items:center; gap:12px;">
        <div>
          <h2 style="margin:0; color:var(--primary);">Group: <?= htmlspecialchars($groupName ?? '—'); ?></h2>
          <div class="small">Group ID: <?= (int)$selectedGroupId; ?></div>
        </div>
        <div style="text-align:right;">
          <?php if ($isAdmin): ?>
            <form method="POST" onsubmit="return confirm('Delete this group and all its members?');" style="display:inline-block;">
              <button type="submit" name="delete_group" class="btn-danger">Delete Group</button>
            </form>
          <?php endif; ?>
        </div>
      </div>

      <hr style="margin:14px 0; border:none; border-top:1px solid rgba(0,0,0,0.06)">

      <h3 style="margin:0 0 8px 0;">Group Members</h3>
      <?php if (empty($members)): ?>
        <div class="small">No members yet.</div>
      <?php endif; ?>

      <div class="card-grid" style="margin-top:10px;">
        <?php foreach ($members as $m): ?>
          <div class="user-card">
            <h4><?= htmlspecialchars($m['FirstName'] . ' ' . $m['LastName']); ?></h4>
            <div class="small"><?= htmlspecialchars($m['Email'] ?? ''); ?></div>

            <?php if ($m['ID'] != $userId): ?>
              <form method="POST" style="margin-top:10px;">
                <input type="hidden" name="remove_member" value="<?= (int)$m['ID']; ?>">
                <button type="submit">Remove</button>
              </form>
            <?php else: ?>
              <div style="margin-top:8px;" class="small">You (group member)</div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>

      <hr style="margin:14px 0; border:none; border-top:1px solid rgba(0,0,0,0.06)">

      <h3 style="margin:0 0 8px 0;">Add Members</h3>
      <form method="GET" class="inline">
        <input type="hidden" name="group_id" value="<?= (int)$selectedGroupId; ?>">
        <input type="search" name="search" placeholder="Search users by name or email" value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
        <button type="submit">Search</button>
      </form>

      <?php if (!empty($searchResults)): ?>
        <div class="card-grid" style="margin-top:12px;">
          <?php foreach ($searchResults as $user): ?>
            <div class="user-card">
              <h4><?= htmlspecialchars($user['FirstName'] . ' ' . $user['LastName']); ?></h4>
              <div class="small"><?= htmlspecialchars($user['Email']); ?></div>

              <form method="POST" style="margin-top:10px;">
                <input type="hidden" name="add_member" value="<?= (int)$user['ID']; ?>">
                <button type="submit" class="btn-success">Add</button>
              </form>
            </div>
          <?php endforeach; ?>
        </div>
      <?php elseif (isset($_GET['search'])): ?>
        <div class="small" style="margin-top:12px;">No users found.</div>
      <?php endif; ?>

    </div>
  <?php endif; ?>

  <div style="height:30px"></div>
  <footer style="text-align:center; color:var(--muted); font-size:0.9rem;">
    &copy; <?= date('Y'); ?> Your App — Family Groups
  </footer>
</div>
</body>
</html>
<?php
// includes/functions.php
// Add or merge these helpers into your existing functions file.

/**
 * Return a display name for a user id.
 * Tries FirstName + LastName, falls back to email or "User {id}".
 */
function getUserDisplayName($userId) {
    global $conn;
    $userId = (int)$userId;
    $stmt = $conn->prepare("SELECT FirstName, LastName, Email FROM tbluser WHERE ID = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$res) return "User $userId";

    $first = trim($res['FirstName'] ?? '');
    $last  = trim($res['LastName'] ?? '');
    $email = trim($res['Email'] ?? '');

    if ($first !== '' || $last !== '') {
        return trim("$first $last");
    }
    if ($email !== '') return $email;
    return "User $userId";
}

/**
 * Safe redirect helper that avoids header already-sent issues and exits.
 */
function safe_redirect($url) {
    if (!headers_sent()) {
        header("Location: $url");
        exit;
    } else {
        echo "<script>location.href=" . json_encode($url) . ";</script>";
        echo "<noscript><meta http-equiv='refresh' content='0;url={$url}' /></noscript>";
        exit;
    }
}
