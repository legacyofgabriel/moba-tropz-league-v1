<?php
include("../config/db.php");
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include("../auth/auth_check.php");

if(!isset($_GET['id'])){
    header("Location: ../dashboard/maindashboard.php?error=Walang tournament na pinili para burahin.");
    exit();
}

$tournament_id_to_delete = intval($_GET['id']);

if (!verify_csrf_token($_GET['token'] ?? '')) {
    header("Location: ../dashboard/maindashboard.php?error=Security token verification failed.");
    exit();
}

// Check if the tournament exists
$stmt = $conn->prepare("SELECT id FROM tournaments WHERE id = ?");
$stmt->bind_param("i", $tournament_id_to_delete);
$stmt->execute();
$check_res = $stmt->get_result();
if($check_res->num_rows === 0){
    header("Location: ../dashboard/maindashboard.php?error=Tournament not found.");
    exit();
}

$res = $check_res->fetch_assoc();
$tournament_name = "Tournament ID: " . $tournament_id_to_delete; // Simplified as main record is about to be purged
log_tactical_action($conn, $_SESSION['user_id'], $tournament_id_to_delete, "TERMINATE", "Authorized purge of tournament and all associated tactical data.");

// Delete the tournament. Due to ON DELETE CASCADE, all related records (teams, players, matches, standings, player_match_stats) will also be deleted.
$stmt = $conn->prepare("DELETE FROM tournaments WHERE id = ?");
$stmt->bind_param("i", $tournament_id_to_delete);
$stmt->execute();

// If the deleted tournament was the active one, unset the session variable
if(isset($_SESSION['active_tournament']) && intval($_SESSION['active_tournament']) === $tournament_id_to_delete){
    unset($_SESSION['active_tournament']);
}

header("Location: ../dashboard/maindashboard.php?msg=Tournament successfully deleted.");
exit();
?>