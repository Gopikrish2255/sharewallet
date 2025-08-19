<?php
$host = 'localhost'; // Your database host
$username = 'root';  // Your database username
$password = '';      // Your database password
$dbname = 'detsdb'; // Your database name

// Create a connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
