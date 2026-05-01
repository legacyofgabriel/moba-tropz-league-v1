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
$res = $conn->query("SELECT * FROM teams WHERE id = $team_id AND tournament_id = $tournament_id");
$team = $res->fetch_assoc();

if(!$team) { die("Team not found or not part of this tournament."); }

if(isset($_POST['update'])){
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $short = mysqli_real_escape_string($conn, $_POST['short']);
    $logo_error = validate_team_logo_upload($_FILES['logo'] ?? []);

    if($logo_error) {
        $error = $logo_error;
    } else {
        $logo_path = save_team_logo_upload($_FILES['logo'] ?? [], $team_id);
        if($logo_path) {
            delete_team_logo_file($team['logo_path'] ?? null);
            $conn->query("UPDATE teams SET name='$name', short_name='$short', logo_path='$logo_path' WHERE id=$team_id");
        } else {
            $conn->query("UPDATE teams SET name='$name', short_name='$short' WHERE id=$team_id");
        }
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
    <link href="https://googleapis.com" rel="stylesheet">
    <style>
        body {
            margin: 0; padding: 0;
            font-family: 'Segoe UI', sans-serif;
            background: radial-gradient(circle at top, #0f172a, #020617);
            height: 100vh; display: flex; align-items: center; justify-content: center;
        }
        .card {
            width: 100%; max-width: 400px;
            background: rgba(15, 23, 42, 0.9);
            backdrop-filter: blur(12px);
            padding: 40px; border-radius: 20px;
            border: 1px solid rgba(255,255,255,0.1);
            box-shadow: 0 25px 50px rgba(0,0,0,0.5);
        }
        .header h2 { 
            font-family: 'Rajdhani', sans-serif; color: #fff; 
            text-align: center; margin: 0; text-transform: uppercase; letter-spacing: 2px;
        }
        .form-group { margin-top: 25px; }
        label { 
            color: #38bdf8; font-size: 11px; text-transform: uppercase; 
            font-weight: 700; display: block; margin-bottom: 8px; letter-spacing: 1px;
        }
        input { 
            width: 100%; padding: 12px; background: #020617; 
            border: 1px solid rgba(255,255,255,0.1); color: #fff; 
            border-radius: 8px; box-sizing: border-box; font-size: 15px;
        }
        input:focus { outline: none; border-color: #38bdf8; }
        .btn { 
            width: 100%; padding: 15px; 
            background: linear-gradient(135deg, #38bdf8, #6366f1); 
            border: none; border-radius: 8px; color: #fff; 
            font-weight: 700; cursor: pointer; margin-top: 25px;
            text-transform: uppercase; letter-spacing: 1px;
        }
        .cancel-link { 
            display: block; text-align: center; margin-top: 20px; 
            color: #94a3b8; text-decoration: none; font-size: 13px; 
        }
    </style>
</head>
<body>
<div class="card">
    <div class="header"><h2>Edit Team</h2></div>
    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>Team Logo</label>
            <input type="file" name="logo" accept="image/*">
        </div>
        <div class="form-group">
            <label>Team Full Name</label>
            <input type="text" name="name" value="<?= $team['name'] ?>" required autocomplete="off">
        </div>
        <div class="form-group">
            <label>Team Tag</label>
            <input type="text" name="short" value="<?= $team['short_name'] ?>" required autocomplete="off">
        </div>
        <button type="submit" name="update" class="btn">Update Team Info</button>
        <a href="teams.php" class="cancel-link">Cancel and Return</a>
    </form>
</div>
</body>
</html>
