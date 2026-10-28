<?php
include "../config.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    die("Access denied.");
}

if (!isset($_GET['venue_id'])) {
    die("No venue selected.");
}

$venue_id = intval($_GET['venue_id']);

$stmt = $conn->prepare("DELETE FROM venues WHERE venue_id = ?");
$stmt->bind_param("i", $venue_id);
$stmt->execute();

header("Location: manage.php");
exit;<?php
