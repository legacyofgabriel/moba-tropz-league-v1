<?php
include("../config/db.php");
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include("../auth/auth_check.php");
include("../includes/player_photos.php");

ensure_player_photo_column($conn);

if(!isset($_GET['id'])) {
    header("Location: teams.php");
    exit();
}

$player_id = intval($_GET['id']);
if(!isset($_SESSION['active_tournament'])){
    header("Location: ../dashboard/maindashboard.php");
    exit();
}

$tournament_id = intval($_SESSION['active_tournament']);
$error = "";

// 1. Kunin ang current info ng player
$res = $conn->query("SELECT p.*, t.name as team_name FROM players p JOIN teams t ON p.team_id = t.id WHERE p.id = $player_id");
$p = $res->fetch_assoc();

if(!$p) { die("Player not found."); }

if(isset($_POST['update'])){
    $new_name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $captain = isset($_POST['captain']) ? 1 : 0;
    $photo_error = validate_player_photo_upload($_FILES['photo'] ?? []);

    // 2. Duplicate Check (Kung pinalitan ang pangalan, check kung may katulad na sa tournament)
    $check = $conn->query("SELECT id FROM players WHERE name='$new_name' AND tournament_id=$tournament_id AND id != $player_id");
    
    if($photo_error) {
        $error = $photo_error;
    } elseif($check->num_rows > 0){
        $error = "Ang pangalang '$new_name' ay ginagamit na ng ibang player!";
    } else {
        $photo_path = save_player_photo_upload($_FILES['photo'] ?? [], $player_id);
        if($photo_path) {
            delete_player_photo_file($p['photo_path'] ?? null);
            $safe_photo = mysqli_real_escape_string($conn, $photo_path);
            $conn->query("UPDATE players SET name='$new_name', role='$role', is_captain=$captain, photo_path='$safe_photo' WHERE id=$player_id");
        } else {
            $conn->query("UPDATE players SET name='$new_name', role='$role', is_captain=$captain WHERE id=$player_id");
        }
        header("Location: teams.php?msg=Player updated successfully");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Player — MOBA TROPZ</title>
    <link href="https://googleapis.com" rel="stylesheet">
    <style>
        body { 
            margin: 0; 
            background: #020617;
            background-image: 
                linear-gradient(rgba(2, 6, 23, 0.75), rgba(2, 6, 23, 0.85)),
                url('https://images5.alphacoders.com/105/1059432.jpg'); /* Hero Selection Vibe */
            background-size: cover;
            background-position: center;
            height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Segoe UI', sans-serif; color: #e2e8f0; 
            position: relative; overflow: hidden;
        }
        body::before {
            content: "";
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: 
                repeating-linear-gradient(0deg, rgba(0,0,0,0.15) 0px, transparent 1px, transparent 2px),
                repeating-linear-gradient(90deg, rgba(56, 189, 248, 0.02) 0px, transparent 1px, transparent 40px);
            background-size: 100% 3px, 40px 100%;
            pointer-events: none;
        }
        .card { background: rgba(15, 23, 42, 0.85); backdrop-filter: blur(12px); padding: 40px; border-radius: 20px; border: 1px solid rgba(56, 189, 248, 0.2); width: 100%; max-width: 400px; box-shadow: 0 25px 50px rgba(0,0,0,0.5); position: relative; z-index: 1; }
        .header { text-align: center; margin-bottom: 25px; }
        .header h2 { font-family: 'Rajdhani', sans-serif; font-size: 18px; color: #94a3b8; text-transform: uppercase; margin: 0; }
        .team-name { color: #38bdf8; font-family: 'Rajdhani', sans-serif; font-size: 24px; font-weight: 700; text-transform: uppercase; display: block; margin-top: 5px; }
        .error-msg { background: rgba(248, 113, 113, 0.1); border: 1px solid #f87171; color: #f87171; padding: 10px; border-radius: 8px; font-size: 13px; text-align: center; margin-bottom: 20px; }
        .form-group { margin-top: 20px; }
        label { color: #38bdf8; font-size: 11px; text-transform: uppercase; font-weight: 700; display: block; margin-bottom: 8px; }
        input[type="text"], select { width: 100%; padding: 12px; background: #020617; border: 1px solid rgba(255,255,255,0.1); color: #fff; border-radius: 8px; box-sizing: border-box; }
        input[type="file"] { width: 100%; padding: 10px; background: #020617; border: 1px solid rgba(255,255,255,0.1); color: #94a3b8; border-radius: 8px; box-sizing: border-box; }
        .hint { color:#64748b; font-size:11px; margin-top:7px; line-height:1.4; }
        .checkbox-container { display: flex; align-items: center; gap: 10px; margin-top: 20px; cursor: pointer; }
        .btn { width: 100%; padding: 15px; background: linear-gradient(135deg, #38bdf8, #6366f1); border: none; border-radius: 8px; color: #fff; font-weight: 700; cursor: pointer; margin-top: 25px; text-transform: uppercase; }
    </style>
</head>
<body>
<div class="card">
    <div class="header">
        <h2>Edit Player Info</h2>
        <span class="team-name"><?= strtoupper($p['team_name']) ?></span>
    </div>
    
    <?php if($error): ?><div class="error-msg"><?= $error ?></div><?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>In-Game Name (IGN)</label>
            <input type="text" name="name" value="<?= $p['name'] ?>" required>
        </div>
        <div class="form-group">
            <label>Role</label>
            <select name="role" required>
                <option value="CORE" <?= ($p['role'] == 'CORE') ? 'selected' : '' ?>>CORE / JUNGLER</option>
                <option value="MID" <?= ($p['role'] == 'MID') ? 'selected' : '' ?>>MIDLANER</option>
                <option value="ROAM" <?= ($p['role'] == 'ROAM') ? 'selected' : '' ?>>ROAMER</option>
                <option value="GOLD" <?= ($p['role'] == 'GOLD') ? 'selected' : '' ?>>GOLD LANER</option>
                <option value="EXP" <?= ($p['role'] == 'EXP') ? 'selected' : '' ?>>EXP LANER</option>
                <option value="COACH" <?= ($p['role'] == 'COACH') ? 'selected' : '' ?>>COACH</option>
                <option value="SUB" <?= ($p['role'] == 'SUB') ? 'selected' : '' ?>>SUBSTITUTE</option>
            </select>
        </div>
        <div class="form-group">
            <label>Profile Photo</label>
            <label for="player-photo-upload" style="
                display: block; width: 100%; padding: 10px; background: #020617; 
                border: 1px solid rgba(255,255,255,0.1); color: #94a3b8; border-radius: 8px; 
                font-size: 12px; text-align: center; cursor: pointer; transition: all 0.3s;
            ">
                <span id="player-photo-filename">
                    <?= !empty($p['photo_path']) ? basename($p['photo_path']) : 'Upload New Photo (Optional)' ?>
                </span>
            </label>
            <input type="file" name="photo" id="player-photo-upload" accept="image/jpeg,image/png,image/webp" style="display: none;">
            <div class="hint">Upload a new photo to replace the current one. JPG, PNG, or WEBP. Max 2MB.</div>
        </div>
        
        <div class="checkbox-container">
            <input type="checkbox" name="captain" id="is_capt" style="width:18px; height:18px;" <?= ($p['is_captain'] == 1) ? 'checked' : '' ?>>
            <label for="is_capt" style="color:#fff; cursor:pointer; font-size:14px;">Team Captain?</label>
        </div>
        <button type="submit" name="update" class="btn">Update Player Info</button>
        <a href="teams.php" style="display:block; text-align:center; color:#94a3b8; margin-top:15px; text-decoration:none; font-size:13px;">Cancel</a>
    </form>
</div>
<script>
    document.getElementById('player-photo-upload').addEventListener('change', function() {
        const filenameSpan = document.getElementById('player-photo-filename');
        if (this.files && this.files.length > 0) {
            filenameSpan.textContent = this.files[0].name;
        } else {
            filenameSpan.textContent = 'Upload New Photo (Optional)';
        }
    });
</script>
</body>
</html>
