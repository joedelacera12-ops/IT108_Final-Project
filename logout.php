<?php
// Unified logout handler
session_start();
session_destroy();

// Redirect to home page
header('Location: /ecommerce_farmers_fishers/php/index.php');
exit;
?>