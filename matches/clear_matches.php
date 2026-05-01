<?php
include("../config/db.php");
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include("../auth/auth_check.php");

if(isset($_SESSION['active_tournament'])){
    $tid = $_SESSION['active_tournament'];
    
    // 1. Burahin lahat ng matches
    $conn->query("DELETE FROM matches WHERE tournament_id = $tid");
    
    // 2. I-reset ang Standings sa 0 (pero huwag burahin ang teams)
    $conn->query("UPDATE standings SET played=0, wins=0, losses=0, points=0 WHERE tournament_id = $tid");
    
    // 3. Burahin din ang player match stats para malinis talaga
    $conn->query("DELETE FROM player_match_stats WHERE tournament_id = $tid");

    header("Location: matches.php?msg=Lahat ng matches ay matagumpay na nabura.");
    exit();
}
