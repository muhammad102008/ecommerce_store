
<?php

session_start();

// Remove admin session details
unset($_SESSION["admin_id"]);
unset($_SESSION["admin_name"]);

// Return to admin login
header("Location: admin_login.php");
exit;

?>
