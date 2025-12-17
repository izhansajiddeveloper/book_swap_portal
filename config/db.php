<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$server = 'localhost';
$username = 'root';
$password = '';
$db_name = 'book_swap_portal';
$conn = new mysqli($server, $username, $password, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} else {
    //echo "DB Connected";
}
?>