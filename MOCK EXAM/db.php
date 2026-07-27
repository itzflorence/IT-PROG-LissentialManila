<?php
$host     = 'localhost';
$dbname   = 'journal_db';
$username = 'root';
$password = 'p@ssword';

$conn = mysqli_connect($host, $username, $password, $dbname);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
