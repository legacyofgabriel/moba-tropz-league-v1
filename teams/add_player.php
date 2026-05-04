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
    $bulk_input = trim($_POST['bulk_names'] ?? '');
    $role = $_POST['role'];
    $captain = isset($_POST['captain']) ? 1 : 0;
    $photo_error = validate_player_photo_upload($_FILES['photo'] ?? []);

    // Split bulk input by commas or newlines
    $names = preg_split('/[,\n\r]+/', $bulk_input, -1, PREG_SPLIT_NO_EMPTY);
    $names = array_map('trim', $names);

    // Validation: Max 6 players
    if(empty($names)) {
        $error = "Please provide at least one player name.";
    } elseif($current_p + count($names) > 6) {
        $error = "Registration limit exceeded. Team can only accept " . (6 - $current_p) . " more players.";
    } elseif($photo_error) {
        $error = $photo_error;
    } else {
        $conn->begin_transaction();
        try {
            $check_stmt = $conn->prepare("SELECT id FROM players WHERE name = ? AND tournament_id = ?");
            $insert_stmt = $conn->prepare("INSERT INTO players (team_id, tournament_id, name, role, is_captain) VALUES (?, ?, ?, ?, ?)");
            
            foreach ($names as $idx => $player_name) {
                // Check duplicate
                $check_stmt->bind_param("si", $player_name, $tournament_id);
                $check_stmt->execute();
                if ($check_stmt->get_result()->num_rows > 0) {
                    throw new Exception("Conflict: Player '$player_name' already exists in this tournament.");
                }

                // Set captain status (only first player if checked)
                $is_cap = ($idx === 0 && $captain) ? 1 : 0;
                $insert_stmt->bind_param("iissi", $team_id, $tournament_id, $player_name, $role, $is_cap);
                $insert_stmt->execute();
                
                $new_id = $conn->insert_id;
                if ($idx === 0 && isset($_FILES['photo']) && $_FILES['photo']['size'] > 0) {
                    $photo_path = save_player_photo_upload($_FILES['photo'], $new_id);
                    if($photo_path) {
                        $up_stmt = $conn->prepare("UPDATE players SET photo_path = ? WHERE id = ?");
                        $up_stmt->bind_param("si", $photo_path, $new_id);
                        $up_stmt->execute();
                    }
                }
            }
            $conn->commit();
            header("Location: ../teams/teams.php");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $error = $e->getMessage();
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
        .auth-card { background: #000; border: 2px solid var(--border); border-radius: 0; padding: 40px; width: 100%; max-width: 460px; position: relative; box-shadow: 10px 10px 0 rgba(0, 242, 255, 0.1); }
        .auth-card::before { content: ""; position: absolute; top: -2px; left: -2px; width: 30px; height: 30px; border-top: 4px solid var(--cyan); border-left: 4px solid var(--cyan); }
        .form-group { margin-bottom: 20px; }
        input, textarea, select { width: 100%; padding: 12px 15px; background: #0a0a0c; border: 1px solid var(--border); border-radius: 0; color: var(--cyan); font-family: 'Space Grotesk', monospace; font-size: 15px; box-sizing: border-box; }
        input:focus, textarea:focus, select:focus { outline: none; border-color: var(--cyan); box-shadow: 0 0 15px var(--cyan-glow); }
        .error-msg { background: rgba(248, 113, 113, 0.1); border: 1px solid #f87171; color: #f87171; padding: 12px; border-radius: 0; font-size: 13px; text-align: center; margin-bottom: 20px; }
        .btn-submit { 
            width: 100%; padding: 16px; background: var(--cyan); border: none; 
            color: #000; font-weight: 900; text-transform: uppercase; cursor: pointer; margin-top: 10px;
            clip-path: polygon(0 0, 100% 0, 100% 70%, 90% 100%, 0 100%);
        }
    </style>
</head>
<body>
<div class="auth-card">
    <div class="section-head" style="flex-direction:column; align-items:center; text-align:center; margin-bottom:25px;">
        <div class="hero-label">ROSTER DEPLOYMENT</div>
        <h2 style="font-family:'Rajdhani'; color:#fff; font-style:italic; font-weight:800; font-size:28px; text-transform:uppercase;"><?= htmlspecialchars($team_data['name']) ?></h2>
    </div>

    <?php if($error): ?><div class="error-msg"><?= $error ?></div><?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label class="stat-label">In-Game Names (Bulk via Comma/Newline)</label>
            <textarea name="bulk_names" placeholder="[SYSTEM_WAITING_FOR_INPUT]&#10;> Enter Roster List..." required style="height:120px; margin-top:8px; background:#050507; border:1px solid var(--cyan-glow); color:var(--cyan); font-family:'Courier New', monospace; font-size:13px; padding:15px;"></textarea>
        </div>
        
        <div class="form-group">
            <label class="stat-label">Primary Role (Batch Assignment)</label>
            <select name="role" required style="margin-top:8px;">
                <option value="CORE">CORE / JUNGLER</option>
                <option value="MID">MIDLANER</option>
                <option value="ROAM">ROAMER</option>
                <option value="GOLD">GOLD LANER</option>
                <option value="EXP">EXP LANER</option>
                <option value="COACH">COACH</option>
                <option value="SUB">SUBSTITUTE</option>
            </select>
        </div>

        <label for="player-photo-upload" style="
            display: block; width: 100%; padding: 10px; background: #0a0a0c; 
            border: 1px solid rgba(255,255,255,0.1); color: #94a3b8; border-radius: 8px; 
            font-size: 12px; text-align: center; cursor: pointer; transition: all 0.3s;
        ">
            <span id="player-photo-filename">Upload Player Photo (Optional)</span>
        </label>
        <input type="file" name="photo" id="player-photo-upload" accept="image/jpeg,image/png,image/webp" style="display: none;">
        <div class="hint" style="margin-top: 7px;">Optional profile photo. JPG, PNG, or WEBP only. Max 2MB.</div>
        <div style="display:flex; align-items:center; gap:10px; margin-top:15px; font-size:14px;">
            <input type="checkbox" name="captain" id="c" style="width:20px; height:20px; accent-color:var(--cyan); cursor:pointer; margin:0;">
            <label for="c" style="cursor:pointer; color:var(--text); font-weight:600;">Set as Team Captain?</label>
        </div>
        <button type="submit" name="add" class="btn-submit">CONFIRM DEPLOYMENT</button>
        <a href="../teams/teams.php" style="display:block; text-align:center; color:var(--muted); margin-top:20px; text-decoration:none; font-size:12px; text-transform:uppercase; letter-spacing:1px;">Cancel Adjustment</a>
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
