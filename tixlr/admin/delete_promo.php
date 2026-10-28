<?php
include "../config.php";

$id = intval($_GET['id']);

mysqli_query($conn, "DELETE FROM promo_codes WHERE id = $id");

header("Location: promo_codes.php");
exit;