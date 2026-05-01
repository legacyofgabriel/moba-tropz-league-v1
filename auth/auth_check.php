<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if(!isset($_SESSION['user_id'])){
    header("Location: ../auth/login.php");
    exit(); // Napakahalaga nito para tumigil ang script execution
}
?>  