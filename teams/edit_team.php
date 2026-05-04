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
$error = "";

// Kunin ang kasalukuyang data ng team
$stmt = $conn->prepare("SELECT t.*, s.wins, s.losses, s.played, s.points FROM teams t LEFT JOIN standings s ON t.id = s.team_id WHERE t.id = ? AND t.tournament_id = ?");
$stmt->bind_param("ii", $team_id, $tournament_id);
$stmt->execute();
$res = $stmt->get_result();
$team = $res->fetch_assoc();

if(!$team) { die("Team not found or not part of this tournament."); }

if(isset($_POST['update'])){
    $name = trim($_POST['name']);
    $short = trim($_POST['short']);
    $logo_file = $_FILES['logo'] ?? [];
    $logo_error = validate_team_logo_upload($logo_file);

    if($logo_error) {
        $error = $logo_error;
    } else {
        $logo_path = save_team_logo_upload($logo_file, $team_id);
        
        if ($logo_path) {
            delete_team_logo_file($team['logo_path'] ?? null);
            $up = $conn->prepare("UPDATE teams SET name = ?, short_name = ?, logo_path = ? WHERE id = ?");
            $up->bind_param("sssi", $name, $short, $logo_path, $team_id);
        } else {
            $up = $conn->prepare("UPDATE teams SET name = ?, short_name = ? WHERE id = ?");
            $up->bind_param("ssi", $name, $short, $team_id);
        }

        if($up->execute()) {
            log_tactical_action($conn, $_SESSION['user_id'], $tournament_id, "REFACTOR", "Updated squad parameters for " . strtoupper($name));
            header("Location: teams.php?msg=Squad transmission updated.");
            exit();
        } else {
            $error = "Critical database error. Transmission failed.";
        }
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
        body { align-items: center; justify-content: center; min-height: 100vh; display: flex; padding: 20px; }
        .refactor-layout { display: grid; grid-template-columns: 320px 480px; gap: 30px; align-items: stretch; }
        .auth-card { background: #000; border: 2px solid var(--border); padding: 40px; position: relative; box-shadow: 10px 10px 0 rgba(0, 242, 255, 0.1); }
        .auth-card::before { content: ""; position: absolute; top: -2px; left: -2px; width: 30px; height: 30px; border-top: 4px solid var(--cyan); border-left: 4px solid var(--cyan); }
        .intel-panel { background: rgba(2, 6, 23, 0.8); border: 1px solid var(--border); padding: 30px; display: flex; flex-direction: column; align-items: center; text-align: center; }
        .logo-preview-box { width: 100%; aspect-ratio: 1; background: #05070a; border: 1px dashed var(--cyan); display: flex; align-items: center; justify-content: center; overflow: hidden; margin-bottom: 20px; position: relative; }
        .logo-preview-box img { width: 100%; height: 100%; object-fit: contain; }
        .error-msg { background: rgba(248, 113, 113, 0.1); border: 1px solid var(--danger); color: var(--danger); padding: 12px; font-size: 13px; text-align: center; margin-bottom: 20px; }
        .form-group { margin-bottom: 20px; }
        input[type="text"] { width: 100%; padding: 14px 18px; background: #0a0a0c; border: 1px solid var(--border); color: #fff; font-family: 'Space Grotesk', monospace; font-size: 16px; box-sizing: border-box; }
        input:focus { outline: none; border-color: var(--cyan); box-shadow: 0 0 15px var(--cyan-glow); }
        .btn-submit { width: 100%; padding: 16px; background: var(--cyan); border: none; color: #000; font-weight: 900; text-transform: uppercase; cursor: pointer; clip-path: polygon(0 0, 100% 0, 100% 70%, 90% 100%, 0 100%); }
        @media (max-width: 850px) { .refactor-layout { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div class="refactor-layout">
    <div class="intel-panel">
        <div class="hero-label">CURRENT_ASSET</div>
        <div class="logo-preview-box" id="logoPreview">
            <img src="<?= team_logo_src($team['logo_path'], '../') ?>" alt="Logo">
        </div>
        <div style="text-align:left; width:100%;">
            <div class="stat-label">SQUAD DATA</div>
            <div style="font-size:14px; font-weight:700; color:#fff; margin-bottom:15px;"><?= $team['short_name'] ?> UNIT</div>
            <div class="stat-label">COMBAT SUCCESS</div>
            <div style="font-size:20px; font-family:'Rajdhani'; color:var(--cyan);"><?= $team['wins'] ?? 0 ?> WINS</div>
        </div>
    </div>

    <div class="auth-card">
    <div class="section-head" style="flex-direction:column; align-items:center; text-align:center; margin-bottom:30px;">
        <div class="hero-label">Tactical Adjustment</div>
        <h2 style="font-family:'Rajdhani'; color:#fff; font-style:italic; font-weight:800; font-size:28px;">EDIT TEAM</h2>
    </div>
    <?php if($error): ?><div class="error-msg"><?= $error ?></div><?php endif; ?>
    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label class="stat-label">Team Logo</label>
            <label for="team-logo-upload" style="
                display: block; width: 100%; padding: 16px; background: #05070a; 
                border: 1px solid var(--border); color: #94a3b8; 
                font-size: 11px; font-weight: 800; text-align: center; cursor: pointer; transition: all 0.3s;
            ">
                <span id="team-logo-filename">REPLACE VISUAL ASSET (PNG/JPG)</span>
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
</div>

<script>
    document.getElementById('team-logo-upload').addEventListener('change', function() {
        if (this.files && this.files.length > 0) {
            document.getElementById('team-logo-filename').textContent = this.files[0].name;
            
            const reader = new FileReader();
            reader.onload = function(e) {
                document.querySelector('#logoPreview img').src = e.target.result;
                document.getElementById('logoPreview').style.borderColor = 'var(--gold)';
            };
            reader.readAsDataURL(this.files[0]);
        } else {
            document.getElementById('team-logo-filename').textContent = 'REPLACE VISUAL ASSET (PNG/JPG)';
        }
    });
</script>
</body>
</html>
