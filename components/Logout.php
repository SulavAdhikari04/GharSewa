<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
session_unset();
session_destroy();
setcookie('user_id', '', time() - 3600, "/"); // Remove cookie
header('Location: ../pages/login.php');
exit(); 