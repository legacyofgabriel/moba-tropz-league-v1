<?php
include("../config/db.php");
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include("../auth/auth_check.php");
include("tournament_state.php");

if(isset($_POST['update'])){
    $match_id = intval($_POST['match_id']);
    $s1 = intval($_POST['score1']); $s2 = intval($_POST['score2']);
    $tid = intval($_SESSION['active_tournament']);
    $state = getTournamentState($conn, $tid);

    $m = $conn->query("SELECT m.*, t1.name as t1, t2.name as t2 FROM matches m 
                       LEFT JOIN teams t1 ON m.team1_id = t1.id 
                       LEFT JOIN teams t2 ON m.team2_id = t2.id 
                       WHERE m.id = $match_id")->fetch_assoc();

    if(!$m || $m['is_locked']) exit();

    if($m['match_type'] == 'Round Robin' && (!$state['can_generate_round_robin'] || $state['rr_stale'] > 0)) {
        header("Location: matches.php?error=" . urlencode("Cannot update matches while teams/players are incomplete or out of sync."));
        exit();
    }

    if($m['match_type'] == 'Playoffs' && !$state['can_generate_playoffs']) {
        clearPlayoffsIfTournamentNotReady($conn, $tid, $state);
        header("Location: playoff_management.php?error=" . urlencode("Playoffs are no longer valid because tournament data changed."));
        exit();
    }

    if($s1 === $s2) {
        $target = ($m['match_type'] == 'Playoffs') ? 'playoff_management.php' : 'matches.php';
        header("Location: $target?error=Scores cannot be tied.");
        exit();
    }

    // ─── SCORING LIMIT VALIDATION ───
    $max_wins = ceil($m['series_limit'] / 2);
    if($m['match_type'] == 'Playoffs') {
        if($s1 < $max_wins && $s2 < $max_wins) {
            header("Location: playoff_management.php?error=Kailangan ng $max_wins wins para sa BO" . $m['series_limit']); exit();
        }
        if($s1 > $max_wins || $s2 > $max_wins) {
            header("Location: playoff_management.php?error=Limit is $max_wins wins only."); exit();
        }
    }

    $win_id = ($s1 > $s2) ? $m['team1_id'] : $m['team2_id'];
    $los_id = ($s1 > $s2) ? $m['team2_id'] : $m['team1_id'];
    $win_name = ($s1 > $s2) ? $m['t1'] : $m['t2'];

    // 1. I-SAVE ANG RESULT NG CURRENT MATCH
    $conn->query("UPDATE matches SET score1=$s1, score2=$s2, status='completed', is_locked=1, winner_name='".mysqli_real_escape_string($conn, $win_name)."' WHERE id=$match_id");

    if($m['match_type'] == 'Round Robin') {
        $conn->query("UPDATE standings SET played=played+1, wins=wins+1, points=points+3 WHERE tournament_id=$tid AND team_id=$win_id");
        $conn->query("UPDATE standings SET played=played+1, losses=losses+1 WHERE tournament_id=$tid AND team_id=$los_id");
        header("Location: matches.php?msg=Match result saved.");
        exit();
    }

    // 2. ─── ADVANCED DOUBLE ELIM LOGIC ───
    if($m['match_type'] == 'Playoffs') {
        
        // UB SEMI 1 -> Winner to UB Final, Loser to LB Semi vs Seed 5
        if($m['round_name'] == 'Upper Bracket Semi Final 1') {
            $conn->query("UPDATE matches SET team1_id=$win_id WHERE round_name='Upper Bracket Final' AND tournament_id=$tid");
            $conn->query("UPDATE matches SET team1_id=$los_id WHERE round_name='Lower Bracket Semi Final 1' AND tournament_id=$tid");
        }
        
        // UB SEMI 2 -> Winner to UB Final, Loser to LB Semi vs Seed 6
        if($m['round_name'] == 'Upper Bracket Semi Final 2') {
            $conn->query("UPDATE matches SET team2_id=$win_id WHERE round_name='Upper Bracket Final' AND tournament_id=$tid");
            $conn->query("UPDATE matches SET team1_id=$los_id WHERE round_name='Lower Bracket Semi Final 2' AND tournament_id=$tid");
        }

        // LB SEMIS -> Winner to LB Finals, Loser Eliminated
        if($m['round_name'] == 'Lower Bracket Semi Final 1') {
            $conn->query("UPDATE matches SET team1_id=$win_id WHERE round_name='Lower Bracket Final' AND tournament_id=$tid");
        }
        if($m['round_name'] == 'Lower Bracket Semi Final 2') {
            $conn->query("UPDATE matches SET team2_id=$win_id WHERE round_name='Lower Bracket Final' AND tournament_id=$tid");
        }

        // UB FINAL -> Winner to Grand Finals, Loser to LB Finals
        if($m['round_name'] == 'Upper Bracket Final') {
            $conn->query("UPDATE matches SET team1_id=$win_id WHERE round_name='Grand Final' AND tournament_id=$tid");
            // Note: Ang matatalo sa UB Final ay karaniwang naghihintay sa LB Final winner
            $conn->query("UPDATE matches SET team2_id=$los_id WHERE round_name='Lower Bracket Final' AND tournament_id=$tid");
        }

        // LB FINAL -> Winner to Grand Finals, Loser Eliminated
        if($m['round_name'] == 'Lower Bracket Final') {
            $conn->query("UPDATE matches SET team2_id=$win_id WHERE round_name='Grand Final' AND tournament_id=$tid");
        }
    }

    header("Location: playoff_management.php?msg=Success! Bracket updated.");
    exit();
}
