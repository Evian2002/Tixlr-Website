<?php
// Fall back to XAMPP defaults if environment variables aren't set
$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$dbname = getenv('DB_NAME') ?: 'tixlr';
$port = getenv('DB_PORT') ?: 3306;

$conn = mysqli_connect($host, $user, $pass, $dbname, (int)$port);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>