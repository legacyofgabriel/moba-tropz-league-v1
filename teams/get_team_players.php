<?php
include("../config/db.php");
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include("../auth/auth_check.php");

header('Content-Type: application/json');

if (!isset($_GET['team_id']) || !isset($_SESSION['active_tournament'])) {
    echo json_encode(['error' => 'Invalid request.']);
    exit();
}

$team_id = intval($_GET['team_id']);
$tournament_id = intval($_SESSION['active_tournament']);

$stmt = $conn->prepare("SELECT id, name, role FROM players WHERE team_id = ? AND tournament_id = ? ORDER BY name ASC");
$stmt->bind_param("ii", $team_id, $tournament_id);
$stmt->execute();
$result = $stmt->get_result();
$players = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode($players);

exit();