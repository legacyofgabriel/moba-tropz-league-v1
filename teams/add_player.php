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

$team_res = $conn->query("SELECT name FROM teams WHERE id=$team_id");
$team_data = $team_res->fetch_assoc();

// Check current player count
$count_p = $conn->query("SELECT COUNT(*) as total FROM players WHERE team_id = $team_id")->fetch_assoc();
$current_p = intval($count_p['total']);

if(isset($_POST['add'])){
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $captain = isset($_POST['captain']) ? 1 : 0;
    $photo_error = validate_player_photo_upload($_FILES['photo'] ?? []);

    // Validation: Max 6 players
    if($current_p >= 6) {
        $error = "Ang team na ito ay puno na (Maximum of 6 players only).";
    } elseif($photo_error) {
        $error = $photo_error;
    } else {
        // Validation: Duplicate Name check sa buong tournament
        $dup = $conn->query("SELECT id FROM players WHERE name='$name' AND tournament_id=$tournament_id");
        if($dup->num_rows > 0) {
            $error = "Ang pangalang '$name' ay registered na sa tournament na ito.";
        } else {
            $conn->query("INSERT INTO players (team_id, tournament_id, name, role, is_captain) VALUES ($team_id, $tournament_id, '$name', '$role', $captain)");
            $new_player_id = $conn->insert_id;

            $photo_path = save_player_photo_upload($_FILES['photo'] ?? [], $new_player_id);
            if($photo_path) {
                $safe_photo = mysqli_real_escape_string($conn, $photo_path);
                $conn->query("UPDATE players SET photo_path='$safe_photo' WHERE id=$new_player_id");
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
    <link href="https://googleapis.com" rel="stylesheet">
    <style>
        body { margin: 0; background: radial-gradient(circle at top, #0f172a, #020617); height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Segoe UI', sans-serif; color: #e2e8f0; }
        .card { background: rgba(15, 23, 42, 0.85); backdrop-filter: blur(12px); padding: 40px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.1); width: 100%; max-width: 400px; box-shadow: 0 25px 50px rgba(0,0,0,0.5); }
        .header { text-align: center; margin-bottom: 25px; }
        .header h2 { font-family: 'Rajdhani', sans-serif; font-size: 18px; color: #94a3b8; text-transform: uppercase; margin: 0; }
        .team-name { color: #38bdf8; font-family: 'Rajdhani', sans-serif; font-size: 28px; font-weight: 700; text-transform: uppercase; display: block; margin-top: 5px; text-shadow: 0 0 10px rgba(56,189,248,0.3); }
        .error-msg { background: rgba(248, 113, 113, 0.1); border: 1px solid #f87171; color: #f87171; padding: 10px; border-radius: 8px; font-size: 12px; text-align: center; margin-bottom: 15px; }
        input, select { width: 100%; padding: 12px; background: #020617; border: 1px solid rgba(255,255,255,0.1); color: #fff; border-radius: 8px; margin-top: 15px; box-sizing: border-box; }
        input[type="file"] { padding: 10px; font-size: 13px; color: #94a3b8; }
        .hint { color:#64748b; font-size:11px; margin-top:7px; line-height:1.4; }
        .btn { width: 100%; padding: 15px; background: linear-gradient(135deg, #38bdf8, #6366f1); border: none; border-radius: 8px; color: #fff; font-weight: 700; cursor: pointer; margin-top: 20px; text-transform: uppercase; }
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
            <input type="checkbox" name="captain" id="c" style="width:auto; margin:0;">
            <label for="c">Set as Team Captain?</label>
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
