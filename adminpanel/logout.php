<?php
session_start();
require_once '../includes/db_connect.php';

unset($_SESSION['admin_logged_in']);
unset($_SESSION['admin_username']);

header('Location: ' . BASE_URL . '/adminpanel/login.php');
exit;
