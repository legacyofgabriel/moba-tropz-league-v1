<?php
include("../config/db.php");
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include("../auth/auth_check.php");
include("../includes/header.php");
include("../includes/footer.php");
include("../includes/player_photos.php");
include("../includes/team_photos.php"); // Include team photos for logos
include("../includes/player_avatar.php");

if(!isset($_GET['match_id'])) {
    header("Location: matches.php");
    exit();
}

$match_id = intval($_GET['match_id']);
$tournament_id = $_SESSION['active_tournament'] ?? 0;

// Fetch Match Details
$stmt_m = $conn->prepare("
    SELECT m.*, t1.name as t1_name, t1.short_name as t1_short, t1.logo_path as t1_logo,
           t2.name as t2_name, t2.short_name as t2_short, t2.logo_path as t2_logo, t2.id as t2_id,
           p.name as mvp_name, p.role as mvp_role, p.photo_path as mvp_photo
    FROM matches m
    JOIN teams t1 ON m.team1_id = t1.id
    JOIN teams t2 ON m.team2_id = t2.id
    LEFT JOIN players p ON m.mvp_player_id = p.id
    WHERE m.id = ? AND m.tournament_id = ?
");
$stmt_m->bind_param("ii", $match_id, $tournament_id);
$stmt_m->execute();
$match = $stmt_m->get_result()->fetch_assoc();

if(!$match || !$match['is_locked']) {
    header("Location: matches.php?error=Match intel not found or match is still pending.");
    exit();
}

// Fetch All Player Stats for this match
$stmt_stats = $conn->prepare("
    SELECT pms.*, p.name as p_name, p.role, t.short_name as team_tag
    FROM player_match_stats pms
    JOIN players p ON pms.player_id = p.id
    JOIN teams t ON p.team_id = t.id
    WHERE pms.match_id = ?
    ORDER BY t.id ASC, pms.kills DESC
");
$stmt_stats->bind_param("i", $match_id);
$stmt_stats->execute();
$all_stats = $stmt_stats->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate Squad Totals
$t1_stats = ['k'=>0, 'd'=>0, 'a'=>0, 'dmg'=>0];
$t2_stats = ['k'=>0, 'd'=>0, 'a'=>0, 'dmg'=>0];
foreach($all_stats as $s) {
    $target = ($s['team_tag'] == $match['t1_short']) ? 't1_stats' : 't2_stats';
    ${$target}['k'] += $s['kills'];
    ${$target}['d'] += $s['deaths'];
    ${$target}['a'] += $s['assists'];
    ${$target}['dmg'] += $s['hero_damage'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Match Intel: <?= $match['t1_short'] ?> vs <?= $match['t2_short'] ?> — MOBA TROPZ</title>
    <link rel="stylesheet" href="../dashboard/maindashboard.css">
    <style>
        .intel-header {
            display: grid; grid-template-columns: 1fr auto 1fr; gap: 40px;
            align-items: center; justify-content: center; background: var(--card); border: 1px solid var(--border);
            padding: 60px 40px; border-radius: 24px; margin-bottom: 30px;
            text-align: center; position: relative; overflow: hidden;
            background-image: 
                radial-gradient(circle at 20% 50%, rgba(0, 242, 255, 0.1), transparent 40%),
                radial-gradient(circle at 80% 50%, rgba(248, 113, 113, 0.1), transparent 40%);
            box-shadow: 0 40px 100px -20px rgba(0,0,0,0.8);
        }
        .team-block { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; }
        .intel-header::before {
            /* Repeating background for "AFTER ACTION REPORT" */
            content: "AFTER ACTION REPORT"; position: absolute; top: 10px; left: 50%;
            transform: translateX(-50%); font-size: 12px; font-weight: 900; color: var(--cyan);
            letter-spacing: 8px; opacity: 0.6; text-shadow: 0 0 10px var(--cyan-glow);
        }
        .score-display { 
            font-family: 'Rajdhani'; font-size: 100px; font-weight: 900; color: #fff; letter-spacing: -4px; 
            display: flex; align-items: center; justify-content: center; gap: 20px; line-height: 1;
            text-shadow: 0 0 30px rgba(255,255,255,0.2);
        }
        .score-num { min-width: 90px; }
        .team-block h2 { 
            font-family: 'Rajdhani'; font-size: 38px; font-weight: 800; color: var(--cyan); margin: 15px 0; 
            letter-spacing: 1px; text-shadow: 0 5px 15px rgba(0,0,0,0.5);
        }
        .mvp-card {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.15) 0%, rgba(10,15,28,0.95) 100%);
            border: 2px solid var(--gold); padding: 30px; border-radius: var(--radius);
            display: flex; align-items: center; gap: 20px; margin-bottom: 30px;
            box-shadow: 0 20px 60px -10px var(--gold-glow);
            clip-path: polygon(0 0, 100% 0, 100% 80%, 95% 100%, 0 100%);
        }
        .mvp-badge { background: var(--gold); color: #000; padding: 4px 12px; font-weight: 900; font-size: 11px; text-transform: uppercase; border-radius: 4px; }
        .squad-summary-grid { 
            display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 30px;
            background: rgba(15, 23, 42, 0.3); padding: 20px; border-radius: 20px; border: 1px solid var(--border);
        }
        .summary-card { 
            background: rgba(0, 0, 0, 0.4); border: 1px solid var(--border); padding: 25px; 
            border-radius: var(--radius); display: flex; flex-direction: column; align-items: center;
            text-align: center;
            transition: 0.3s;
        }
        .summary-card:hover {
            transform: scale(1.02);
            background: rgba(0, 0, 0, 0.6);
        }
        .summary-card .team-logo-small { width: 50px; height: 50px; object-fit: cover; border-radius: 8px; margin-bottom: 10px; }
        .summary-card strong { font-size: 28px; font-family: 'Rajdhani'; color: #fff; }
        .summary-card span { font-size: 11px; opacity: 0.7; }

        .stat-table-tactical { width: 100%; border-collapse: collapse; background: var(--card); border: 1px solid var(--border); }
        .stat-table-tactical th { background: #000; color: var(--cyan); font-size: 11px; padding: 15px; text-transform: uppercase; text-align: center; }
        .stat-table-tactical td { padding: 15px; border-bottom: 1px solid var(--border); font-size: 13px; text-align: center; }
        .stat-table-tactical tr:hover { background: rgba(0, 242, 255, 0.03); }
        .win-tag { 
            background: var(--cyan); color: #000; padding: 4px 12px; border-radius: 4px; 
            font-weight: 900; font-size: 11px; letter-spacing: 1px; box-shadow: 0 0 15px var(--cyan-glow); 
            display: inline-block; margin-top: 10px; }

        /* Specific row styling for teams */
        .team1-row { border-left: 4px solid var(--cyan); }
        .team2-row { border-left: 4px solid var(--danger); }
        .mvp-row { background: rgba(245, 158, 11, 0.1) !important; border-color: var(--gold) !important; box-shadow: 0 0 10px var(--gold-glow); }
        .mvp-row td { color: #fff !important; }
        .mvp-row td a { color: var(--gold) !important; }

        .team-block .team-logo-header { width: 140px; height: 140px; object-fit: cover; border-radius: 18px; border: 3px solid var(--cyan); margin-bottom: 15px; box-shadow: 0 0 20px rgba(0, 242, 255, 0.1); }
        .team-block.red-side .team-logo-header { border-color: var(--danger); box-shadow: 0 0 20px rgba(248, 113, 113, 0.1); }
        .team-block.red-side h2 { color: var(--danger); }
        .team-block.red-side .hero-label { color: var(--danger); }
    </style>
</head>
<body>
<?php render_app_header('matches'); ?>

<div class="wrapper">
    <div class="intel-header">
        <div class="team-block <?= ($match['winner_name'] == $match['t1_name']) ? 'winning-side' : 'losing-side' ?>">
            <img src="<?= team_logo_src($match['t1_logo'], '../') ?>" class="team-logo-header" alt="Team Logo">
            <h2 style="color:<?= ($match['winner_name'] == $match['t1_name']) ? 'var(--cyan)' : 'var(--muted)' ?>; opacity:<?= ($match['is_locked'] && $match['winner_name'] != $match['t1_name']) ? '0.6' : '1' ?>;">
                <?= strtoupper($match['t1_name']) ?>
            </h2>
            <?php if($match['winner_name'] == $match['t1_name']): ?><span class="win-tag">MATCH WINNER</span><?php endif; ?>
        </div>
        <div class="score-display" style="color:<?= ($match['winner_name'] == $match['t1_name']) ? 'var(--cyan)' : 'var(--danger)' ?>; min-width: 250px;">
            <span class="score-num" style="text-align: right;"><?= $match['score1'] ?></span>
            <span style="color:var(--muted); font-size:48px; position: relative; top: -5px; flex-shrink: 0; padding: 0 10px;">—</span>
            <span class="score-num" style="text-align: left;"><?= $match['score2'] ?></span>
        </div>
        <div class="team-block red-side <?= ($match['winner_name'] == $match['t2_name']) ? 'winning-side' : 'losing-side' ?>">
            <img src="<?= team_logo_src($match['t2_logo'], '../') ?>" class="team-logo-header" alt="Team Logo">
            <h2 style="color:<?= ($match['winner_name'] == $match['t2_name']) ? 'var(--cyan)' : 'var(--danger)' ?>; opacity:<?= ($match['is_locked'] && $match['winner_name'] != $match['t2_name']) ? '0.6' : '1' ?>;">
                <?= strtoupper($match['t2_name']) ?>
            </h2>
            <?php if($match['winner_name'] == $match['t2_name']): ?><span class="win-tag">MATCH WINNER</span><?php endif; ?>
        </div>
    </div>

    <div style="display:flex; justify-content:center; margin-bottom:30px;">
        <button onclick="copyMatchDispatch(this)" class="app-action gold" style="font-size:10px; padding:10px 20px; cursor:pointer;">COPY MATCH DISPATCH</button>
    </div>

    <div class="squad-summary-grid">
        <div class="summary-card" style="border-left: 4px solid var(--cyan); background: rgba(0, 242, 255, 0.05);">
            <img src="<?= team_logo_src($match['t1_logo'], '../') ?>" class="team-logo-small" alt="Team Logo">
            <div class="stat-label" style="color:var(--cyan);">SQUAD INTEL</div>
            <strong style="color:var(--cyan);"><?= $t1_stats['k'] ?> / <?= $t1_stats['d'] ?> / <?= $t1_stats['a'] ?></strong>
            <span style="margin-top:5px;">TOTAL KDA</span>
            <div class="divider" style="width:50%; margin:15px auto;"></div>
            <strong style="font-size:20px;"><?= number_format($t1_stats['dmg']) ?></strong>
            <span>TOTAL HERO DAMAGE</span>
        </div>
        <div class="summary-card" style="border-left: 4px solid var(--danger); background: rgba(248, 113, 113, 0.05);">
            <img src="<?= team_logo_src($match['t2_logo'], '../') ?>" class="team-logo-small" alt="Team Logo">
            <div class="stat-label" style="color:var(--danger);">SQUAD INTEL</div>
            <strong style="color:var(--danger);"><?= $t2_stats['k'] ?> / <?= $t2_stats['d'] ?> / <?= $t2_stats['a'] ?></strong>
            <span style="margin-top:5px;">TOTAL KDA</span>
            <div class="divider" style="width:50%; margin:15px auto;"></div>
            <strong style="font-size:20px;"><?= number_format($t2_stats['dmg']) ?></strong>
            <span>TOTAL HERO DAMAGE</span>
        </div>
    </div>

    <?php if($match['mvp_name']): ?>
    <div class="mvp-card">
        <?php 
        $mvp_img = !empty($match['mvp_photo']) ? '../' . $match['mvp_photo'] : player_avatar_data_uri($match['mvp_name'], $match['mvp_role']); // Assuming player_avatar_data_uri is available
        // Determine MVP card border color based on winning team
        $mvp_border_color = ($match['mvp_name'] && $match['winner_name'] == $match['t1_name']) ? 'var(--cyan)' : 'var(--danger)';
        ?>
        <img src="<?= $mvp_img ?>" style="width: 80px; height: 100px; object-fit: cover; border: 2px solid <?= $mvp_border_color ?>;">
        <div>
            <div class="mvp-badge">Most Valuable Player</div>
            <h3 style="font-family:'Rajdhani'; font-size:32px; color:#fff; margin: 5px 0;"><?= strtoupper($match['mvp_name']) ?></h3>
            <span style="color:var(--muted); font-size:12px; font-weight:700;"><?= strtoupper($match['mvp_role']) ?></span>
        </div>
    </div>
    <?php endif; ?>

    <div class="section-label">Squad Statistics Breakdown</div>
    <div class="table-shell">
        <table class="stat-table-tactical">
            <thead>
                <tr>
                    <th>Operative</th>
                    <th>Hero</th>
                    <th>K/D/A</th>
                    <th>Damage</th>
                    <th>Gold</th>
                    <th>TF%</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($all_stats as $s): ?>
                <?php
                    $team_total_dmg = ($s['team_tag'] == $match['t1_short']) ? $t1_stats['dmg'] : $t2_stats['dmg'];
                    $dmg_share = ($team_total_dmg > 0) ? round(($s['hero_damage'] / $team_total_dmg) * 100) : 0;
                    $row_class = ($s['team_tag'] == $match['t1_short']) ? 'team1-row' : 'team2-row';
                    if ($s['p_name'] == $match['mvp_name']) $row_class .= ' mvp-row';
                ?>
                <tr class="<?= $row_class ?>">
                    <td style="font-weight:700;">
                        <a href="player_profile.php?id=<?= $s['player_id'] ?>" style="color:#fff; text-decoration:none;">
                            <?= strtoupper($s['p_name']) ?>
                        </a>
                    </td>
                    <td style="color:var(--gold); font-weight:800;"><?= strtoupper($s['hero_name']) ?></td>
                    <td style="font-family:'Space Grotesk';">
                        <span style="color:var(--cyan);"><?= $s['kills'] ?></span> / 
                        <span style="color:var(--danger);"><?= $s['deaths'] ?></span> / 
                        <span><?= $s['assists'] ?></span>
                    </td>
                    <td><?= number_format($s['hero_damage']) ?></td>
                    <td style="color:var(--gold); font-weight:800; font-size:11px;"><?= $dmg_share ?>%</td>
                    <td style="font-family:'Space Grotesk';"><?= number_format($s['total_gold']) ?></td>
                    <td style="color:var(--cyan); font-weight:800;"><?= $s['tf_participation'] ?>%</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <div style="margin-top:40px; text-align:center;">
        <a href="matches.php" class="app-action" style="border:none;">← RETURN TO FIXTURES</a>
    </div>
</div>

<?php render_app_footer(); ?>
<script>
function copyMatchDispatch(btn) {
    const text = `[COMBAT_REPORT_MISSION_ID: <?= $match['id'] ?>]\n` +
                 `TOURNAMENT: <?= strtoupper($match['match_type']) ?>\n` +
                 `RESULT: <?= strtoupper($match['t1_name']) ?> (${<?= $match['score1'] ?>}) vs (${<?= $match['score2'] ?>}) <?= strtoupper($match['t2_name']) ?>\n` +
                 `OUTCOME: WINNER_DECLARED [<?= strtoupper($match['winner_name']) ?>]\n` +
                 `------------------------\n` +
                 `MVP_IDENTIFIED: <?= strtoupper($match['mvp_name'] ?? 'N/A') ?>\n` +
                 `SQUAD_01_KDA: <?= $t1_stats['k'] ?>/<?= $t1_stats['d'] ?>/<?= $t1_stats['a'] ?>\n` +
                 `SQUAD_02_KDA: <?= $t2_stats['k'] ?>/<?= $t2_stats['d'] ?>/<?= $t2_stats['a'] ?>\n` +
                 `------------------------\n` +
                 `STATUS: MISSION_LOGGED`;
    navigator.clipboard.writeText(text).then(() => {
        const orig = btn.innerText;
        btn.innerText = "DISPATCH_COPIED";
        btn.style.color = "var(--cyan)";
        setTimeout(() => { btn.innerText = orig; btn.style.color = ""; }, 2000);
    });
}
</script>
</body>
</html>