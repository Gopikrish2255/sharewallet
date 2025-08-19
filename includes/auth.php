<?php
require_once 'database.php';

function registerUser($firstName, $lastName, $gender, $email, $mobile, $password) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO tbluser (FirstName, LastName, Gender, Email, Mobile, Password) VALUES (?, ?, ?, ?, ?, ?)");
    return $stmt->execute([$firstName, $lastName, $gender, $email, $mobile, password_hash($password, PASSWORD_BCRYPT)]);
}

function loginUser($email, $password) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM tbluser WHERE Email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && password_verify($password, $user['Password'])) {
        session_start();
        $_SESSION['user_id'] = $user['UserID'];
        return true;
    }
    return false;
}
?>
