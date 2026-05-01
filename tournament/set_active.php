<?php
include("../config/db.php");
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if(isset($_GET['id'])){
    $_SESSION['active_tournament'] = intval($_GET['id']);
}

header("Location: ../dashboard/maindashboard.php");
?>
