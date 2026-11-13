<?php
session_start();
session_destroy();
require_once 'includes/db_connect.php';
header('Location: ' . BASE_URL);
exit;
?>
