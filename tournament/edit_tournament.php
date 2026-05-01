<?php
include("../config/db.php");
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include("../auth/auth_check.php");

$id = intval($_GET['id']);
$res = $conn->query("SELECT * FROM tournaments WHERE id = $id");
$t = $res->fetch_assoc();

if(isset($_POST['update'])){
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $organizer = mysqli_real_escape_string($conn, $_POST['organizer']);
    $format = $_POST['format'];
    $team_count = intval($_POST['team_count']);

    $conn->query("UPDATE tournaments SET name='$name', organizer='$organizer', format_type='$format', team_count=$team_count WHERE id=$id");
    header("Location: ../dashboard/maindashboard.php?id=$id");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Settings — MOBA TROPZ</title>
    <link href="https://googleapis.com" rel="stylesheet">
    <style>
        body {
            margin: 0; padding: 0;
            background: radial-gradient(circle at top, #0f172a, #020617);
            height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Segoe UI', sans-serif;
        }
        .card {
            background: rgba(15, 23, 42, 0.85); backdrop-filter: blur(12px);
            padding: 40px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.1);
            width: 100%; max-width: 400px; box-shadow: 0 25px 50px rgba(0,0,0,0.5);
        }
        .header h2 { font-family: 'Rajdhani', sans-serif; color: #fff; text-align: center; margin: 0; text-transform: uppercase; }
        .form-group { margin-top: 20px; }
        label { color: #38bdf8; font-size: 11px; text-transform: uppercase; font-weight: 700; display: block; margin-bottom: 8px; }
        input, select { width: 100%; padding: 12px; background: #020617; border: 1px solid rgba(255,255,255,0.1); color: #fff; border-radius: 8px; box-sizing: border-box; }
        .btn { width: 100%; padding: 15px; background: linear-gradient(135deg, #38bdf8, #6366f1); border: none; border-radius: 8px; color: #fff; font-weight: 700; cursor: pointer; margin-top: 25px; }
    </style>
</head>
<body>
<div class="card">
    <div class="header"><h2>Edit Tournament</h2></div>
    <form method="POST">
        <div class="form-group"><label>Tournament Name</label><input type="text" name="name" value="<?= $t['name'] ?>" required></div>
        <div class="form-group"><label>Organizer</label><input type="text" name="organizer" value="<?= $t['organizer'] ?>" required></div>
        <div class="form-group">
            <label>Tournament Format</label>
            <select name="format">
                <?php foreach(['Round Robin', 'Single Elimination', 'Double Elimination', 'Round Robin + Playoffs'] as $format): ?>
                    <option value="<?= $format ?>" <?= $t['format_type'] === $format ? 'selected' : '' ?>><?= $format ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Max Slots</label>
            <select name="team_count">
                <option value="4" <?= $t['team_count'] == 4 ? 'selected' : '' ?>>4 Teams</option>
                <option value="6" <?= $t['team_count'] == 6 ? 'selected' : '' ?>>6 Teams</option>
                <option value="8" <?= $t['team_count'] == 8 ? 'selected' : '' ?>>8 Teams</option>
                <option value="16" <?= $t['team_count'] == 16 ? 'selected' : '' ?>>16 Teams</option>
                <option value="32" <?= $t['team_count'] == 32 ? 'selected' : '' ?>>32 Teams</option>
            </select>
        </div>
        <button type="submit" name="update" class="btn">Save Settings</button>
        <a href="../dashboard/maindashboard.php" style="display:block; text-align:center; color:#94a3b8; margin-top:15px; text-decoration:none; font-size:13px;">Cancel</a>
    </form>
</div>
</body>
</html>
