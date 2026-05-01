<?php
include("../config/db.php");
include("../auth/auth_check.php");

if (session_status() === PHP_SESSION_NONE) { session_start(); }

if(isset($_POST['create'])){
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $organizer = mysqli_real_escape_string($conn, $_POST['organizer']);
    $format = mysqli_real_escape_string($conn, $_POST['format']);
    $team_count = intval($_POST['team_count']);

    // Generate Tournament Code (TRM-2026-001)
    $year = date("Y");
    $res = $conn->query("SELECT COUNT(*) as total FROM tournaments");
    $row = $res->fetch_assoc();
    $num = str_pad($row['total'] + 1, 3, '0', STR_PAD_LEFT);
    $code = "TRM-$year-$num";

    $query = "INSERT INTO tournaments (tournament_code, name, organizer, format_type, team_count) 
              VALUES ('$code', '$name', '$organizer', '$format', '$team_count')";

    if($conn->query($query)){
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
    <!-- Gagamit tayo ng internal CSS para sigurado ang alignment gaya ng dashboard -->
    <link href="https://googleapis.com" rel="stylesheet">
    <style>
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
        /* MLBB Overlay Effect */
        body::before {
            content: "";
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: 
                repeating-linear-gradient(0deg, rgba(0,0,0,0.15) 0px, transparent 1px, transparent 2px),
                repeating-linear-gradient(90deg, rgba(56, 189, 248, 0.02) 0px, transparent 1px, transparent 40px);
            background-size: 100% 3px, 40px 100%;
            pointer-events: none;
            z-index: 0;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .container { width: 100%; max-width: 450px; padding: 20px; }
        .card {
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(56, 189, 248, 0.2);
            border-radius: 20px;
            padding: 40px 30px;
            animation: fadeIn 0.5s ease-out;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            position: relative;
            z-index: 1;
        }
        .header h2 { 
            font-family: 'Rajdhani', sans-serif; 
            font-size: 28px; 
            margin: 0 0 10px; 
            color: #fff; 
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .header p { font-size: 14px; color: #94a3b8; text-align: center; margin-bottom: 30px; }

        .form-group { margin-bottom: 20px; }
        label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 8px; color: #38bdf8; text-transform: uppercase; letter-spacing: 1px; }
        
        input, select {
            width: 100%;
            padding: 12px 15px;
            background: rgba(2, 6, 23, 0.6);
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 10px;
            color: #fff;
            font-size: 14px;
            box-sizing: border-box;
            transition: 0.3s;
        }
        input:focus, select:focus {
            outline: none;
            border-color: #38bdf8;
            box-shadow: 0 0 0 4px rgba(56, 189, 248, 0.1);
        }

        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #38bdf8, #6366f1);
            border: none;
            border-radius: 10px;
            color: #fff;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 10px;
            transition: 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(99, 102, 241, 0.3); }

        .cancel-btn {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #94a3b8;
            text-decoration: none;
            font-size: 13px;
            transition: 0.3s;
        }
        .cancel-btn:hover { color: #fff; }
    </style>
</head>
<body>

<div class="container">
    <div class="card">
        <div class="header">
            <h2>Create Tournament</h2>
            <p>Set up your tournament details below</p>
        </div>

        <form method="POST">
            <div class="form-group">
                <label>Tournament Name</label>
                <input type="text" name="name" placeholder="Enter tournament name" required autocomplete="off">
            </div>

            <div class="form-group">
                <label>Organizer</label>
                <input type="text" name="organizer" placeholder="Enter organizer name" required autocomplete="off">
            </div>

            <div class="form-group">
                <label>Tournament Format</label>
                <select name="format">
                    <option value="Round Robin">Round Robin</option>
                    <option value="Single Elimination">Single Elimination</option>
                    <option value="Double Elimination">Double Elimination</option>
                    <option value="Round Robin + Playoffs">Round Robin + Playoffs</option>
                </select>
            </div>

            <div class="form-group">
                <label>Number of Teams</label>
                <select name="team_count">
                    <option value="4">4 Teams</option>
                    <option value="6">6 Teams</option>
                    <option value="8">8 Teams</option>
                    <option value="16">16 Teams</option>
                    <option value="32">32 Teams</option>
                </select>
            </div>

            <button type="submit" name="create" class="btn">Confirm & Create</button>
        </form>

        <a href="../dashboard/maindashboard.php" class="cancel-btn">← Back to Dashboard</a>
    </div>
</div>

</body>
</html>