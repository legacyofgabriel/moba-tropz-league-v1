<?php
include("../config/db.php");
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include("../auth/auth_check.php");
include("../includes/header.php");
include("../includes/team_photos.php"); // For team_logo_src function
include("../includes/footer.php");

if(!isset($_SESSION['active_tournament'])){
    header("Location: ../dashboard/maindashboard.php");
    exit();
}

$tournament_id = intval($_SESSION['active_tournament']);

// Fetch Standings with aggregated KDA intel
$stmt = $conn->prepare("
    SELECT s.*, t.name, t.short_name, t.logo_path,
    (SELECT SUM(pms.kills) FROM player_match_stats pms JOIN players p ON pms.player_id = p.id WHERE pms.tournament_id = ? AND p.team_id = t.id) as t_kills,
    (SELECT SUM(pms.deaths) FROM player_match_stats pms JOIN players p ON pms.player_id = p.id WHERE pms.tournament_id = ? AND p.team_id = t.id) as t_deaths,
    (SELECT SUM(pms.assists) FROM player_match_stats pms JOIN players p ON pms.player_id = p.id WHERE pms.tournament_id = ? AND p.team_id = t.id) as t_assists,
    (SELECT SUM(pms.hero_damage) FROM player_match_stats pms JOIN players p ON pms.player_id = p.id WHERE pms.tournament_id = ? AND p.team_id = t.id) as t_damage_total
    FROM standings s
    JOIN teams t ON s.team_id = t.id
    WHERE s.tournament_id = ?
    ORDER BY s.points DESC, s.wins DESC, (t_kills + t_assists) DESC, t_damage_total DESC
");
$stmt->bind_param("iiiii", $tournament_id, $tournament_id, $tournament_id, $tournament_id, $tournament_id);
$stmt->execute();
$standings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Helper function to get team form (last 3 matches)
function get_team_form($conn, $tid, $tournament_id) {
    $stmt = $conn->prepare("
        SELECT winner_name, (SELECT name FROM teams WHERE id = ?) as my_name
        FROM matches 
        WHERE tournament_id = ? AND is_locked = 1 AND (team1_id = ? OR team2_id = ?) 
        ORDER BY id DESC LIMIT 3
    ");
    $stmt->bind_param("iiii", $tid, $tournament_id, $tid, $tid);
    $stmt->execute();
    $res = $stmt->get_result();
    $form = [];
    while($row = $res->fetch_assoc()) {
        $form[] = ($row['winner_name'] == $row['my_name']) ? 'W' : 'L';
    }
    return array_reverse($form);
}

// Feature: Squad Momentum (Win Streak)
function get_win_streak($conn, $tid, $tournament_id) {
    $stmt = $conn->prepare("
        SELECT winner_name, (SELECT name FROM teams WHERE id = ?) as my_name
        FROM matches 
        WHERE tournament_id = ? AND is_locked = 1 AND (team1_id = ? OR team2_id = ?) 
        ORDER BY id DESC
    ");
    $stmt->bind_param("iiii", $tid, $tournament_id, $tid, $tid);
    $stmt->execute();
    $res = $stmt->get_result();
    $streak = 0;
    while($row = $res->fetch_assoc()) {
        if ($row['winner_name'] == $row['my_name']) $streak++; else break;
    }
    return $streak;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>League Standings — MOBA TROPZ</title>
    <link rel="stylesheet" href="../dashboard/maindashboard.css">
    <style>
        .form-pill {
            display: inline-flex; justify-content: center; align-items: center;
            width: 22px; height: 22px; border-radius: 4px; font-size: 10px; font-weight: 900;
            margin-right: 4px;
        }
        .form-w { background: var(--cyan); color: #000; }
        .form-l { background: var(--danger); color: #fff; }
        .rank-glow { font-weight: 900; color: var(--gold); text-shadow: 0 0 10px var(--gold-glow); }
    </style>
</head>
<body>
<?php render_app_header('standings'); ?>

<div class="wrapper">
    <div class="hero" style="padding: 40px; text-align: center;">
        <div class="hero-label">Tactical Standings</div>
        <h1 class="hero-title" style="font-size: 42px;">LEAGUE COMMAND CENTER</h1>
    </div>

    <div class="table-shell">
        <table class="tournament-table">
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Squad</th>
                    <th>MP</th>
                    <th>W</th>
                    <th>L</th>
                    <th>PTS</th>
                    <th>Net KDA</th>
                    <th>Streak</th>
                    <th>Gold/Match</th>
                    <th>Win Rate</th>
                    <th>Recent Form</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($standings as $index => $s): 
                    $win_rate = $s['played'] > 0 ? round(($s['wins'] / $s['played']) * 100) : 0;
                    $kda = ($s['t_deaths'] ?? 0) > 0 ? number_format(($s['t_kills'] + $s['t_assists']) / $s['t_deaths'], 2) : ($s['t_kills'] + $s['t_assists']);
                    $form = get_team_form($conn, $s['team_id'], $tournament_id);
                    $streak = get_win_streak($conn, $s['team_id'], $tournament_id);
                ?>
                <tr class="<?= $index < 3 ? 'active-row' : '' ?>">
                    <td class="code-cell <?= $index < 3 ? 'rank-glow' : '' ?>">#<?= $index + 1 ?></td>
                    <td>
                        <a href="../teams/team_profile.php?id=<?= $s['team_id'] ?>" style="color:#fff; text-decoration:none; font-weight:800; display:flex; align-items:center; gap:10px;" onclick="event.stopPropagation()">
                            <?php $logo = team_logo_src($s['logo_path'], '../'); ?>
                            <img src="<?= $logo ?>" alt="<?= htmlspecialchars($s['name']) ?> Logo" style="width:30px; height:30px; object-fit:cover; border-radius:4px; border:1px solid var(--border);">
                            <?= strtoupper($s['name']) ?>
                        </a>
                    </td>
                    <td style="font-family:'Rajdhani'; font-weight:700;"><?= $s['played'] ?></td>
                    <td style="color:var(--cyan); font-weight:700;"><?= $s['wins'] ?></td>
                    <td style="color:var(--danger);"><?= $s['losses'] ?></td>
                    <td style="font-family:'Rajdhani'; font-size:18px; color:var(--gold); font-weight:800;"><?= $s['points'] ?></td>
                    <td style="font-size:12px;"><?= $kda ?> <span style="opacity:0.5; font-size:10px;">(<?= intval($s['t_kills']) ?>/<?= intval($s['t_deaths']) ?>/<?= intval($s['t_assists']) ?>)</span></td>
                    <td style="font-weight:900; color:<?= $streak >= 3 ? 'var(--cyan)' : 'inherit' ?>;"><?= $streak > 0 ? $streak . 'W' : '-' ?></td>
                    <td style="font-size:12px; color:var(--gold);"><?= number_format($s['t_gold_efficiency'] ?? 0) ?></td>
                    <td>
                        <div class="slot-cell">
                            <span style="font-size:11px;"><?= $win_rate ?>%</span>
                            <div class="slot-bar"><span style="width: <?= $win_rate ?>%; background: <?= $win_rate >= 50 ? 'var(--cyan)' : 'var(--danger)' ?>;"></span></div>
                        </div>
                    </td>
                    <td>
                        <?php if(empty($form)): ?>
                            <span style="color:var(--muted); font-size:10px;">NO DATA</span>
                        <?php else: ?>
                            <?php foreach($form as $res): ?>
                                <span class="form-pill form-<?= strtolower($res) ?>"><?= $res ?></span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php render_app_footer(); ?>
</body>
</html>