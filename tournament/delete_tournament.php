<?php
include("../config/db.php");
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include("../auth/auth_check.php");

if(!isset($_GET['id'])){
    header("Location: ../dashboard/maindashboard.php?error=Walang tournament na pinili para burahin.");
    exit();
}

$tournament_id_to_delete = intval($_GET['id']);

// Check if the tournament exists
$check_res = $conn->query("SELECT id FROM tournaments WHERE id = $tournament_id_to_delete");
if($check_res->num_rows === 0){
    header("Location: ../dashboard/maindashboard.php?error=Tournament not found.");
    exit();
}

// Delete the tournament. Due to ON DELETE CASCADE, all related records (teams, players, matches, standings, player_match_stats) will also be deleted.
$conn->query("DELETE FROM tournaments WHERE id = $tournament_id_to_delete");

// If the deleted tournament was the active one, unset the session variable
if(isset($_SESSION['active_tournament']) && intval($_SESSION['active_tournament']) === $tournament_id_to_delete){
    unset($_SESSION['active_tournament']);
}

header("Location: ../dashboard/maindashboard.php?msg=Tournament successfully deleted.");
exit();
?>