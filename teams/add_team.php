<?php
include("../config/db.php");
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include("../auth/auth_check.php");
include("../includes/player_photos.php");

ensure_team_logo_column($conn);

if(!isset($_SESSION['active_tournament'])){
    header("Location: ../dashboard/maindashboard.php");
    exit();
}

$tournament_id = intval($_SESSION['active_tournament']);
$error = "";

// Kunin ang limit at count para sa validation
$t_query = $conn->query("SELECT team_count FROM tournaments WHERE id = $tournament_id");
$t_data = $t_query->fetch_assoc();
$limit = intval($t_data['team_count']);

$c_query = $conn->query("SELECT COUNT(*) as current FROM teams WHERE tournament_id = $tournament_id");
$c_data = $c_query->fetch_assoc();
$current = intval($c_data['current']);

if(isset($_POST['add'])){
    if($current >= $limit) {
        $error = "Bawal na magdagdag. Puno na ang slots!";
    } else {
        $name = trim($_POST['name']);
        $short = trim($_POST['short']);
        $logo_error = validate_team_logo_upload($_FILES['logo'] ?? []);

        // Duplicate Check
        $stmt = $conn->prepare("SELECT id FROM teams WHERE (name=? OR short_name=?) AND tournament_id=?");
        $stmt->bind_param("ssi", $name, $short, $tournament_id);
        $stmt->execute();
        $check = $stmt->get_result();

        if($logo_error) {
            $error = $logo_error;
        } elseif($check->num_rows > 0){
            $error = "Ang Team Name o Tag ay ginagamit na!";
        } else {
            $stmt = $conn->prepare("INSERT INTO teams (tournament_id, name, short_name) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $tournament_id, $name, $short);
            $stmt->execute();
            $new_team_id = $conn->insert_id;

            $logo_path = save_team_logo_upload($_FILES['logo'] ?? [], $new_team_id);
            if($logo_path) {
                $stmt = $conn->prepare("UPDATE teams SET logo_path=? WHERE id=?");
                $stmt->bind_param("si", $logo_path, $new_team_id);
                $stmt->execute();
            }

            $stmt = $conn->prepare("INSERT INTO standings (tournament_id, team_id, played, wins, losses, points) VALUES (?, ?, 0, 0, 0, 0)");
            $stmt->bind_param("ii", $tournament_id, $new_team_id);
            $stmt->execute();

            header("Location: teams.php");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register Team — MOBA TROPZ</title>
    <link rel="stylesheet" href="../dashboard/maindashboard.css">
    <style>
        body { align-items: center; justify-content: center; min-height: 100vh; display: flex; }
        .auth-card { background: #000; border: 2px solid var(--border); border-radius: 0; padding: 40px; width: 100%; max-width: 440px; position: relative; box-shadow: 10px 10px 0 rgba(0, 242, 255, 0.1); }
        .auth-card::before { content: ""; position: absolute; top: -2px; left: -2px; width: 30px; height: 30px; border-top: 4px solid var(--cyan); border-left: 4px solid var(--cyan); }
        .form-group { margin-bottom: 20px; }
        input { width: 100%; padding: 12px 15px; background: #0a0a0c; border: 1px solid var(--border); border-radius: 0; color: var(--cyan); font-family: 'Space Grotesk', monospace; font-size: 15px; box-sizing: border-box; }
        input:focus { outline: none; border-color: var(--cyan); box-shadow: 0 0 15px var(--cyan-glow); }
        .btn-submit { width: 100%; padding: 16px; background: var(--cyan); border: none; color: #000; font-weight: 900; text-transform: uppercase; cursor: pointer; clip-path: polygon(0 0, 100% 0, 100% 70%, 90% 100%, 0 100%); }
    </style>
</head>
<body>
<div class="auth-card">
    <div class="section-head" style="flex-direction:column; align-items:center; text-align:center; margin-bottom:30px;">
        <div class="hero-label">Recruitment</div>
        <h2 style="font-family:'Rajdhani'; color:#fff; font-style:italic; font-weight:800; font-size:28px;">REGISTER TEAM</h2>
    </div>
    <?php if($error): ?>
        <div style="background: rgba(248,113,113,0.1); border:1px solid #f87171; color:#f87171; padding:12px; border-radius:0; font-size:13px; text-align:center; margin-bottom:20px;"><?= $error ?></div>
    <?php endif; ?>
    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label class="stat-label">Full Team Name</label>
            <input type="text" name="name" placeholder="E.g. Blacklist International" required>
        </div>
        <div class="form-group">
            <label class="stat-label">Team Tag (Short)</label>
            <input type="text" name="short" placeholder="E.g. BLCK" required>
        </div>
        <div class="form-group">
            <label class="stat-label">Team Logo</label>
            <input type="file" name="logo" accept="image/*" style="padding:10px; font-size:12px; color:var(--muted);">
        </div>
        <button type="submit" name="add" class="btn-submit">DEPLOY TEAM</button>
        <a href="teams.php" style="display:block; text-align:center; margin-top:20px; color:var(--muted); text-decoration:none; font-size:13px; text-transform:uppercase; letter-spacing:1px;">Cancel Mission</a>
    </form>
</div>
</body>
</html>
