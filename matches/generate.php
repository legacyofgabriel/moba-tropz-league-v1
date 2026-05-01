<?php
include("../config/db.php");
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include("../auth/auth_check.php");
include("tournament_state.php");

if(!isset($_SESSION['active_tournament'])){
    header("Location: ../dashboard/maindashboard.php");
    exit();
}

$tournament_id = intval($_SESSION['active_tournament']);
$state = getTournamentState($conn, $tournament_id);

if(!$state['can_generate_round_robin']){
    header("Location: matches.php?error=" . urlencode(implode(" ", $state['messages'])));
    exit();
}

// 1. Kunin ang required team count mula sa settings
$t_res = $conn->query("SELECT team_count, name FROM tournaments WHERE id = $tournament_id");
$t_info = $t_res->fetch_assoc();
$target_teams = intval($t_info['team_count']);

// 2. Kunin ang actual teams na registered
$teams_res = $conn->query("SELECT id, name FROM teams WHERE tournament_id = $tournament_id");
$actual_teams = $teams_res->num_rows;

// VALIDATION: Team Count Check
if ($actual_teams !== $target_teams) {
    header("Location: matches.php?error=Kailangan ng saktong $target_teams teams para magsimula. ($actual_teams registered)");
    exit();
}

// 3. VALIDATION: Player Count Check (Atleast 5, Max 6)
$valid_teams = [];
while($team = $teams_res->fetch_assoc()){
    $tid = $team['id'];
    $p_res = $conn->query("SELECT COUNT(*) as total FROM players WHERE team_id = $tid");
    $p_count = $p_res->fetch_assoc()['total'];

    if ($p_count < 5) {
        header("Location: matches.php?error=Ang team na '".strtoupper($team['name'])."' ay kulang sa players (Atleast 5 kailangan).");
        exit();
    }
    if ($p_count > 6) {
        header("Location: matches.php?error=Ang team na '".strtoupper($team['name'])."' ay sobra sa players (Maximum of 6 lang).");
        exit();
    }
    $valid_teams[] = $tid;
}

// 4. KUNG PASADO LAHAT, REFRESH DATA
$conn->query("DELETE FROM matches WHERE tournament_id = $tournament_id");
$conn->query("DELETE FROM standings WHERE tournament_id = $tournament_id");

// GENERATE ROUND ROBIN
for($i = 0; $i < count($valid_teams); $i++){
    for($j = $i + 1; $j < count($valid_teams); $j++){
        $t1 = $valid_teams[$i];
        $t2 = $valid_teams[$j];
        $conn->query("INSERT INTO matches (tournament_id, team1_id, team2_id, match_type, status)
                      VALUES ($tournament_id, $t1, $t2, 'Round Robin', 'pending')");
    }
}

// INITIALIZE STANDINGS
foreach($valid_teams as $team_id){
    $conn->query("INSERT INTO standings (tournament_id, team_id, played, wins, losses, points) VALUES ($tournament_id, $team_id, 0, 0, 0, 0)");
}

header("Location: matches.php?msg=Tournament schedule generated successfully!");
exit();
