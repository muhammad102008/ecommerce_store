
<?php

session_start();

// Clear all session data
$_SESSION = [];

// Destroy the session
session_destroy();

// Return to homepage
header("Location: index.php");
exit;

?>
