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
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $short = mysqli_real_escape_string($conn, $_POST['short']);
        $logo_error = validate_team_logo_upload($_FILES['logo'] ?? []);

        // Duplicate Check
        $check = $conn->query("SELECT id FROM teams WHERE (name='$name' OR short_name='$short') AND tournament_id=$tournament_id");

        if($logo_error) {
            $error = $logo_error;
        } elseif($check->num_rows > 0){
            $error = "Ang Team Name o Tag ay ginagamit na!";
        } else {
            // A. INSERT TEAM
            $conn->query("INSERT INTO teams (tournament_id, name, short_name) 
                          VALUES ($tournament_id, '$name', '$short')");
            $new_team_id = $conn->insert_id;

            $logo_path = save_team_logo_upload($_FILES['logo'] ?? [], $new_team_id);
            if($logo_path) {
                $conn->query("UPDATE teams SET logo_path='$logo_path' WHERE id=$new_team_id");
            }

            // B. AUTOMATIC STANDING ENTRY
            $conn->query("INSERT INTO standings (tournament_id, team_id, played, wins, losses, points) VALUES ($tournament_id, $new_team_id, 0, 0, 0, 0)");

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
    <link rel="stylesheet" href="../tournament/create.css">
</head>
<body style="background: radial-gradient(circle at top, #0f172a, #020617); display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0;">

<div class="card" style="width: 380px; background: rgba(15, 23, 42, 0.9); backdrop-filter: blur(10px); padding: 40px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.1);">
    <h2 style="font-family: 'Rajdhani'; color: #fff; text-align: center; margin: 0 0 20px 0;">REGISTER TEAM</h2>
    
    <?php if($error): ?>
        <div style="background: rgba(248,113,113,0.1); border:1px solid #f87171; color:#f87171; padding:10px; border-radius:8px; font-size:13px; text-align:center; margin-bottom:20px;"><?= $error ?></div>
    <?php endif; ?>

    <style>
        /* Existing styles for add_team.php */
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', sans-serif;
            background: #020617;
            background-image: 
                linear-gradient(rgba(2, 6, 23, 0.7), rgba(2, 6, 23, 0.8)),
                url('https://images2.alphacoders.com/105/1059431.jpg'); /* MLBB Map Scenery */
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #e2e8f0;
            position: relative;
            overflow: hidden;
        }
        .maker-preview { width: 120px; height: 120px; background: rgba(2, 6, 23, 0.7); border-radius: 15px; margin: 0 auto 20px; border: 2px dashed rgba(56,189,248,0.3); display: flex; align-items: center; justify-content: center; overflow: hidden; position: relative; }
        .maker-preview img { width: 100%; height: 100%; object-fit: contain; position: absolute; top: 0; left: 0; }
        .maker-preview::before { content: "PREVIEW"; position: absolute; font-size: 10px; color: rgba(255,255,255,0.3); font-weight: bold; }
        .maker-controls { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 20px; }
        .maker-controls label { font-size: 10px; opacity: 0.6; color: #94a3b8; text-transform: uppercase; margin-bottom: 5px; display: block; }
        .maker-controls input[type="color"] { width: 100%; height: 40px; padding: 2px; border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; background: #020617; }
        .maker-controls select { width: 100%; padding: 8px; height: 40px; border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; background: #020617; color: #fff; font-size: 12px; }
    </style>

    <form method="POST" enctype="multipart/form-data">
        <div class="maker-section" style="background: rgba(2,6,23,0.5); padding: 20px; border-radius: 12px; margin-bottom: 20px; border: 1px solid rgba(255,255,255,0.05);">
            <div class="maker-preview" id="logoPreview"></div>
            <div class="maker-controls">
                <div>
                    <label>BG COLOR</label>
                    <input type="color" id="makerColor" value="#38bdf8">
                </div>
                <div>
                    <label>ICON</label>
                    <select id="makerIcon">
                        <option value="shield">SHIELD</option>
                        <option value="star">STAR</option>
                        <option value="none">NONE</option>
                    </select>
                </div>
            </div>
            <input type="hidden" name="logo_config" id="logoConfig">
        </div>

        <div class="form-group">
            <label style="color:var(--cyan); font-size:11px; text-transform:uppercase;">Team Name</label>
            <input type="text" name="name" placeholder="Full Team Name" required style="width:100%; padding:12px; background:#020617; border:1px solid rgba(255,255,255,0.1); color:#fff; border-radius:8px;">
        </div>
        <div class="form-group" style="margin-top:15px;">
            <label style="color:var(--cyan); font-size:11px; text-transform:uppercase;">Team Logo</label>
            <input type="file" name="logo" accept="image/*" style="width:100%; padding:10px; background:#020617; border:1px solid rgba(255,255,255,0.1); color:#94a3b8; border-radius:8px; font-size:12px;">
        </div>
        <div class="form-group" style="margin-top:15px;">
            <label style="color:var(--cyan); font-size:11px; text-transform:uppercase;">Team Tag</label>
            <input type="text" name="short" placeholder="e.g. BLCK" required style="width:100%; padding:12px; background:#020617; border:1px solid rgba(255,255,255,0.1); color:#fff; border-radius:8px;">
        </div>
        <button type="submit" name="add" class="btn" style="width:100%; padding:15px; background:linear-gradient(135deg, #38bdf8, #6366f1); border:none; border-radius:8px; color:#fff; font-weight:700; margin-top:25px; cursor:pointer;">CONFIRM REGISTRATION</button>
        <a href="teams.php" style="display:block; text-align:center; margin-top:20px; color:#94a3b8; text-decoration:none; font-size:13px;">Cancel</a>
    </form>
</div>
</body>
</html>
