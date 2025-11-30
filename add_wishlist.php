<?php
session_start();
define('INCLUDED', true);

require_once 'includes/database.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}

$userId = $_SESSION['user_id'];

// Fetch categories
$catQuery = $conn->prepare("SELECT ID, CategoryName FROM tblcategory ORDER BY CategoryName ASC");
$catQuery->execute();
$categories = $catQuery->get_result()->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $date = $_POST['wishlist_date'];
    $name = sanitize($_POST['item_name']);
    $price = floatval($_POST['item_price']);
    $cat = intval($_POST['category']);
    $note = sanitize($_POST['item_note']);

    if (!empty($name) && !empty($date) && $cat > 0) {
        $stmt = $conn->prepare("
            INSERT INTO tblwishlist (UserId, WishlistDate, ItemName, ItemPrice, CategoryID, ItemNote)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("issdis", $userId, $date, $name, $price, $cat, $note);

        if ($stmt->execute()) {
            $success_message = "Wishlist item added!";
        } else {
            $error_message = "Failed to add wishlist item.";
        }
    } else {
        $error_message = "Please fill in all required fields.";
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Wishlist Item</title>
    <style>
        body {
            background: #a29bfe;
            font-family: Poppins, sans-serif;
            display: flex;
            margin: 0;
        }
        .main-content {
            margin-left: 250px;
            padding: 2rem;
            width: calc(100% - 250px);
        }
        .container {
            background: rgba(255,255,255,0.3);
            padding: 2rem;
            border-radius: 15px;
            backdrop-filter: blur(8px);
        }
        input, select, textarea {
            width: 100%;
            padding: 0.8rem;
            margin: 0.7rem 0;
            border-radius: 10px;
            border: 1px solid #ddd;
        }
        .btn {
            background: #6c5ce7;
            color: white;
            border: none;
            padding: 1rem;
            width: 100%;
            border-radius: 10px;
            cursor: pointer;
        }
    </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="main-content">
    <div class="container">
        <h1>Add Wishlist Item</h1>

        <?php if (isset($success_message)) echo "<p style='color:green'>$success_message</p>"; ?>
        <?php if (isset($error_message)) echo "<p style='color:red'>$error_message</p>"; ?>

        <form method="POST">

            <label>Date</label>
            <input type="date" name="wishlist_date" required>

            <label>Item</label>
            <input type="text" name="item_name" placeholder="Item name" required>

            <label>Estimated Cost</label>
            <input type="number" step="0.01" name="item_price" placeholder="Cost">

            <label>Category</label>
            <select name="category" required>
                <option value="">Select Category</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= $c['ID'] ?>"><?= htmlspecialchars($c['CategoryName']) ?></option>
                <?php endforeach; ?>
            </select>

            <label>Note</label>
            <textarea name="item_note" rows="3" placeholder="Optional notes"></textarea>

            <button class="btn">Add</button>

        </form>
    </div>
</div>

</body>
</html>
