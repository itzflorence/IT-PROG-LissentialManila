<?php
$host     = 'localhost';
$dbname   = 'lissential_manila_db';
$username = 'root';
$password = '';

$conn = mysqli_connect($host, $username, $password, $dbname, 3306);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
