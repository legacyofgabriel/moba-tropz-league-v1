<?php
include("../config/db.php");
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include("../auth/auth_check.php");

if(!isset($_SESSION['active_tournament'])){
    header("Location: ../dashboard/maindashboard.php");
    exit();
}

$match_id = intval($_GET['match_id']);
$tournament_id = intval($_SESSION['active_tournament']);
$res = $conn->query("SELECT m.*, t1.name as t1, t2.name as t2 FROM matches m JOIN teams t1 ON m.team1_id=t1.id JOIN teams t2 ON m.team2_id=t2.id WHERE m.id=$match_id");
$m = $res->fetch_assoc();

if(isset($_POST['save_stats'])){
    $highest_score = -999;
    $mvp_id = null;
    $has_real_data = false; // FLAG: Sinisiguro na may ininput na stats

    // Prepared statement for better performance and security
    $stmt = $conn->prepare("INSERT INTO player_match_stats 
        (match_id, player_id, tournament_id, hero_name, kills, deaths, assists, hero_damage, tf_participation, total_gold) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE hero_name=?, kills=?, deaths=?, assists=?, hero_damage=?, tf_participation=?, total_gold=?");

    // Loop through players of BOTH teams provided in the form
    foreach($_POST['p'] as $pid => $s){
        $pid = intval($pid);
        $hero = trim($s['hero']);
        $k = intval($s['k']); $d = max(0, intval($s['d'])); $a = intval($s['a']);
        $hd = intval($s['hd']); $tg = intval($s['tg']);
        $tf = floatval($s['tf']);

        // Check kung nag-input ba talaga
        if($k > 0 || $a > 0 || $hd > 0) { $has_real_data = true; }

        // Formula: Score = (K*2) + A - (D*1.5) + (HD/2000) + (TF*0.1)
        // Ang formula na ito ay tinitingnan ang lahat ng players mula sa dalawang team
        $current_p_score = ($k * 2) + $a - ($d * 1.5) + ($hd / 2000) + ($tf * 0.1);

        if($current_p_score > $highest_score){
            $highest_score = $current_p_score;
            $mvp_id = $pid;
        }

        $stmt->bind_param("iiisiiiddisiiidd", $match_id, $pid, $tournament_id, $hero, $k, $d, $a, $hd, $tf, $tg, $hero, $k, $d, $a, $hd, $tf, $tg);
        $stmt->execute();
    }
    // MAG-AASSIGN LANG NG MVP KUNG MAY REAL DATA NA NA-INPUT (HINDI PURO 0)
    if($has_real_data && $mvp_id){
        $conn->query("UPDATE matches SET mvp_player_id = $mvp_id WHERE id = $match_id");
    }

    header("Location: matches.php?msg=Statistics saved successfully!");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Input Match Stats</title>
    <link rel="stylesheet" href="../dashboard/maindashboard.css">
    <style>
        .in { width: 55px; background: rgba(0,0,0,0.5); border: 1px solid var(--border); color: #fff; font-size: 11px; padding: 6px; border-radius: 4px; text-align: center; }
    </style>
</head>
<body>
<div class="wrapper">
    <form method="POST">
    <div class="hero" style="padding:20px; text-align:center;"><h2 class="hero-title" style="font-size:20px;">Input Stats: <?= $m['t1'] ?> vs <?= $m['t2'] ?></h2></div>
    <?php foreach([$m['team1_id'] => $m['t1'], $m['team2_id'] => $m['t2']] as $tid => $name): ?>
        <div class="section-label" style="margin-top:30px; color:var(--gold);"><?= strtoupper($name) ?></div>
        <div class="nav-card" style="display:block; overflow-x:auto; padding:10px;">
            <table style="width:100%; border-collapse:collapse; text-align:center;">
                <thead><tr style="color:var(--cyan); font-size:10px;">
                    <th style="text-align:left; padding-left:10px;">Player</th><th style="text-align:left;">Role</th>
                    <th>Hero</th><th>K</th><th>D</th><th>A</th><th>H.Dmg</th><th>TF%</th><th>Gold</th>
                </tr></thead>
                <tbody>
                    <?php $ps = $conn->query("SELECT id, name, role FROM players WHERE team_id=$tid"); while($p = $ps->fetch_assoc()): ?>
                    <tr style="border-bottom:1px solid rgba(255,255,255,0.03);">
                        <td style="text-align:left; padding:12px 10px; color:#fff; font-weight:600;"><?= $p['name'] ?></td>
                        <td style="text-align:left; color:var(--gold); font-size:10px; font-weight:800;"><?= $p['role'] ?></td>
                        <td><input type="text" name="p[<?= $p['id'] ?>][hero]" class="in" style="width:90px;" required></td>
                        <td><input type="number" name="p[<?= $p['id'] ?>][k]" class="in" value="0"></td>
                        <td><input type="number" name="p[<?= $p['id'] ?>][d]" class="in" value="0"></td>
                        <td><input type="number" name="p[<?= $p['id'] ?>][a]" class="in" value="0"></td>
                        <td><input type="number" name="p[<?= $p['id'] ?>][hd]" class="in" value="0"></td>
                        <td><input type="number" step="0.1" name="p[<?= $p['id'] ?>][tf]" class="in" value="0"></td>
                        <td><input type="number" name="p[<?= $p['id'] ?>][tg]" class="in" value="0"></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php endforeach; ?>
    <button type="submit" name="save_stats" class="btn-logout" style="width:100%; margin-top:30px; height:50px; background:var(--cyan); color:#000; font-weight:bold; cursor:pointer;">SAVE STATISTICS & DECLARE MVP</button>
    </form>
</div>
</body>
</html>
