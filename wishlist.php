<?php


session_start();
define('INCLUDED', true);

require_once 'includes/database.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}

$userId = $_SESSION['user_id'];

$query = $conn->prepare("
    SELECT w.*, c.CategoryName 
    FROM tblwishlist w
    JOIN tblcategory c ON w.CategoryID = c.ID
    WHERE w.UserId = ?
    ORDER BY w.WishlistDate DESC
");
$query->bind_param("i", $userId);
$query->execute();
$items = $query->get_result()->fetch_all(MYSQLI_ASSOC);

// Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete = $conn->prepare("DELETE FROM tblwishlist WHERE ID = ? AND UserId = ?");
    $delete->bind_param("ii", $_POST['delete_id'], $userId);
    $delete->execute();
    header("Location: wishlist.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Wishlist</title>
    <style>
        body { background: #a29bfe; margin:0; display:flex; font-family:Poppins; }
        .main-content { margin-left:250px; padding:2rem; width:calc(100% - 250px); }
        .container { background:rgba(255,255,255,0.3); padding:2rem; border-radius:15px; }
        table { width:100%; border-spacing:0 10px; }
        th, td { background:white; padding:1rem; border-radius:10px; }
        .btn-del { background:red; color:white; border:none; padding:0.5rem 1rem; border-radius:8px; }
        .add-btn { background:#6c5ce7; padding:0.7rem 1.5rem; color:white; text-decoration:none; border-radius:10px; }
    </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="main-content">
    <div class="container">

        <h1>Wishlist</h1>

        <a class="add-btn" href="add_wishlist.php">+ Add Wishlist Item</a>
        <br><br>

        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Item</th>
                    <th>Cost</th>
                    <th>Category</th>
                    <th>Note</th>
                    <th>Delete</th>
                </tr>
            </thead>
            <tbody>

                <?php foreach ($items as $i): ?>
                <tr>
                    <td><?= $i['WishlistDate'] ?></td>
                    <td><?= htmlspecialchars($i['ItemName']) ?></td>
                    <td>₹<?= number_format($i['ItemPrice'], 2) ?></td>
                    <td><?= htmlspecialchars($i['CategoryName']) ?></td>
                    <td><?= htmlspecialchars($i['ItemNote']) ?></td>
                    <td>
                        <form method="POST">
                            <input type="hidden" name="delete_id" value="<?= $i['ID'] ?>">
                            <button class="btn-del" onclick="return confirm('Delete item?')">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>

            </tbody>
        </table>

    </div>
</div>

</body>
</html>
