<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include("../config/db.php");
include("../auth/auth_check.php");
include("../includes/header.php");
include("../includes/footer.php");

// Centralized Selection Logic
$selected_id = null;
if (isset($_GET['id'])) {
    $selected_id = intval($_GET['id']);
    $_SESSION['active_tournament'] = $selected_id;
}

$selected_id = isset($_SESSION['active_tournament']) ? intval($_SESSION['active_tournament']) : null;

if ($selected_id) {
    $stmt = $conn->prepare("SELECT * FROM tournaments WHERE id = ?");
    $stmt->bind_param("i", $selected_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $active_tournament = $res ? $res->fetch_assoc() : null;
} else {
    $active_tournament = null;
}

$tournaments_query = $conn->query("
    SELECT t.*,
           COUNT(DISTINCT tm.id) AS registered_teams,
           COUNT(DISTINCT p.id) AS registered_players
    FROM tournaments t
    LEFT JOIN teams tm ON tm.tournament_id = t.id
    LEFT JOIN players p ON p.tournament_id = t.id
    GROUP BY t.id
    ORDER BY t.id DESC
");

$tournaments = [];
while($row = $tournaments_query->fetch_assoc()) {
    $tournaments[] = $row;
}

$total_tournaments = count($tournaments);
$total_teams = 0;
$total_players = 0;
foreach($tournaments as $t) {
    $total_teams += intval($t['registered_teams']);
    $total_players += intval($t['registered_players']);
}

// Enhancement: Standings Snapshot logic
$standings_snapshot = [];
if ($selected_id) {
    $ss_stmt = $conn->prepare("
        SELECT s.*, t.name as team_name, t.short_name 
        FROM standings s 
        JOIN teams t ON s.team_id = t.id 
        WHERE s.tournament_id = ? 
        ORDER BY s.points DESC, s.wins DESC 
        LIMIT 3
    ");
    $ss_stmt->bind_param("i", $selected_id);
    $ss_stmt->execute();
    $ss_query = $ss_stmt->get_result();
    while($row = $ss_query->fetch_assoc()) {
        $standings_snapshot[] = $row;
    }
}

// Enhancement: Recent Results logic
$recent_results = [];
if ($selected_id) {
    $recent_stmt = $conn->prepare("
        SELECT m.*, t1.name as t1, t2.name as t2, t1.short_name as s1, t2.short_name as s2
        FROM matches m
        JOIN teams t1 ON m.team1_id = t1.id
        JOIN teams t2 ON m.team2_id = t2.id
        WHERE m.tournament_id = ? AND m.is_locked = 1
        ORDER BY m.id DESC
        LIMIT 3
    ");
    $recent_stmt->bind_param("i", $selected_id);
    $recent_stmt->execute();
    $recent_query = $recent_stmt->get_result();
    while($row = $recent_query->fetch_assoc()) {
        $recent_results[] = $row;
    }
}

// Enhancement: Tournament Completion Rate
$completion_rate = 0;
if ($selected_id) {
    $progress_query = $conn->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN is_locked = 1 THEN 1 ELSE 0 END) as completed
        FROM matches WHERE tournament_id = ?
    ");
    $progress_query->bind_param("i", $selected_id);
    $progress_query->execute();
    $prog = $progress_query->get_result()->fetch_assoc();
    $completion_rate = $prog['total'] > 0 ? round(($prog['completed'] / $prog['total']) * 100) : 0;
}

// Feature: Tournament Analytics (Leaders)
$top_slayers = [];
$hero_meta = [];
if ($selected_id) {
    // Top Performers by Kills
    $slayer_stmt = $conn->prepare("
        SELECT p.name, t.short_name, SUM(pms.kills) as total_kills, AVG(pms.total_gold) as avg_gold
        FROM player_match_stats pms
        JOIN players p ON pms.player_id = p.id
        JOIN teams t ON p.team_id = t.id
        WHERE pms.tournament_id = ?
        GROUP BY p.id
        ORDER BY total_kills DESC
        LIMIT 5
    ");
    $slayer_stmt->bind_param("i", $selected_id);
    $slayer_stmt->execute();
    $top_slayers = $slayer_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Hero Meta Distribution
    $hero_stmt = $conn->prepare("
        SELECT hero_name, COUNT(*) as picks, 
               ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM player_match_stats WHERE tournament_id = ?)), 1) as pick_rate
        FROM player_match_stats
        WHERE tournament_id = ?
        GROUP BY hero_name
        ORDER BY picks DESC
        LIMIT 5
    ");
    $hero_stmt->bind_param("ii", $selected_id, $selected_id);
    $hero_stmt->execute();
    $hero_meta = $hero_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Enhancement: Mission Record (Highest Kills in a single match)
    $record_stmt = $conn->prepare("
        SELECT p.name, MAX(pms.kills) as max_k 
        FROM player_match_stats pms 
        JOIN players p ON pms.player_id = p.id 
        WHERE pms.tournament_id = ? 
        GROUP BY p.id ORDER BY max_k DESC LIMIT 1
    ");
    $record_stmt->bind_param("i", $selected_id);
    $record_stmt->execute();
    $mission_record = $record_stmt->get_result()->fetch_assoc();

    // Feature: Overall Tournament KDA
    $overall_tournament_kda = ['k' => 0, 'd' => 0, 'a' => 0, 'ratio' => 'N/A'];
    $overall_kda_stmt = $conn->prepare("
        SELECT SUM(pms.kills) as total_k, SUM(pms.deaths) as total_d, SUM(pms.assists) as total_a
        FROM player_match_stats pms
        WHERE pms.tournament_id = ?
    ");
    $overall_kda_stmt->bind_param("i", $selected_id);
    $overall_kda_stmt->execute();
    $overall_kda_res = $overall_kda_stmt->get_result()->fetch_assoc();
    if ($overall_kda_res) {
        $overall_tournament_kda['k'] = $overall_kda_res['total_k'];
        $overall_tournament_kda['d'] = $overall_kda_res['total_d'];
        $overall_tournament_kda['a'] = $overall_kda_res['total_a'];
        if ($overall_kda_res['total_d'] > 0) {
            $overall_tournament_kda['ratio'] = number_format(($overall_kda_res['total_k'] + $overall_kda_res['total_a']) / $overall_kda_res['total_d'], 2);
        } else if ($overall_kda_res['total_k'] + $overall_kda_res['total_a'] > 0) {
             $overall_tournament_kda['ratio'] = number_format($overall_kda_res['total_k'] + $overall_kda_res['total_a'], 2);
        }
    }

    // Feature: Top Roles
    $top_roles = [];
    $top_roles_stmt = $conn->prepare("
        SELECT p.role, COUNT(p.id) as role_count
        FROM players p WHERE p.tournament_id = ?
        GROUP BY p.role ORDER BY role_count DESC LIMIT 3
    ");
    $top_roles_stmt->bind_param("i", $selected_id);
    $top_roles_stmt->execute();
    $top_roles = $top_roles_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Feature: Mission Highs (Superlatives) - Highest Kills Match
    $lethal_match_stmt = $conn->prepare("
        SELECT m.id, SUM(pms.kills) as total_kills, SUM(pms.hero_damage) as total_dmg,
               t1.short_name as s1, t2.short_name as s2
        FROM player_match_stats pms
        JOIN matches m ON pms.match_id = m.id
        JOIN teams t1 ON m.team1_id = t1.id
        JOIN teams t2 ON m.team2_id = t2.id
        WHERE pms.tournament_id = ?
        GROUP BY m.id ORDER BY total_kills DESC LIMIT 1
    ");
    $lethal_match_stmt->bind_param("i", $selected_id);
    $lethal_match_stmt->execute();
    $lethal_match = $lethal_match_stmt->get_result()->fetch_assoc();

    // Feature: Mission Highs (Superlatives)
    $superlative_stmt = $conn->prepare("
        SELECT m.id, SUM(pms.kills) as total_kills, SUM(pms.hero_damage) as total_dmg,
               t1.short_name as s1, t2.short_name as s2
        FROM player_match_stats pms
        JOIN matches m ON pms.match_id = m.id
        JOIN teams t1 ON m.team1_id = t1.id
        JOIN teams t2 ON m.team2_id = t2.id
        WHERE pms.tournament_id = ?
        GROUP BY m.id ORDER BY total_kills DESC LIMIT 1
    ");
    // This query was redundant with lethal_match_stmt, so I'll replace it with a query for highest damage match.
    $heavy_artillery_match_stmt = $conn->prepare("
        SELECT m.id, SUM(pms.kills) as total_kills, SUM(pms.hero_damage) as total_dmg,
               t1.short_name as s1, t2.short_name as s2
        FROM player_match_stats pms
        JOIN matches m ON pms.match_id = m.id
        JOIN teams t1 ON m.team1_id = t1.id
        JOIN teams t2 ON m.team2_id = t2.id
        WHERE pms.tournament_id = ?
        GROUP BY m.id ORDER BY total_dmg DESC LIMIT 1
    ");
    $heavy_artillery_match_stmt->bind_param("i", $selected_id);
    $heavy_artillery_match_stmt->execute();
    $heavy_artillery_match = $heavy_artillery_match_stmt->get_result()->fetch_assoc();

    // Feature: Live Match Ticker Data
    $ticker_matches = [];
    $ticker_stmt = $conn->prepare("
        SELECT m.*, t1.name as t1, t2.name as t2, t1.short_name as s1, t2.short_name as s2
        FROM matches m
        JOIN teams t1 ON m.team1_id = t1.id
        JOIN teams t2 ON m.team2_id = t2.id
        WHERE m.tournament_id = ?
        ORDER BY m.id DESC
        LIMIT 15
    ");
    $ticker_stmt->bind_param("i", $selected_id);
    $ticker_stmt->execute();
    $ticker_matches = array_reverse($ticker_stmt->get_result()->fetch_all(MYSQLI_ASSOC)); // Reverse to show oldest first for ticker flow
}

// Enhancement: Upcoming Operations
$upcoming_matches = [];
if ($selected_id) {
    $up_query = $conn->prepare("
        SELECT m.*, t1.name as t1, t2.name as t2, t1.short_name as s1, t2.short_name as s2
        FROM matches m
        JOIN teams t1 ON m.team1_id = t1.id
        JOIN teams t2 ON m.team2_id = t2.id
        WHERE m.tournament_id = ? AND m.is_locked = 0
        ORDER BY m.id ASC LIMIT 3
    ");
    $up_query->bind_param("i", $selected_id);
    $up_query->execute();
    $upcoming_matches = $up_query->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Enhancement: Tactical Audit Log
$audit_logs = [];
if ($selected_id) {
    $audit_query = $conn->prepare("
        SELECT l.*, u.username FROM tactical_audit_logs l 
        JOIN users u ON l.user_id = u.id WHERE l.tournament_id = ? 
        ORDER BY l.id DESC LIMIT 5
    ");
    $audit_query->bind_param("i", $selected_id);
    $audit_query->execute();
    $audit_logs = $audit_query->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard — MOBA TROPZ</title>
    <link rel="stylesheet" href="maindashboard.css">
    <style>
        :root {
            --glass: rgba(15, 23, 42, 0.7);
            --glass-border: rgba(255, 255, 255, 0.08);
            --accent-gradient: linear-gradient(135deg, #0ea5e9 0%, #6366f1 100%);
        }

        .hero {
            background: linear-gradient(to right, rgba(2, 6, 23, 0.9), rgba(2, 6, 23, 0.4)), 
                        url('https://images.unsplash.com/photo-1542751371-adc38448a05e?q=80&w=2070') center/cover;
            border-radius: 24px;
            border: 1px solid var(--glass-border);
            padding: 40px;
            margin-bottom: 30px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.3);
        }

        .hero-title {
            font-size: 48px;
            letter-spacing: -1px;
            background: linear-gradient(to bottom, #fff 40%, #94a3b8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin: 10px 0;
        }

        .stat-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: var(--glass);
            backdrop-filter: blur(12px);
            border: 1px solid var(--glass-border);
            padding: 25px;
            border-radius: 20px;
            transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            border-color: var(--cyan);
            box-shadow: 0 10px 30px rgba(14, 165, 233, 0.15);
        }

        .stat-card strong {
            font-size: 32px;
            font-family: 'Rajdhani', sans-serif;
            color: #fff;
            display: block;
            margin-top: 10px;
        }

        .table-shell {
            background: var(--glass);
            border-radius: 24px;
            border: 1px solid var(--glass-border);
            padding: 10px;
            overflow: hidden;
        }

        .tournament-table th {
            background: rgba(255,255,255,0.02);
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 1px;
            color: #64748b;
            padding: 20px;
        }
    </style>
</head>
<body>

<?php render_app_header('dashboard'); ?>

<div class="wrapper">
    <div style="display:flex; justify-content:flex-end; margin-bottom:15px; opacity:0.7;">
        <div id="tactical-clock" style="font-family:'Courier New', monospace; font-size:12px; color:var(--cyan); border:1px solid var(--cyan-glow); padding:6px 18px; background:#000;">
            SYS_TIME: [ LOADING... ]
        </div>
    </div>

    <script>
        // Auto-dismiss alerts
        setTimeout(() => {
            document.querySelectorAll('.tactical-alert').forEach(el => {
                el.style.opacity = '0';
                setTimeout(() => el.remove(), 500);
            });
        }, 5000);

        // Tactical Clock Sync
        setInterval(() => {
            const now = new Date();
            const timeStr = now.toTimeString().split(' ')[0];
            document.getElementById('tactical-clock').innerText = `SYS_TIME: ${timeStr} UTC`;
        }, 1000);
    </script>

    <?php if(isset($_GET['msg'])): ?>
        <div class="tactical-alert success">SYSTEM_MSG: <?= htmlspecialchars($_GET['msg']) ?></div>
    <?php endif; ?>
    <?php if(isset($_GET['error'])): ?>
        <div class="tactical-alert error">SYSTEM_ERR: <?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>

    <div class="hero">
        <?php if ($active_tournament): ?>
            <div class="hero-main">
                <div>
                    <div class="hero-label" style="letter-spacing: 4px; font-size: 11px; color: var(--cyan); font-weight: 800;">CURRENTLY MANAGING</div>
                    <h1 class="hero-title"><?= htmlspecialchars(strtoupper($active_tournament['name'])) ?></h1>
                    <div class="hero-meta">
                        <span class="hero-code" style="cursor:pointer;" title="Click to copy" onclick="copyToClipboard('<?= htmlspecialchars($active_tournament['tournament_code']) ?>', this)">
                            <?= htmlspecialchars($active_tournament['tournament_code']) ?>
                            <small style="margin-left:8px; opacity:0.6; font-size:9px;">(COPY)</small>
                        </span>
                        <span><?= htmlspecialchars($active_tournament['format_type']) ?></span>
                        <span><?= intval($active_tournament['team_count']) ?> teams</span>
                    </div>
                </div>
                <a href="../tournament/edit_tournament.php?id=<?= $active_tournament['id'] ?>"
                   class="btn-logout btn-accent" style="padding: 15px 30px; border-radius: 14px; font-weight: 800; letter-spacing: 1px;">
                   CONFIGURE
                </a>
            </div>
        <?php else: ?>
            <div class="hero-no-tournament">Please select a tournament</div>
        <?php endif; ?>
    </div>

    <div class="stat-grid">
        <div class="stat-card">
            <span class="stat-label">Tournaments</span>
            <strong><?= $total_tournaments ?></strong>
        </div>
        <div class="stat-card" style="border-right: 4px solid var(--cyan);">
            <span class="stat-label">Mission Progress</span>
            <strong class="<?= $completion_rate < 100 ? 'status-active' : '' ?>" style="display:inline-block;"><?= $completion_rate ?>%</strong>
        </div>
        <div class="stat-card" style="border-right: 4px solid var(--danger);">
            <span class="stat-label">Lethal Engagement (Match High)</span>
            <strong><?= $lethal_match ? $lethal_match['total_kills'] . " KILLS — " . $lethal_match['s1'] . " vs " . $lethal_match['s2'] : "N/A" ?></strong>
        </div>
        <div class="stat-card" style="border-right: 4px solid var(--gold);">
            <span class="stat-label">Heavy Artillery (Match High DMG)</span>
            <strong><?= $heavy_artillery_match ? number_format($heavy_artillery_match['total_dmg']) . " DMG — " . $heavy_artillery_match['s1'] . " vs " . $heavy_artillery_match['s2'] : "N/A" ?></strong>
        </div>
        <div class="stat-card" style="border-left: 4px solid var(--gold);">
            <span class="stat-label">Mission Record (Single Match Max Kills)</span>
            <strong><?= $mission_record ? $mission_record['max_k'] . " — " . strtoupper($mission_record['name']) : "0 — N/A" ?></strong>
        </div>
    </div>

    <?php if ($active_tournament): ?>
    <div class="section-label">Tournament Overview</div>
    <div class="stat-grid" style="grid-template-columns: repeat(3, 1fr); margin-bottom: 50px;">
        <div class="stat-card" style="border-left: 4px solid var(--cyan);">
            <span class="stat-label">Total Engagements</span>
            <strong><?= $prog['total'] ?? 0 ?></strong>
            <span style="font-size:11px; color:var(--muted); font-weight:800; letter-spacing:1px; margin-top:5px; display:block;">MATCHES PLAYED</span>
        </div>
        <div class="stat-card" style="border-left: 4px solid var(--gold);">
            <span class="stat-label">Overall KDA Ratio</span>
            <strong><?= $overall_tournament_kda['ratio'] ?></strong>
            <span style="font-size:11px; color:var(--muted); font-weight:800; letter-spacing:1px; margin-top:5px; display:block;">(<?= $overall_tournament_kda['k'] ?>K / <?= $overall_tournament_kda['d'] ?>D / <?= $overall_tournament_kda['a'] ?>A)</span>
        </div>
        <div class="stat-card" style="border-left: 4px solid var(--danger);">
            <span class="stat-label">Most Played Roles</span>
            <div style="margin-top:5px;">
                <?php if (!empty($top_roles)): ?>
                    <?php foreach ($top_roles as $role_data): ?>
                        <span style="font-size:14px; font-weight:800; display:block;"><?= strtoupper($role_data['role']) ?> <small style="color:var(--muted);">(<?= $role_data['role_count'] ?> Players)</small></span>
                    <?php endforeach; ?>
                <?php else: ?>
                    <span style="font-size:14px; color:var(--muted);">N/A</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($active_tournament && !empty($upcoming_matches)): ?>
    <div class="section-label">Upcoming Operations (Pending Deployment)</div>
    <div class="stat-grid" style="grid-template-columns: repeat(3, 1fr); margin-bottom: 50px;">
        <?php foreach($upcoming_matches as $m): ?>
            <div class="stat-card" style="border-bottom: 2px solid var(--gold-glow); background: rgba(245, 158, 11, 0.02);">
                <span class="stat-label"><?= strtoupper($m['match_type']) ?> — ID: <?= $m['id'] ?></span>
                <div style="display:flex; justify-content:space-between; align-items:center; margin-top:10px;">
                    <span style="font-weight:700; font-size:14px;"><?= $m['s1'] ?></span>
                    <span style="color:var(--muted); font-size:10px; font-weight:800;">VS</span>
                    <span style="font-weight:700; font-size:14px; color:var(--danger);"><?= $m['s2'] ?></span>
                </div>
                <div style="margin-top:15px; display:flex; justify-content:center;">
                    <a href="../matches/matches.php" class="table-action" style="font-size:9px; border-color:var(--gold); color:var(--gold);">DEPLOY SCOUT</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($active_tournament && !empty($standings_snapshot)): ?>
    <div class="section-head">
        <div class="section-label">Standings Snapshot (Top 3)</div>
        <a href="../matches/standings.php" class="section-action" style="background:none; border:1px solid var(--border); color:var(--muted); font-size:10px;">VIEW FULL TABLE</a>
    </div>
    <div class="stat-grid" style="grid-template-columns: repeat(3, 1fr); margin-bottom: 50px;">
        <?php foreach($standings_snapshot as $index => $s): ?>
            <div class="stat-card" style="border-left: 4px solid <?= $index === 0 ? 'var(--gold)' : 'var(--cyan)' ?>;">
                <span class="stat-label">RANK <?= $index + 1 ?> — <?= htmlspecialchars($s['short_name']) ?></span>
                <div style="display:flex; justify-content:space-between; align-items:flex-end;">
                    <a href="../teams/team_profile.php?id=<?= $s['team_id'] ?>" style="text-decoration:none; color:inherit;"><strong style="font-size: 20px;"><?= htmlspecialchars($s['team_name']) ?></strong></a>
                    <span style="font-family:'Rajdhani'; color:var(--cyan); font-weight:800; font-size:18px;">
                        <?= $s['points'] ?> PTS
                    </span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($active_tournament && !empty($recent_results)): ?>
    <div class="section-label">Recent Combat Results</div>
    <div class="table-shell" style="margin-bottom: 50px;">
        <table class="tournament-table">
            <tbody>
                <?php foreach($recent_results as $match): ?>
                <tr>
                    <td style="width:100px; color:var(--muted); font-size:10px; font-weight:800;"><?= strtoupper($match['match_type']) ?></td>
                    <td style="text-align:right; font-weight:700; width:30%;">
                        <a href="../teams/team_profile.php?id=<?= $match['team1_id'] ?>" style="color:inherit; text-decoration:none;"><?= strtoupper($match['t1']) ?></a>
                    </td>
                    <td style="text-align:center; width:150px;">
                        <span style="font-family:'Rajdhani'; font-size:24px; color:var(--cyan); font-weight:800;">
                            <?= $match['score1'] ?> — <?= $match['score2'] ?>
                        </span>
                    </td>
                    <td style="text-align:left; font-weight:700; width:30%; color:var(--danger);">
                        <a href="../teams/team_profile.php?id=<?= $match['team2_id'] ?>" style="color:inherit; text-decoration:none;"><?= strtoupper($match['t2']) ?></a>
                    </td>
                    <td style="text-align:right;">
                        <a href="../matches/match_intel.php?match_id=<?= $match['id'] ?>" class="table-action" style="font-size:10px;">VIEW INTEL</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php if ($active_tournament && !empty($top_slayers)): ?>
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px; margin-bottom: 50px;">
        <!-- Tournament Leaders -->
        <div>
            <div class="section-label">Top Performers (Total Kills)</div>
            <div class="table-shell">
                <table class="tournament-table">
                    <thead>
                        <tr><th>Player</th><th>Team</th><th>Kills</th><th>Avg Gold</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach($top_slayers as $ps): ?>
                        <tr>
                            <td style="font-weight:800; color:#fff;"><?= htmlspecialchars($ps['name']) ?></td>
                            <td style="color:var(--cyan);"><?= htmlspecialchars($ps['short_name']) ?></td>
                            <td style="font-family:'Rajdhani'; font-size:18px; color:var(--gold);"><?= $ps['total_kills'] ?></td>
                            <td style="font-size:12px; opacity:0.7;"><?= number_format($ps['avg_gold']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Hero Meta -->
        <div>
            <div class="section-label">Hero Meta Intel (Pick/Win)</div>
            <div class="table-shell" style="padding: 20px;">
                <?php foreach($hero_meta as $hero): ?>
                <div style="margin-bottom:15px;">
                    <div style="display:flex; justify-content:space-between; margin-bottom:5px; font-size:12px;">
                        <span style="font-weight:800;"><?= strtoupper($hero['hero_name']) ?> <small style="color:var(--muted); font-weight:400; margin-left:5px;">(<?= $hero['picks'] ?> PICKS)</small></span>
                        <span style="color:var(--cyan);"><?= $hero['pick_rate'] ?>% PR | <span style="color:var(--gold);"><?= $hero['win_rate'] ?>% WR</span></span>
                    </div>
                    <div class="slot-bar"><span style="width: <?= $hero['pick_rate'] ?>%; background: var(--cyan);"></span></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="section-head">
        <div>
            <div class="section-label">Created Tournaments</div>
            <div class="section-sub">Filter: <input type="text" id="tournamentSearch" placeholder="Search league name..." 
                style="background:transparent; border:none; border-bottom:1px solid var(--border); color:var(--cyan); padding:2px 10px; outline:none; font-size:12px;"></div>
        </div>
        <a href="../tournament/create.php" class="section-action">New Tournament</a>
    </div>
    <div class="table-shell">
        <table class="tournament-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Tournament</th>
                    <th>Format</th>
                    <th>Teams</th>
                    <th>Players</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($tournaments) === 0): ?>
                    <tr>
                        <td colspan="7" class="empty-cell">No tournaments created yet.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach($tournaments as $t): ?>
                    <?php
                        $is_active = intval($selected_id) === intval($t['id']);
                        $team_slots = intval($t['registered_teams']) . " / " . intval($t['team_count']);
                        $team_percent = intval($t['team_count']) > 0 ? min(100, round((intval($t['registered_teams']) / intval($t['team_count'])) * 100)) : 0;
                    ?>
                    <tr class="tournament-row <?= $is_active ? 'active-row' : '' ?>" data-name="<?= strtolower($t['name']) ?>">
                        <td class="code-cell"><?= htmlspecialchars($t['tournament_code']) ?></td>
                        <td>
                            <div class="table-title"><?= htmlspecialchars($t['name']) ?></div>
                            <div class="table-sub"><?= htmlspecialchars($t['organizer']) ?></div>
                        </td>
                        <td><?= htmlspecialchars($t['format_type']) ?></td>
                        <td>
                            <div class="slot-cell">
                                <span><?= $team_slots ?></span>
                                <div class="slot-bar"><span style="width: <?= $team_percent ?>%;"></span></div>
                            </div>
                        </td>
                        <td><span class="number-pill"><?= intval($t['registered_players']) ?></span></td>
                        <td>
                            <span class="status-badge <?= $is_active ? 'status-active' : 'status-default' ?>">
                                <?= $is_active ? 'Active' : htmlspecialchars($t['status']) ?>
                            </span>
                        </td>
                        <td class="action-cell">
                            <a href="../tournament/edit_tournament.php?id=<?= $t['id'] ?>" class="table-action">Edit</a>
                            <a href="../tournament/delete_tournament.php?id=<?= $t['id'] ?>&token=<?= $_SESSION['csrf_token'] ?>" 
                               class="table-action table-action-danger" 
                               onclick="return confirm('Sigurado ka bang buburahin ang tournament na ito? Kasama ang lahat ng teams, players, at matches nito. HINDI ITO MAIBABALIK!')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php render_app_footer(); ?>
<script>
document.getElementById('tournamentSearch').addEventListener('input', function(e) {
    const term = e.target.value.toLowerCase();
    document.querySelectorAll('.tournament-row').forEach(row => {
        const name = row.getAttribute('data-name');
        row.style.display = name.includes(term) ? '' : 'none';
    });
});

// Live Match Ticker Dynamic Speed
document.addEventListener('DOMContentLoaded', function() {
    const tickerContent = document.querySelector('.match-ticker-content');
    if (tickerContent) {
        // Duplicate content to ensure continuous loop without jump
        tickerContent.innerHTML += tickerContent.innerHTML; 

        // Calculate duration based on content width and desired speed (e.g., 50px/second)
        // We need to wait for layout to render to get accurate scrollWidth
        setTimeout(() => {
            const contentWidth = tickerContent.scrollWidth / 2; // Original content width
            const containerWidth = tickerContent.parentElement.clientWidth;
            
            if (contentWidth > containerWidth) {
                const speed = 50; // pixels per second
                const duration = (contentWidth / speed); // seconds
                tickerContent.style.animationDuration = `${duration}s`;
            } else {
                // If content is shorter than container, no need to scroll, just center
                tickerContent.style.animation = 'none';
                tickerContent.style.justifyContent = 'center';
                tickerContent.style.paddingLeft = '0'; // Remove initial padding
            }
        }, 100); // Small delay to ensure rendering
    }
});

function copyToClipboard(text, el) {
    navigator.clipboard.writeText(text).then(() => {
        const original = el.innerHTML;
        el.innerHTML = "CODE COPIED!";
        el.style.color = "var(--gold)";
        el.style.borderColor = "var(--gold)";
        setTimeout(() => { el.innerHTML = original; el.style.color = ""; el.style.borderColor = ""; }, 2000);
    });
}
</script>

<?php if ($active_tournament && !empty($ticker_matches)): ?>
<div class="match-ticker-container">
    <div class="match-ticker-content">
        <?php foreach ($ticker_matches as $match): ?>
            <span class="ticker-item">
                <span class="ticker-status <?= $match['is_locked'] ? 'completed' : 'pending' ?>">
                    <?= $match['is_locked'] ? 'COMPLETED' : 'PENDING' ?>
                </span>
                <span class="ticker-teams">
                    <?= strtoupper($match['s1']) ?>
                    <?php if ($match['is_locked']): ?>
                        <span class="ticker-score"><?= $match['score1'] ?> — <?= $match['score2'] ?></span>
                    <?php else: ?>
                        <span class="ticker-vs">VS</span>
                    <?php endif; ?>
                    <?= strtoupper($match['s2']) ?>
                </span>
                <?php if ($match['is_locked'] && $match['winner_name']): ?>
                    <span class="ticker-winner">WIN: <?= strtoupper($match['winner_name']) ?></span>
                <?php endif; ?>
            </span>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>
</body>
</html>
