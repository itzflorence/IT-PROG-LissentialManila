<?php
session_start();
require 'db.php';
$error = '';

// capture logged in user ID
$user_id = $_SESSION['user_id'];

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        die("Invalid entry ID.");
}

$id = (int) $_GET['id'];

$delete = "DELETE FROM entries WHERE id = '$id'";

mysqli_query($conn, $delete);
mysqli_close($conn);

header("Location: read.php");
exit();
?>