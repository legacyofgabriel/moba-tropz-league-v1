<?php
include("../config/db.php");
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include("../auth/auth_check.php");

if(isset($_SESSION['active_tournament'])){
    $tid = $_SESSION['active_tournament'];
    
    // 1. Burahin lahat ng matches
    $stmt1 = $conn->prepare("DELETE FROM matches WHERE tournament_id = ?");
    $stmt1->bind_param("i", $tid);
    $stmt1->execute();
    
    // 2. I-reset ang Standings sa 0 (pero huwag burahin ang teams)
    $stmt2 = $conn->prepare("UPDATE standings SET played=0, wins=0, losses=0, points=0 WHERE tournament_id = ?");
    $stmt2->bind_param("i", $tid);
    $stmt2->execute();
    
    // 3. Burahin din ang player match stats para malinis talaga
    $stmt3 = $conn->prepare("DELETE FROM player_match_stats WHERE tournament_id = ?");
    $stmt3->bind_param("i", $tid);
    $stmt3->execute();

    header("Location: matches.php?msg=Lahat ng matches ay matagumpay na nabura.");
    exit();
}
