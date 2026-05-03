<?php
include("../config/db.php");
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include("../auth/auth_check.php");
include("../includes/player_photos.php");

ensure_player_photo_column($conn);

if(!isset($_SESSION['active_tournament'])){
    header("Location: ../dashboard/maindashboard.php");
    exit();
}

$team_id = intval($_GET['team_id']);
$tournament_id = intval($_SESSION['active_tournament']);
$error = "";

$stmt = $conn->prepare("SELECT name FROM teams WHERE id = ?");
$stmt->bind_param("i", $team_id);
$stmt->execute();
$team_res = $stmt->get_result();
$team_data = $team_res->fetch_assoc();

// Check current player count
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM players WHERE team_id = ?");
$stmt->bind_param("i", $team_id);
$stmt->execute();
$count_p = $stmt->get_result()->fetch_assoc();
$current_p = intval($count_p['total']);

if(isset($_POST['add'])){
    $name = trim($_POST['name']);
    $role = $_POST['role'];
    $captain = isset($_POST['captain']) ? 1 : 0;
    $photo_error = validate_player_photo_upload($_FILES['photo'] ?? []);

    // Validation: Max 6 players
    if($current_p >= 6) {
        $error = "Ang team na ito ay puno na (Maximum of 6 players only).";
    } elseif($photo_error) {
        $error = $photo_error;
    } else {
        // Validation: Duplicate Name check sa buong tournament
        $stmt = $conn->prepare("SELECT id FROM players WHERE name = ? AND tournament_id = ?");
        $stmt->bind_param("si", $name, $tournament_id);
        $stmt->execute();
        $check_dup = $stmt->get_result();

        if($check_dup->num_rows > 0) {
            $error = "Ang pangalang '$name' ay registered na sa tournament na ito.";
        } else {
            $stmt = $conn->prepare("INSERT INTO players (team_id, tournament_id, name, role, is_captain) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iissi", $team_id, $tournament_id, $name, $role, $captain);
            $stmt->execute();
            $new_player_id = $conn->insert_id;

            $photo_path = save_player_photo_upload($_FILES['photo'] ?? [], $new_player_id);
            if($photo_path) {
                $stmt = $conn->prepare("UPDATE players SET photo_path = ? WHERE id = ?");
                $stmt->bind_param("si", $photo_path, $new_player_id);
                $stmt->execute();
            }
            header("Location: ../teams/teams.php");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Player — MOBA TROPZ</title>
    <link rel="stylesheet" href="../dashboard/maindashboard.css">
    <style>
        body { margin: 0; height: 100vh; display: flex; align-items: center; justify-content: center; }
        .card { 
            background: #000; padding: 40px; border: 2px solid var(--cyan); 
            width: 100%; max-width: 440px; position: relative;
            box-shadow: 0 0 30px var(--cyan-glow);
        }
        .header { text-align: center; margin-bottom: 25px; }
        .header h2 { font-family: 'Rajdhani', sans-serif; font-size: 14px; color: var(--muted); text-transform: uppercase; letter-spacing: 3px; }
        .team-name { color: var(--cyan); font-family: 'Rajdhani', sans-serif; font-size: 32px; font-weight: 800; font-style: italic; text-transform: uppercase; display: block; }
        .error-msg { background: rgba(248, 113, 113, 0.1); border: 1px solid #f87171; color: #f87171; padding: 10px; border-radius: 8px; font-size: 12px; text-align: center; margin-bottom: 15px; }
        input, select { 
            width: 100%; padding: 14px; background: #0a0a0c; border: 1px solid var(--border); 
            color: #fff; margin-top: 15px; box-sizing: border-box; font-family: 'Inter';
        }
        input:focus { border-color: var(--cyan); outline: none; }
        input[type="file"] { padding: 10px; font-size: 13px; color: #94a3b8; }
        .btn { 
            width: 100%; padding: 18px; background: var(--cyan); border: none; 
            color: #000; font-weight: 900; cursor: pointer; margin-top: 25px; 
            text-transform: uppercase; clip-path: polygon(5% 0, 100% 0, 95% 100%, 0 100%);
        }
    </style>
</head>
<body>
<div class="card">
    <div class="header">
        <h2>Add Player to</h2>
        <span class="team-name"><?= strtoupper($team_data['name']) ?></span>
    </div>
    <?php if($error): ?><div class="error-msg"><?= $error ?></div><?php endif; ?>
    <form method="POST" enctype="multipart/form-data">
        <input type="text" name="name" placeholder="In-Game Name (IGN)" required autocomplete="off">
        <select name="role" required>
            <option value="CORE">CORE / JUNGLER</option>
            <option value="MID">MIDLANER</option>
            <option value="ROAM">ROAMER</option>
            <option value="GOLD">GOLD LANER</option>
            <option value="EXP">EXP LANER</option>
            <option value="COACH">COACH</option>
            <option value="SUB">SUBSTITUTE</option>
        </select>
        <label for="player-photo-upload" style="
            display: block; width: 100%; padding: 10px; background: #020617; 
            border: 1px solid rgba(255,255,255,0.1); color: #94a3b8; border-radius: 8px; 
            font-size: 12px; text-align: center; cursor: pointer; transition: all 0.3s; margin-top: 15px;
        ">
            <span id="player-photo-filename">Upload Player Photo (Optional)</span>
        </label>
        <input type="file" name="photo" id="player-photo-upload" accept="image/jpeg,image/png,image/webp" style="display: none;">
        <div class="hint" style="margin-top: 7px;">Optional profile photo. JPG, PNG, or WEBP only. Max 2MB.</div>
        <div style="display:flex; align-items:center; gap:10px; margin-top:15px; font-size:14px;">
            <input type="checkbox" name="captain" id="c" style="width:20px; height:20px; accent-color:var(--cyan); cursor:pointer; margin:0;">
            <label for="c" style="cursor:pointer; color:var(--text); font-weight:600;">Set as Team Captain?</label>
        </div>
        <button type="submit" name="add" class="btn">Confirm Registration</button>
        <a href="../teams/teams.php" style="display:block; text-align:center; color:#94a3b8; margin-top:15px; text-decoration:none; font-size:12px;">Cancel</a>
    </form>
</div>
<script>
    document.getElementById('player-photo-upload').addEventListener('change', function() {
        const filenameSpan = document.getElementById('player-photo-filename');
        if (this.files && this.files.length > 0) {
            filenameSpan.textContent = this.files[0].name;
        } else {
            filenameSpan.textContent = 'Upload Player Photo (Optional)';
        }
    });
</script>
</body>
</html>
