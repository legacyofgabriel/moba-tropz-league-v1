<?php
include("../config/db.php");
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include("../auth/auth_check.php");

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['token'] ?? '')) {
        $response['message'] = 'Security token mismatch.';
        echo json_encode($response);
        exit();
    }

    $player_id = intval($_POST['player_id'] ?? 0);
    $field = $_POST['field'] ?? '';
    $value = $_POST['value'] ?? '';
    $tournament_id = intval($_SESSION['active_tournament'] ?? 0);
    $user_id = intval($_SESSION['user_id'] ?? 0);

    if ($player_id === 0 || empty($field) || $tournament_id === 0 || $user_id === 0) {
        $response['message'] = 'Invalid parameters.';
        echo json_encode($response);
        exit();
    }

    $allowed_fields = ['role', 'is_captain'];
    if (!in_array($field, $allowed_fields)) {
        $response['message'] = 'Invalid field for update.';
        echo json_encode($response);
        exit();
    }

    $stmt = $conn->prepare("UPDATE players SET {$field} = ? WHERE id = ? AND tournament_id = ?");
    if ($field === 'is_captain') {
        $value = intval($value); // Ensure integer for boolean
        $stmt->bind_param("iii", $value, $player_id, $tournament_id);
    } else { // role
        $stmt->bind_param("sii", $value, $player_id, $tournament_id);
    }

    if ($stmt->execute()) {
        log_tactical_action($conn, $user_id, $tournament_id, "PLAYER_UPDATE", "Operative ID {$player_id}: {$field} updated to '{$value}'.");
        $response['success'] = true;
        $response['message'] = 'Player updated successfully.';
    } else {
        $response['message'] = 'Database error: ' . $conn->error;
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
exit();