<?php
include("../config/db.php");
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include("../auth/auth_check.php");
include("tournament_state.php");

if(!isset($_SESSION['active_tournament'])){
    header("Location: ../dashboard/maindashboard.php");
    exit();
}

$tid = intval($_SESSION['active_tournament']);
$state = getTournamentState($conn, $tid);

if(!$state['can_generate_playoffs']){
    clearPlayoffsIfTournamentNotReady($conn, $tid, $state);
    header("Location: playoff_management.php?error=" . urlencode(implode(" ", $state['messages'])));
    exit();
}

// 1. CLEAR OLD PLAYOFFS
$conn->query("DELETE FROM matches WHERE tournament_id=$tid AND match_type='Playoffs'");

// 2. FETCH TOP 6 SEEDS
$res = $conn->query("SELECT team_id FROM standings WHERE tournament_id=$tid ORDER BY points DESC, wins DESC LIMIT 6");
$seeds = [];
while($row = $res->fetch_assoc()) { $seeds[] = $row['team_id']; }

if(count($seeds) < 6) {
    header("Location: matches.php?error=Need 6 teams in RR to start playoffs."); exit();
}

// 3. INSERT ROUNDS
// UB Semis (BO3)
$conn->query("INSERT INTO matches (tournament_id, team1_id, team2_id, match_type, round_name, series_limit, status) VALUES ($tid, {$seeds[0]}, {$seeds[3]}, 'Playoffs', 'Upper Bracket Semi Final 1', 3, 'pending')");
$conn->query("INSERT INTO matches (tournament_id, team1_id, team2_id, match_type, round_name, series_limit, status) VALUES ($tid, {$seeds[1]}, {$seeds[2]}, 'Playoffs', 'Upper Bracket Semi Final 2', 3, 'pending')");

// LB Semis (BO1): losers from UB semis are filled after those matches complete.
$conn->query("INSERT INTO matches (tournament_id, team2_id, match_type, round_name, series_limit, status) VALUES ($tid, {$seeds[4]}, 'Playoffs', 'Lower Bracket Semi Final 1', 1, 'pending')");
$conn->query("INSERT INTO matches (tournament_id, team2_id, match_type, round_name, series_limit, status) VALUES ($tid, {$seeds[5]}, 'Playoffs', 'Lower Bracket Semi Final 2', 1, 'pending')");

// PLACEHOLDERS
$conn->query("INSERT INTO matches (tournament_id, match_type, round_name, series_limit, status) VALUES ($tid, 'Playoffs', 'Upper Bracket Final', 5, 'pending')");
$conn->query("INSERT INTO matches (tournament_id, match_type, round_name, series_limit, status) VALUES ($tid, 'Playoffs', 'Lower Bracket Final', 3, 'pending')");
$conn->query("INSERT INTO matches (tournament_id, match_type, round_name, series_limit, status) VALUES ($tid, 'Playoffs', 'Grand Final', 7, 'pending')");

header("Location: playoff_management.php?msg=Bracket Ready!");
