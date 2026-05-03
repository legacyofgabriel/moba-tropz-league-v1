<?php
include("../config/db.php");
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include("../auth/auth_check.php");
include("../includes/player_photos.php");

if(!isset($_GET['id'])) {
    header("Location: teams.php");
    exit();
}

$team_id = intval($_GET['id']);
if(!isset($_SESSION['active_tournament'])){
    header("Location: ../dashboard/maindashboard.php");
    exit();
}

$tournament_id = intval($_SESSION['active_tournament']);

// Kunin ang kasalukuyang data ng team
$stmt = $conn->prepare("SELECT * FROM teams WHERE id = ? AND tournament_id = ?");
$stmt->bind_param("ii", $team_id, $tournament_id);
$stmt->execute();
$res = $stmt->get_result();
$team = $res->fetch_assoc();

if(!$team) { die("Team not found or not part of this tournament."); }

if(isset($_POST['update'])){
    $name = trim($_POST['name']);
    $short = trim($_POST['short']);
    $logo_error = validate_team_logo_upload($_FILES['logo'] ?? []);

    if($logo_error) {
        $error = $logo_error;
    } else {
        $logo_path = save_team_logo_upload($_FILES['logo'] ?? [], $team_id);
        if($logo_path) {
            delete_team_logo_file($team['logo_path'] ?? null);
            $stmt = $conn->prepare("UPDATE teams SET name = ?, short_name = ?, logo_path = ? WHERE id = ?");
            $stmt->bind_param("sssi", $name, $short, $logo_path, $team_id);
        } else {
            $stmt = $conn->prepare("UPDATE teams SET name = ?, short_name = ? WHERE id = ?");
            $stmt->bind_param("ssi", $name, $short, $team_id);
        }
        $stmt->execute();
        header("Location: teams.php?msg=Team updated successfully");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Team — MOBA TROPZ</title>
    <link rel="stylesheet" href="../dashboard/maindashboard.css">
    <style>
        body { align-items: center; justify-content: center; min-height: 100vh; display: flex; }
        .auth-card { background: #000; border: 2px solid var(--border); padding: 40px; width: 100%; max-width: 440px; position: relative; box-shadow: 10px 10px 0 rgba(0, 242, 255, 0.1); }
        .auth-card::before { content: ""; position: absolute; top: -2px; left: -2px; width: 30px; height: 30px; border-top: 4px solid var(--cyan); border-left: 4px solid var(--cyan); }
        .form-group { margin-bottom: 20px; }
        input { width: 100%; padding: 12px 15px; background: #0a0a0c; border: 1px solid var(--border); color: var(--cyan); font-family: 'Space Grotesk', monospace; box-sizing: border-box; }
        input:focus { outline: none; border-color: var(--cyan); box-shadow: 0 0 15px var(--cyan-glow); }
        .btn-submit { width: 100%; padding: 16px; background: var(--cyan); border: none; color: #000; font-weight: 900; text-transform: uppercase; cursor: pointer; clip-path: polygon(0 0, 100% 0, 100% 70%, 90% 100%, 0 100%); }
    </style>
</head>
<body>
<div class="auth-card">
    <div class="section-head" style="flex-direction:column; align-items:center; text-align:center; margin-bottom:30px;">
        <div class="hero-label">Tactical Adjustment</div>
        <h2 style="font-family:'Rajdhani'; color:#fff; font-style:italic; font-weight:800; font-size:28px;">EDIT TEAM</h2>
    </div>
    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label class="stat-label">Team Logo</label>
            <label for="team-logo-upload" style="
                display: block; width: 100%; padding: 10px; background: #0a0a0c; 
                border: 1px solid rgba(255,255,255,0.1); color: #94a3b8; border-radius: 8px; 
                font-size: 12px; text-align: center; cursor: pointer; transition: all 0.3s;
            ">
                <span id="team-logo-filename">
                    <?= !empty($team['logo_path']) ? basename($team['logo_path']) : 'Upload New Logo (Optional)' ?>
                </span>
            </label>
            <input type="file" name="logo" id="team-logo-upload" accept="image/*" style="display: none;">
        </div>
        <div class="form-group">
            <label class="stat-label">Team Full Name</label>
            <input type="text" name="name" value="<?= $team['name'] ?>" required autocomplete="off">
        </div>
        <div class="form-group">
            <label class="stat-label">Team Tag</label>
            <input type="text" name="short" value="<?= $team['short_name'] ?>" required autocomplete="off">
        </div>
        <button type="submit" name="update" class="btn-submit">Update Team Info</button>
        <a href="teams.php" style="display:block; text-align:center; margin-top:20px; color:var(--muted); text-decoration:none; font-size:13px; text-transform:uppercase; letter-spacing:1px;">Cancel Adjustment</a>
    </form>
</div>
<script>
    document.getElementById('team-logo-upload').addEventListener('change', function() {
        const filenameSpan = document.getElementById('team-logo-filename');
        if (this.files && this.files.length > 0) {
            filenameSpan.textContent = this.files[0].name;
        } else {
            filenameSpan.textContent = 'Upload New Logo (Optional)';
        }
    });
</script>
</body>
</html>
