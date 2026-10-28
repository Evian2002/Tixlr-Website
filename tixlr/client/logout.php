<?php
session_start();
session_unset();   // remove all session variables
session_destroy(); // destroy the session

// Redirect to landing page instead of login
header("Location: ../index.php");
exit;