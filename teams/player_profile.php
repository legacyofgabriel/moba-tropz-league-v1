<?php
include("../config/db.php");
include("../auth/auth_check.php");
include("../includes/header.php");
include("../includes/footer.php");
include("../includes/player_avatar.php");
include("../includes/player_photos.php");

ensure_player_photo_column($conn);

$player_id = intval($_GET['id'] ?? 0);
$p = $conn->query("SELECT p.*, t.name as team_name, t.short_name
                   FROM players p
                   JOIN teams t ON p.team_id = t.id
                   WHERE p.id = $player_id")->fetch_assoc();

if(!$p) {
    header("Location: teams.php");
    exit();
}

$mvp_count = intval($conn->query("SELECT COUNT(*) AS total FROM matches WHERE mvp_player_id = $player_id")->fetch_assoc()['total']);

$stats = $conn->query("SELECT
        COUNT(*) AS games,
        COALESCE(SUM(kills), 0) AS kills,
        COALESCE(SUM(deaths), 0) AS deaths,
        COALESCE(SUM(assists), 0) AS assists,
        COALESCE(AVG(hero_damage), 0) AS avg_damage,
        COALESCE(AVG(total_gold), 0) AS avg_gold,
        COALESCE(AVG(tf_participation), 0) AS avg_tf
    FROM player_match_stats
    WHERE player_id = $player_id")->fetch_assoc();

$games = intval($stats['games']);
$kills = intval($stats['kills']);
$deaths = intval($stats['deaths']);
$assists = intval($stats['assists']);
$avg_deaths = $games > 0 ? $deaths / $games : 0;
$avg_gold = floatval($stats['avg_gold']);

$kda = $deaths > 0 ? ($kills + $assists) / $deaths : ($kills + $assists);
$avg_kills = $games > 0 ? $kills / $games : 0;
$avg_assists = $games > 0 ? $assists / $games : 0;
$avatar = player_photo_src($p, '../', player_avatar_data_uri($p['name'], $p['role']));

$match_history = $conn->query("SELECT ps.*, m.match_type, m.round_name, m.winner_name, t1.name AS team1, t2.name AS team2
    FROM player_match_stats ps
    JOIN matches m ON ps.match_id = m.id
    LEFT JOIN teams t1 ON m.team1_id = t1.id
    LEFT JOIN teams t2 ON m.team2_id = t2.id
    WHERE ps.player_id = $player_id
    ORDER BY ps.id DESC
    LIMIT 8");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($p['name']) ?> - Pro Profile</title>
    <link rel="stylesheet" href="../dashboard/maindashboard.css">
    <style>
        .profile-hero {
            display: grid;
            grid-template-columns: 320px 1fr;
            gap: 28px;
            align-items: stretch;
            padding: 28px;
            background: linear-gradient(135deg, rgba(15,23,42,.86), rgba(30,41,59,.58));
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: 0 24px 50px rgba(0,0,0,.28);
        }
        .profile-photo-card {
            position: relative;
            min-height: 390px;
            overflow: hidden;
            border-radius: var(--radius);
            border: 1px solid rgba(255,255,255,.1);
            background: rgba(2,6,23,.45);
        }
        .profile-photo-card img {
            width: 100%;
            height: 100%;
            min-height: 390px;
            object-fit: cover;
            display: block;
        }
        .profile-photo-card::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, transparent 50%, rgba(2,6,23,.88));
        }
        .photo-caption {
            position: absolute;
            left: 20px;
            right: 20px;
            bottom: 20px;
            z-index: 2;
        }
        .profile-main {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 26px;
        }
        .profile-kicker {
            color: var(--cyan);
            font-size: 12px;
            font-weight: 900;
            letter-spacing: 3px;
            text-transform: uppercase;
        }
        .profile-name {
            margin: 10px 0 14px;
            color: #fff;
            font-family: 'Rajdhani', sans-serif;
            font-size: 58px;
            line-height: .95;
            text-transform: uppercase;
        }
        .profile-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .profile-tag {
            display: inline-flex;
            align-items: center;
            min-height: 30px;
            padding: 6px 12px;
            color: #020617;
            background: var(--cyan);
            border-radius: 8px;
            font-size: 12px;
            font-weight: 900;
            text-transform: uppercase;
        }
        .profile-tag.gold { background: var(--gold); }
        .profile-tag.dark {
            color: var(--text);
            background: rgba(148,163,184,.12);
            border: 1px solid rgba(148,163,184,.18);
        }
        .profile-stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
        }
        .pro-stat {
            padding: 22px;
            background: rgba(30, 41, 59, 0.4);
            backdrop-filter: blur(5px);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            transition: 0.3s;
            position: relative;
            overflow: hidden;
        }
        .pro-stat:hover {
            border-color: var(--cyan-glow);
            background: rgba(56, 189, 248, 0.05);
            transform: translateY(-5px);
        }
        .pro-stat strong {
            display: block;
            color: #fff;
            font-family: 'Rajdhani', sans-serif;
            font-size: 30px;
            line-height: 1;
        }
        .pro-stat span {
            display: block;
            margin-top: 8px;
            color: var(--muted);
            font-size: 11px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .profile-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin: 28px 0;
        }
        .profile-section {
            margin-top: 30px;
        }
        .avatar-thumb {
            width: 44px;
            height: 52px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid rgba(56,189,248,.25);
        }
        @media (max-width: 1100px) {
            .profile-hero,
            .profile-stats,
            .profile-grid {
                grid-template-columns: 1fr;
            }
            .profile-name { font-size: 42px; }
        }
    </style>
