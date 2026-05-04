<?php
include("../config/db.php");
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include("../auth/auth_check.php");
include("../includes/header.php");
include("../includes/footer.php");
include("../includes/player_avatar.php");
include("../includes/player_photos.php");

if(!isset($_SESSION['active_tournament'])){
    header("Location: ../dashboard/maindashboard.php");
    exit();
}

$tournament_id = intval($_SESSION['active_tournament']);

// Fetch Top Operatives by KDA (Total Kills + Total Assists) / Greatest(1, Total Deaths)
$stmt = $conn->prepare("
    SELECT p.id, p.name, p.role, p.photo_path, t.short_name, t.name as team_name,
           SUM(pms.kills) as total_k, SUM(pms.deaths) as total_d, SUM(pms.assists) as total_a,
           SUM(pms.hero_damage) as total_dmg, COUNT(pms.id) as matches_played
    FROM players p
    JOIN teams t ON p.team_id = t.id
    JOIN player_match_stats pms ON p.id = pms.player_id
    WHERE p.tournament_id = ?
    GROUP BY p.id
    HAVING matches_played > 0
    ORDER BY (SUM(pms.kills) + SUM(pms.assists)) / GREATEST(1, SUM(pms.deaths)) DESC
    LIMIT 15
");
$stmt->bind_param("i", $tournament_id);
$stmt->execute();
$leaders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MVP Leaderboard — MOBA TROPZ</title>
    <link rel="stylesheet" href="../dashboard/maindashboard.css">
    <style>
        .leader-row { height: 75px; transition: 0.3s; }
        .leader-row:hover { background: rgba(0, 242, 255, 0.04) !important; }
        .rank-badge { 
            width: 28px; height: 28px; display: flex; align-items: center; justify-content: center;
            background: rgba(148, 163, 184, 0.1); border-radius: 6px; font-family: 'Rajdhani'; font-weight: 800;
        }
        .rank-1 { background: var(--gold); color: #000; box-shadow: 0 0 15px var(--gold-glow); }
        .rank-2 { background: #e2e8f0; color: #000; }
        .rank-3 { background: #cd7f32; color: #fff; }
        .kda-val { font-family: 'Space Grotesk'; font-weight: 700; color: var(--cyan); }
    </style>
</head>
<body>
<?php render_app_header('mvp'); ?>

<div class="wrapper">
    <div class="hero" style="text-align: center; padding: 60px;">
        <div class="hero-label">Tactical Rankings</div>
        <h1 class="hero-title" style="font-size: 52px;">MVP LEADERBOARD</h1>
        <div class="hero-meta" style="justify-content: center;">
            <span>TOP 15 OPERATIVES BY KDA PERFORMANCE</span>
        </div>
    </div>

    <div class="table-shell">
        <table class="tournament-table">
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Operative</th>
                    <th>Squad</th>
                    <th>Role</th>
                    <th>Matches</th>
                    <th>K/D/A</th>
                    <th>KDA Ratio</th>
                    <th>Total Damage</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($leaders as $index => $p): 
                    $kda_ratio = number_format(($p['total_k'] + $p['total_a']) / max(1, $p['total_d']), 2);
                    $rank = $index + 1;
                ?>
                <tr class="leader-row">
                    <td>
                        <div class="rank-badge <?= 'rank-'.$rank ?>"><?= $rank ?></div>
                    </td>
                    <td>
                        <div style="display:flex; align-items:center; gap:12px;">
                            <?php $thumb = player_photo_src($p, '../', player_avatar_data_uri($p['name'], $p['role'])); ?>
                            <img src="<?= $thumb ?>" class="player-avatar-mini" style="width:36px; height:44px;">
                            <a href="../teams/player_profile.php?id=<?= $p['id'] ?>"
                               style="color:#fff; text-decoration:none; font-weight:800; font-size:15px;"
                               onclick="event.stopPropagation()">
                                <?= strtoupper($p['name']) ?>
                            </a>
                        </div>
                    </td>
                    <td style="color:var(--cyan); font-weight:700;"><?= $p['short_name'] ?></td>
                    <td style="font-size:11px; font-weight:800; opacity:0.8;"><?= $p['role'] ?></td>
                    <td style="font-family:'Rajdhani'; font-weight:700;"><?= $p['matches_played'] ?></td>
                    <td style="font-size:13px; letter-spacing:1px;">
                        <?= $p['total_k'] ?> / <span style="color:var(--danger);"><?= $p['total_d'] ?></span> / <?= $p['total_a'] ?>
                    </td>
                    <td class="kda-val"><?= $kda_ratio ?></td>
                    <td style="font-size:12px; opacity:0.7;"><?= number_format($p['total_dmg']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($leaders)): ?>
                    <tr><td colspan="8" class="empty-cell">No combat data recorded yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php render_app_footer(); ?>
</body>
</html>