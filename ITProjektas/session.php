<?php
session_start();

if (!isset($_SESSION['login_user'])) {
    header("location: login.php");
    exit;
}

$login_session = $_SESSION['login_user'];
?>