</head>
<body>
<?php render_app_header('teams', [
    ['label' => 'Back to Teams', 'href' => 'teams.php', 'variant' => 'primary'],
    ['label' => 'Edit Player', 'href' => 'edit_player.php?id=' . intval($p['id'])]
]); ?>

<main class="wrapper" style="max-width:1180px;">
    <section class="profile-hero">
        <div class="profile-photo-card">
            <img src="<?= $avatar ?>" alt="<?= htmlspecialchars($p['name']) ?> profile picture">
            <div class="photo-caption">
                <div class="profile-kicker"><?= htmlspecialchars($p['short_name']) ?></div>
                <div class="table-title"><?= htmlspecialchars($p['team_name']) ?></div>
            </div>
        </div>

        <div class="profile-main">
            <div>
                <div class="profile-kicker">Professional Player Profile</div>
                <h1 class="profile-name"><?= htmlspecialchars($p['name']) ?></h1>
                <div class="profile-tags">
                    <span class="profile-tag"><?= htmlspecialchars($p['role']) ?></span>
                    <span class="profile-tag dark"><?= htmlspecialchars($p['team_name']) ?></span>
                    <?php if(intval($p['is_captain']) === 1): ?>
                        <span class="profile-tag gold">Captain</span>
                    <?php endif; ?>
                    <?php if($mvp_count > 0): ?>
                        <span class="profile-tag gold">MVP x<?= $mvp_count ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="profile-main-bottom">
                <div class="profile-stats">
                    <div class="pro-stat" style="border-left: 4px solid var(--cyan);"><strong><?= number_format($kda, 2) ?></strong><span>KDA Ratio</span></div>
                    <div class="pro-stat"><strong><?= $games ?></strong><span>Games Logged</span></div>
                    <div class="pro-stat"><strong><?= number_format($avg_kills, 1) ?></strong><span>Avg Kills</span></div>
                    <div class="pro-stat" style="border-left: 4px solid var(--gold);"><strong><?= number_format($avg_assists, 1) ?></strong><span>Avg Assists</span></div>
                </div>
            </div>
        </div>
    </section>

    <section class="profile-grid">
        <div class="stat-card"><div class="val"><?= number_format($kills) ?></div><div class="stat-label">Total Kills</div></div>
        <div class="stat-card"><div class="val"><?= number_format($assists) ?></div><div class="stat-label">Total Assists</div></div>
        <div class="stat-card"><div class="val"><?= number_format($stats['avg_damage']) ?></div><div class="stat-label">Avg Hero Damage</div></div>
        <div class="stat-card"><div class="val"><?= number_format($stats['avg_tf'], 1) ?>%</div><div class="stat-label">Team Fight</div></div>
    </section>

    <section class="profile-section">
        <div class="section-head">
            <div>
                <div class="section-label">Recent Match Stats</div>
                <div class="section-sub">Latest recorded heroes and performance numbers.</div>
            </div>
        </div>

        <div class="table-shell">
            <table class="tournament-table">
                <thead>
                    <tr>
                        <th>Match</th>
                        <th>Hero</th>
                        <th>K</th>
                        <th>D</th>
                        <th>A</th>
                        <th>Damage</th>
                        <th>TF%</th>
                        <th>Gold</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($match_history->num_rows === 0): ?>
                        <tr><td colspan="8" class="empty-cell">No match stats recorded for this player yet.</td></tr>
                    <?php endif; ?>

                    <?php while($row = $match_history->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <div class="table-title"><?= htmlspecialchars($row['team1'] ?? 'TBD') ?> vs <?= htmlspecialchars($row['team2'] ?? 'TBD') ?></div>
                                <div class="table-sub"><?= htmlspecialchars($row['round_name'] ?: $row['match_type']) ?></div>
                            </td>
                            <td><span class="status-badge status-default"><?= htmlspecialchars($row['hero_name']) ?></span></td>
                            <td><span class="number-pill"><?= intval($row['kills']) ?></span></td>
                            <td><span class="number-pill"><?= intval($row['deaths']) ?></span></td>
                            <td><span class="number-pill"><?= intval($row['assists']) ?></span></td>
                            <td><?= number_format($row['hero_damage']) ?></td>
                            <td><?= number_format($row['tf_participation'], 1) ?>%</td>
                            <td><?= number_format($row['total_gold']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<?php render_app_footer(); ?>
</body>
</html>
