<?php
include("../config/db.php");
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include("../auth/auth_check.php");
include("../includes/header.php");
include("../includes/footer.php");
include("../includes/player_avatar.php");
include("../includes/player_photos.php");

if(!isset($_GET['id'])) { header("Location: teams.php"); exit(); }
$team_id = intval($_GET['id']);
$tournament_id = $_SESSION['active_tournament'] ?? 0;

// Fetch Team Details & Standings Intelligence
$stmt = $conn->prepare("
    SELECT t.*, s.wins, s.losses, s.played, s.points
    FROM teams t 
    JOIN standings s ON t.id = s.team_id 
    WHERE t.id = ? AND t.tournament_id = ?
");
$stmt->bind_param("ii", $team_id, $tournament_id);
$stmt->execute();
$team = $stmt->get_result()->fetch_assoc();

if(!$team) { header("Location: teams.php"); exit(); }

// Fetch Operative Roster
$stmt_p = $conn->prepare("SELECT * FROM players WHERE team_id = ? ORDER BY is_captain DESC, name ASC");
$stmt_p->bind_param("i", $team_id);
$stmt_p->execute();
$roster = $stmt_p->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch Individual Player Summary Stats for Roster
$stmt_player_summary = $conn->prepare("
    SELECT p.id, p.name, p.role, p.is_captain, p.photo_path,
           SUM(pms.kills) as total_k, SUM(pms.deaths) as total_d, SUM(pms.assists) as total_a,
           COUNT(pms.id) as matches_played
    FROM players p
    LEFT JOIN player_match_stats pms ON p.id = pms.player_id AND pms.tournament_id = ?
    WHERE p.team_id = ?
    GROUP BY p.id ORDER BY p.is_captain DESC, p.name ASC
");
$stmt_player_summary->bind_param("ii", $tournament_id, $team_id);
$stmt_player_summary->execute();
$roster_with_stats = $stmt_player_summary->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch Recent Mission History
$stmt_history = $conn->prepare("
    SELECT m.*, t1.short_name as s1, t2.short_name as s2, t1.name as t1_full, t2.name as t2_full
    FROM matches m
    JOIN teams t1 ON m.team1_id = t1.id
    JOIN teams t2 ON m.team2_id = t2.id
    WHERE (m.team1_id = ? OR m.team2_id = ?) AND m.tournament_id = ? AND m.is_locked = 1
    ORDER BY m.id DESC LIMIT 5
");
$stmt_history->bind_param("iii", $team_id, $team_id, $tournament_id);
$stmt_history->execute();
$history = $stmt_history->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch Overall Team Stats (KDA)
$stmt_team_overall_stats = $conn->prepare("
    SELECT SUM(pms.kills) as total_k, SUM(pms.deaths) as total_d, SUM(pms.assists) as total_a
    FROM player_match_stats pms
    JOIN players p ON pms.player_id = p.id
    WHERE p.team_id = ? AND pms.tournament_id = ?
");
$stmt_team_overall_stats->bind_param("ii", $team_id, $tournament_id);
$stmt_team_overall_stats->execute();
$team_overall_stats_radar = $stmt_team_overall_stats->get_result()->fetch_assoc();

// Siguraduhin na may default values kung wala pang match data
$team_overall_stats_radar = $team_overall_stats_radar ?? ['total_k' => 0, 'total_d' => 0, 'total_a' => 0, 'matches_played' => 0];

$team_overall_kda_ratio = ($team_overall_stats_radar['total_d'] > 0) ? number_format(($team_overall_stats_radar['total_k'] + $team_overall_stats_radar['total_a']) / $team_overall_stats_radar['total_d'], 2) : ($team_overall_stats_radar['total_k'] + $team_overall_stats_radar['total_a']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Squad Intel: <?= $team['short_name'] ?> — MOBA TROPZ</title>
    <link rel="stylesheet" href="../dashboard/maindashboard.css">
    <style>
        .team-profile-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 30px; }
        .squad-card { background: var(--card); border: 2px solid var(--border); padding: 40px; border-radius: var(--radius); text-align: center; }
        .squad-logo { width: 120px; height: 120px; border-radius: 20px; border: 2px solid var(--cyan); margin-bottom: 20px; object-fit: cover; }
        .win-rate-ring { font-family: 'Rajdhani'; font-size: 48px; font-weight: 900; color: var(--cyan); }
        .divider { height: 1px; background: var(--border); margin: 25px 0; }
        .history-row { transition: 0.3s; border-bottom: 1px solid rgba(255,255,255,0.03); }
        .history-row:hover { background: rgba(0, 242, 255, 0.02); }
        .outcome-tag { font-family: 'Rajdhani'; font-weight: 800; font-size: 11px; padding: 2px 8px; border-radius: 4px; }
        .outcome-win { background: rgba(0, 242, 255, 0.1); color: var(--cyan); }
        .outcome-loss { background: rgba(248, 113, 113, 0.1); color: var(--danger); }
        .vs-label { color: var(--muted); font-size: 10px; font-weight: 800; margin: 0 10px; }
        .team-overall-stats {
            display: flex;
            gap: 15px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid var(--border);
            justify-content: center;
        }
        .team-overall-stats > div {
            flex: 1;
            text-align: center;
            padding: 10px;
            background: rgba(0,0,0,0.2);
            border: 1px solid var(--border);
            border-radius: var(--radius);
        }
        .team-overall-stats strong {
            font-family: 'Rajdhani';
            font-size: 24px;
            color: var(--gold);
            display: block;
        }
        .team-overall-stats span {
            font-size: 10px;
            color: var(--muted);
            text-transform: uppercase;
            font-weight: 800;
        }
        .profile-header-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
        }
        .player-kda-summary {
            font-size: 10px;
            color: var(--muted);
            margin-top: 5px;
        }
        .player-kda-summary span {
            font-weight: 700;
            color: var(--cyan);
        }
        .player-kda-summary .deaths {
            color: var(--danger);
        }
        .chart-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 30px;
            margin-bottom: 30px;
        }
        .chart-card h2 {
            font-family: 'Rajdhani', sans-serif;
            font-size: 24px;
            color: #fff;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 10px;
        }
    </style>
</head>
<body>
<?php render_app_header('teams'); ?>

<div class="wrapper">
    <div class="profile-header-grid">
        <div class="squad-card" style="padding: 30px;">
            <?php $logo = !empty($team['logo_path']) ? '../' . $team['logo_path'] : '../assets/default_team.png'; ?>
            <img src="<?= $logo ?>" class="squad-logo" onerror="this.src='../assets/default_team.png'" style="width:150px; height:150px; border-radius:50%; margin-bottom:20px;">
            <h1 class="hero-title" style="font-size:38px; margin-bottom:5px;"><?= strtoupper($team['name']) ?></h1>
            <div class="hero-label" style="font-size:12px;"><?= $team['short_name'] ?> OPERATIONAL UNIT</div>
            
            <div class="divider" style="margin: 30px 0;"></div>
            
            <div class="win-rate-ring" style="margin-bottom:5px;"><?= $team['played'] > 0 ? round(($team['wins'] / $team['played']) * 100) : 0 ?>%</div>
            <div class="stat-label" style="font-size:10px;">WIN RATE SUCCESS</div>
            
            <div class="stat-grid" style="grid-template-columns: 1fr 1fr; margin-top: 25px; gap:10px;">
                <div class="stat-card" style="padding:15px; border-radius:8px;">
                    <span class="stat-label" style="font-size:9px;">Wins</span>
                    <strong style="color:var(--cyan); font-size:24px;"><?= $team['wins'] ?></strong>
                </div>
                <div class="stat-card" style="padding:15px; border-radius:8px;">
                    <span class="stat-label" style="font-size:9px;">Points</span>
                    <strong style="color:var(--gold); font-size:24px;"><?= $team['points'] ?></strong>
                </div>
            </div>

            <div class="team-overall-stats" style="margin-top:25px; padding-top:20px;">
                <div class="stat-card" style="padding:10px; border-radius:8px;">
                    <strong><?= $team_overall_kda_ratio ?></strong>
                    <span>TEAM KDA</span>
                </div>
                <div style="padding:10px; border-radius:8px;">
                    <strong><?= $team_overall_stats_radar['total_k'] ?></strong>
                    <span>TOTAL KILLS</span>
                </div>
                <div style="padding:10px; border-radius:8px;">
                    <strong><?= $team_overall_stats_radar['total_d'] ?></strong>
                    <span>TOTAL DEATHS</span>
                </div>
            </div>

            <?php if (!empty($team['tactical_notes'])): ?>
                <div class="section-label" style="margin-top: 30px; text-align:left;">Tactical Notes (Internal)</div>
                <div class="tactical-notes-display" style="margin-top:10px; text-align:left; border-radius:8px; font-size:12px;">
                    <?= htmlspecialchars($team['tactical_notes']) ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Right Side: Roster Intelligence -->
        <div>
            <div class="section-label">Active Operative Roster</div>
            <div class="p-grid" style="grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));">
                <?php foreach($roster_with_stats as $p): ?>
                    <div class="p-item" style="padding: 20px; background: var(--card); border: 1px solid var(--border);">
                        <div style="display:flex; align-items:center; gap:15px;">
                            <?php $thumb = player_photo_src($p, '../', player_avatar_data_uri($p['name'], $p['role'])); ?>
                            <img src="<?= $thumb ?>" class="player-avatar-mini" style="width:50px; height:60px;">
                            <div>
                                <a href="player_profile.php?id=<?= $p['id'] ?>" style="color:#fff; text-decoration:none; font-weight:800; font-size:16px;">
                                    <?= strtoupper($p['name']) ?> <?= ($p['is_captain']) ? '⭐' : '' ?>
                                </a>
                                <div style="color:var(--cyan); font-size:11px; font-weight:800; margin-top:4px;"><?= $p['role'] ?></div>
                                <?php if ($p['matches_played'] > 0): ?>
                                    <div class="player-kda-summary">
                                        <span class="kills"><?= $p['total_k'] ?>K</span> / 
                                        <span class="deaths"><?= $p['total_d'] ?>D</span> / 
                                        <span class="assists"><?= $p['total_a'] ?>A</span>
                                        (KDA: <span><?= number_format(($p['total_k'] + $p['total_a']) / max(1, $p['total_d']), 2) ?></span>)
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if(empty($roster)): ?>
                    <div class="empty-cell" style="grid-column: 1/-1;">NO OPERATIVES REGISTERED IN THIS SQUAD.</div>
                <?php endif; ?>
            </div>
            
            <!-- Mission History Section -->
            <div class="section-label" style="margin-top: 40px;">Recent Mission History</div>
            <div class="table-shell">
                <table class="tournament-table">
                    <thead>
                        <tr>
                            <th>Match ID</th>
                            <th>Engagement</th>
                            <th>Outcome</th>
                            <th>Score</th>
                            <th>Intel</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($history as $m): 
                            $is_win = ($m['winner_name'] == $team['name']);
                            $opponent = ($m['team1_id'] == $team_id) ? $m['s2'] : $m['s1'];
                        ?>
                        <tr class="history-row">
                            <td class="code-cell">#<?= $m['id'] ?></td>
                            <td>
                                <span style="font-weight:700; color:var(--cyan);"><?= $team['short_name'] ?></span>
                                <span class="vs-label">VS</span>
                                <span style="font-weight:700;"><?= $opponent ?></span>
                            </td>
                            <td>
                                <span class="outcome-tag <?= $is_win ? 'outcome-win' : 'outcome-loss' ?>">
                                    <?= $is_win ? 'SUCCESS' : 'DEFEATED' ?>
                                </span>
                            </td>
                            <td style="font-family:'Rajdhani'; font-weight:800;"><?= $m['score1'] ?> — <?= $m['score2'] ?></td>
                            <td><a href="../matches/match_intel.php?match_id=<?= $m['id'] ?>" class="table-action" style="font-size:10px;">VIEW</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-top: 40px;">
                <div class="chart-card">
                    <h2>Squad Skill Matrix</h2>
                    <?php if ($team_overall_stats_radar['matches_played'] > 0): ?>
                        <canvas id="squadRadarChart" style="max-height: 300px;"></canvas>
                    <?php else: ?>
                        <p style="color:var(--muted); text-align:center;">No match data for radar analysis.</p>
                    <?php endif; ?>
                </div>
                <div class="chart-card">
                    <h2>Team KDA Trend</h2>
                    <?php if (!empty($team_match_trend_data)): ?>
                        <canvas id="teamKDATrendChart" style="max-height: 300px;"></canvas>
                    <?php else: ?>
                        <p style="color:var(--muted); text-align:center;">No match history for trend analysis.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div style="margin-top: 30px; display: flex; gap: 15px;">
                <a href="teams.php" class="app-action" style="border-radius:0; flex: 1; text-align:center;">← BACK TO COMMAND CENTER</a>
                <a href="edit_team.php?id=<?= $team['id'] ?>" class="app-action gold" style="border-radius:0; flex: 1; text-align: center;">MODIFY SQUAD DATA</a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Squad Radar Chart
    <?php if ($team_overall_stats_radar['matches_played'] > 0): ?>
    const squadRadarCtx = document.getElementById('squadRadarChart').getContext('2d');
    new Chart(squadRadarCtx, {
        type: 'radar',
        data: {
            labels: ['Kills', 'Assists', 'Dmg Output', 'Gold Efficiency', 'TF Participation'],
            datasets: [{
                label: 'Squad Avg',
                data: [
                    <?= $team_overall_stats_radar['total_k'] / $team_overall_stats_radar['matches_played'] ?>,
                    <?= $team_overall_stats_radar['total_a'] / $team_overall_stats_radar['matches_played'] ?>,
                    <?= $team_overall_stats_radar['total_hd'] / $team_overall_stats_radar['matches_played'] / 1000 ?>, // Scaled
                    <?= $team_overall_stats_radar['total_tg'] / $team_overall_stats_radar['matches_played'] / 500 ?>, // Scaled
                    <?= $team_overall_stats_radar['total_tf'] / $team_overall_stats_radar['matches_played'] ?>
                ],
                borderColor: 'var(--cyan)', backgroundColor: 'rgba(0, 242, 255, 0.2)',
            }, {
                label: 'Tournament Avg',
                data: [
                    <?= $tourney_avg['avg_k'] ?>, <?= $tourney_avg['avg_a'] ?>,
                    <?= $tourney_avg['avg_hd'] / 1000 ?>, <?= $tourney_avg['avg_tg'] / 500 ?>,
                    <?= $tourney_avg['avg_tf'] ?>
                ],
                borderColor: 'rgba(255,255,255,0.2)', backgroundColor: 'rgba(255,255,255,0.05)',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { labels: { color: '#fff', font: { family: 'Exo 2' } } } },
            scales: {
                r: {
                    angleLines: { color: 'rgba(255,255,255,0.1)' },
                    grid: { color: 'rgba(255,255,255,0.1)' },
                    pointLabels: { color: 'var(--muted)', font: { size: 10, family: 'Rajdhani' } },
                    ticks: { display: false }
                }
            }
        }
    });
    <?php endif; ?>

    // Team KDA Trend Chart
    <?php if (!empty($team_match_trend_data)): ?>
    const teamKDATrendCtx = document.getElementById('teamKDATrendChart').getContext('2d');
    new Chart(teamKDATrendCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode($trend_labels) ?>,
            datasets: [{
                label: 'Squad KDA Ratio',
                data: <?= json_encode($trend_kda_ratios) ?>,
                borderColor: 'var(--gold)',
                backgroundColor: 'rgba(245, 158, 11, 0.1)',
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { labels: { color: '#fff', font: { family: 'Exo 2' } } } },
            scales: {
                x: {
                    title: { display: true, text: 'Matches', color: 'var(--muted)', font: { family: 'Exo 2' } },
                    ticks: { color: 'var(--muted)', font: { family: 'Exo 2' } },
                    grid: { color: 'rgba(255,255,255,0.05)' }
                },
                y: {
                    title: { display: true, text: 'KDA Ratio', color: 'var(--muted)', font: { family: 'Exo 2' } },
                    ticks: { color: 'var(--muted)', font: { family: 'Exo 2' } },
                    grid: { color: 'rgba(255,255,255,0.05)' }
                }
            }
        }
    });
    <?php endif; ?>
</script>

<?php render_app_footer(); ?>
</body>
</html>