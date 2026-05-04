<?php
include("../config/db.php");
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include("../auth/auth_check.php");
include("../includes/header.php");
include("../includes/footer.php");
include("../includes/player_photos.php"); // For player_photo_src function
include("../includes/player_avatar.php"); // For player_avatar_data_uri function

if(!isset($_GET['id'])) {
    header("Location: teams.php");
    exit();
}

$player_id = intval($_GET['id']);
if(!isset($_SESSION['active_tournament'])){
    header("Location: ../dashboard/maindashboard.php");
    exit();
}

$tournament_id = intval($_SESSION['active_tournament']);

// Fetch Player Details
$stmt_p = $conn->prepare("
    SELECT p.*, t.name as team_name, t.short_name 
    FROM players p 
    JOIN teams t ON p.team_id = t.id 
    WHERE p.id = ? AND p.tournament_id = ?
");
$stmt_p->bind_param("ii", $player_id, $tournament_id);
$stmt_p->execute();
$player = $stmt_p->get_result()->fetch_assoc();

if(!$player) {
    header("Location: teams.php?error=Player not found in active tournament.");
    exit();
}

// Fetch Player Match Statistics History
$stmt_stats = $conn->prepare("
    SELECT pms.kills, pms.deaths, pms.assists, m.id as match_id, m.match_type,
           t1.short_name as team1_short, t2.short_name as team2_short, m.winner_name
    FROM player_match_stats pms
    JOIN matches m ON pms.match_id = m.id
    JOIN teams t1 ON m.team1_id = t1.id
    JOIN teams t2 ON m.team2_id = t2.id
    WHERE pms.player_id = ? AND pms.tournament_id = ? AND m.is_locked = 1
    ORDER BY m.id ASC
");
$stmt_stats->bind_param("ii", $player_id, $tournament_id);
$stmt_stats->execute();
$match_history = $stmt_stats->get_result()->fetch_all(MYSQLI_ASSOC);

// Prepare data for Chart.js
$chart_labels = [];
$chart_kills = [];
$chart_deaths = [];
$chart_assists = [];

foreach ($match_history as $match) {
    $chart_labels[] = "Match " . $match['match_id'] . " (" . $match['team1_short'] . " vs " . $match['team2_short'] . ")";
    $chart_kills[] = $match['kills'];
    $chart_deaths[] = $match['deaths'];
    $chart_assists[] = $match['assists'];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Player Profile: <?= htmlspecialchars($player['name']) ?> — MOBA TROPZ</title>
    <link rel="stylesheet" href="../dashboard/maindashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .profile-wrapper {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px;
        }
        .profile-header {
            display: flex;
            align-items: center;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 30px;
            margin-bottom: 30px;
        }
        .profile-avatar {
            width: 120px;
            height: 140px;
            object-fit: cover;
            border-radius: 12px;
            border: 2px solid var(--cyan);
            margin-right: 30px;
        }
        .profile-info h1 {
            font-family: 'Rajdhani', sans-serif;
            font-size: 42px;
            color: #fff;
            margin: 0;
            line-height: 1;
            letter-spacing: -1px;
        }
        .profile-info .team-tag {
            font-size: 14px;
            color: var(--gold);
            margin-top: 5px;
            display: block;
            font-weight: 800;
        }
        .profile-info .role-badge {
            display: inline-block;
            background: var(--cyan);
            color: #020617;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            margin-top: 10px;
        }
        .career-history-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 30px;
        }
        .career-history-card h2 {
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

<div class="profile-wrapper">
    <div class="profile-header">
        <?php $player_thumb = player_photo_src($player, '../', player_avatar_data_uri($player['name'], $player['role'])); ?>
        <img src="<?= $player_thumb ?>" alt="<?= htmlspecialchars($player['name']) ?> Profile" class="profile-avatar">
        <div class="profile-info">
            <h1><?= htmlspecialchars(strtoupper($player['name'])) ?> <?= ($player['is_captain']) ? '⭐' : '' ?></h1>
            <span class="team-tag"><?= htmlspecialchars(strtoupper($player['short_name'])) ?> — <?= htmlspecialchars(strtoupper($player['team_name'])) ?></span>
            <span class="role-badge"><?= htmlspecialchars(strtoupper($player['role'])) ?></span>
        </div>
    </div>

    <div class="career-history-card">
        <h2>Career History (K/D/A Progression)</h2>
        <?php if (!empty($match_history)): ?>
            <canvas id="killProgressionChart"></canvas>
        <?php else: ?>
            <p style="color:var(--muted); text-align:center;">No match statistics available for this player yet.</p>
        <?php endif; ?>
    </div>
</div>

<script>
    // RADAR CHART CONFIGURATION
    const radarCtx = document.getElementById('skillRadarChart').getContext('2d');
    new Chart(radarCtx, {
        type: 'radar',
        data: {
            labels: ['Kills', 'Assists', 'Dmg Output', 'Gold Efficiency', 'TF Participation'],
            datasets: [{
                label: 'Operative',
                data: [
                    <?= $overall_stats['matches_played'] > 0 ? ($overall_stats['total_k'] / $overall_stats['matches_played']) : 0 ?>, // Kills
                    <?= $overall_stats['matches_played'] > 0 ? ($overall_stats['total_a'] / $overall_stats['matches_played']) : 0 ?>, // Assists
                    <?= $overall_stats['matches_played'] > 0 ? ($player_radar_stats['total_hd'] / $overall_stats['matches_played'] / 1000) : 0 ?>, // Avg Hero Damage (scaled)
                    <?= $overall_stats['matches_played'] > 0 ? ($player_radar_stats['total_tg'] / $overall_stats['matches_played'] / 500) : 0 ?>, // Avg Total Gold (scaled)
                    <?= $overall_stats['matches_played'] > 0 ? ($player_radar_stats['total_tf'] / $overall_stats['matches_played']) : 0 ?> // Avg TF Participation
                ],
                borderColor: 'var(--cyan)', backgroundColor: 'rgba(0, 242, 255, 0.2)',
            }, {
                label: 'Tournament Avg',
                data: [
                    <?= $tourney_avg['avg_k'] ?>, // Kills
                    <?= $tourney_avg['avg_a'] ?>, // Assists
                    <?= $tourney_avg['avg_hd'] / 1000 ?>, // Avg Hero Damage (scaled)
                    <?= $tourney_avg['avg_tg'] / 500 ?>, // Avg Total Gold (scaled)
                    <?= $tourney_avg['avg_tf'] ?> // Avg TF Participation
                ],
                borderColor: 'rgba(255,255,255,0.2)', backgroundColor: 'rgba(255,255,255,0.05)',
            }]
        },
        options: {
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

    // LINE CHART CONFIGURATION
    <?php if (!empty($match_history)): ?>
    const ctx = document.getElementById('killProgressionChart').getContext('2d');
    const killProgressionChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($chart_labels) ?>,
            datasets: [{
                label: 'Kills',
                data: <?= json_encode($chart_kills) ?>,
                borderColor: 'var(--cyan)',
                backgroundColor: 'rgba(0, 242, 255, 0.1)',
                tension: 0.3,
                fill: true
            }, {
                label: 'Deaths',
                data: <?= json_encode($chart_deaths) ?>,
                borderColor: 'var(--danger)',
                backgroundColor: 'rgba(248, 113, 113, 0.1)',
                tension: 0.3,
                fill: true
            }, {
                label: 'Assists',
                data: <?= json_encode($chart_assists) ?>,
                borderColor: 'var(--gold)',
                backgroundColor: 'rgba(245, 158, 11, 0.1)',
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: false,
                    text: 'Player K/D/A Progression'
                },
                legend: {
                    labels: {
                        color: 'var(--muted)',
                        font: { family: 'Exo 2' }
                    }
                }
            },
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Matches',
                        color: 'var(--muted)',
                        font: { family: 'Exo 2' }
                    },
                    ticks: { color: 'var(--muted)', font: { family: 'Exo 2' } },
                    grid: { color: 'rgba(255,255,255,0.05)' }
                },
                y: {
                    title: {
                        display: true,
                        text: 'Count',
                        color: 'var(--muted)',
                        font: { family: 'Exo 2' }
                    },
                    ticks: { color: 'var(--muted)', font: { family: 'Exo 2' } }
                }
            }
        }
    });
    <?php endif; ?>
</script>

<?php render_app_footer(); ?>
</body>
</html>