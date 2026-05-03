<?php
include("../config/db.php");
include("../auth/auth_check.php");
include("../includes/header.php");

if (session_status() === PHP_SESSION_NONE) { session_start(); }

if(isset($_POST['create'])){
    $name = trim($_POST['name']);
    $organizer = trim($_POST['organizer']);
    $format = $_POST['format'];
    $team_count = intval($_POST['team_count']);

    // Generate Tournament Code (TRM-2026-001)
    $year = date("Y");
    $res = $conn->query("SELECT COUNT(*) as total FROM tournaments");
    $row = $res->fetch_assoc();
    $num = str_pad($row['total'] + 1, 3, '0', STR_PAD_LEFT);
    $code = "TRM-$year-$num";

    $stmt = $conn->prepare("INSERT INTO tournaments (tournament_code, name, organizer, format_type, team_count) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssi", $code, $name, $organizer, $format, $team_count);

    if($stmt->execute()){
        // WELL-CONNECTED LOGIC:
        // Kunin ang ID ng kakagawa lang na tournament
        $new_tournament_id = $conn->insert_id; 
        
        // I-set sa session para alam ng Teams at Matches ang gagawin
        $_SESSION['active_tournament'] = $new_tournament_id; 
        
        // I-redirect sa dashboard dala ang bagong ID para mag-sync ang selector
        header("Location: ../dashboard/maindashboard.php?id=" . $new_tournament_id);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Tournament — MOBA TROPZ</title>
    <link rel="stylesheet" href="../dashboard/maindashboard.css">
    <style>
        body { 
            align-items: center; 
            justify-content: center; 
            min-height: 100vh; 
            display: flex;
        }
        .auth-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 40px;
            max-width: 480px;
            width: 100%;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
        }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; color: var(--cyan); font-weight: 700; font-size: 11px; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 1px; }
        .form-control {
            width: 100%;
            background: rgba(2, 6, 23, 0.5);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 12px 16px;
            color: #fff;
            font-family: 'Inter', sans-serif;
        }
        .form-control:focus { outline: none; border-color: var(--cyan); box-shadow: 0 0 0 1px var(--cyan); }
    </style>
</head>
<body>
<div class="wrapper" style="display:flex; align-items:center; justify-content:center;">
    <div class="auth-card">
        <div class="section-head" style="flex-direction:column; align-items:center; text-align:center; margin-bottom:30px;">
            <div class="hero-label">Tournament Setup</div>
            <h1 class="hero-title" style="font-size:32px;">New League</h1>
        </div>

        <form method="POST">
            <div class="form-group">
                <label>Name</label>
                <input type="text" name="name" class="form-control" placeholder="E.g. Tropz Invitational" required>
            </div>

            <div class="form-group">
                <label>Organizer</label>
                <input type="text" name="organizer" class="form-control" placeholder="Your Name/Org" required>
            </div>

            <div class="form-group">
                <label>Format</label>
                <select name="format" class="form-control">
                    <option value="Round Robin">Round Robin</option>
                    <option value="Single Elimination">Single Elimination</option>
                    <option value="Double Elimination">Double Elimination</option>
                    <option value="Round Robin + Playoffs">Round Robin + Playoffs</option>
                </select>
            </div>

            <div class="form-group">
                <label>Team Slots</label>
                <select name="team_count" class="form-control">
                    <option value="4">4 Teams</option>
                    <option value="6">6 Teams</option>
                    <option value="8">8 Teams</option>
                    <option value="16">16 Teams</option>
                    <option value="32">32 Teams</option>
                </select>
            </div>

            <button type="submit" name="create" class="app-action primary" style="width:100%; height:50px; font-size:14px; border:none; cursor:pointer;">Initialize Tournament</button>
        </form>

        <a href="../dashboard/maindashboard.php" class="app-action" style="width:100%; margin-top:12px; border:none;">Cancel & Return</a>
    </div>
</div>
</body>
</html>