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
    $res = $conn->query("SELECT * FROM tournaments WHERE id = $selected_id");
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
    <div class="hero">
        <?php if ($active_tournament): ?>
            <div class="hero-main">
                <div>
                    <div class="hero-label" style="letter-spacing: 4px; font-size: 11px; color: var(--cyan); font-weight: 800;">CURRENTLY MANAGING</div>
                    <h1 class="hero-title"><?= htmlspecialchars(strtoupper($active_tournament['name'])) ?></h1>
                    <div class="hero-meta">
                        <span class="hero-code"><?= htmlspecialchars($active_tournament['tournament_code']) ?></span>
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
        <div class="stat-card">
            <span class="stat-label">Teams</span>
            <strong><?= $total_teams ?></strong>
        </div>
        <div class="stat-card">
            <span class="stat-label">Players</span>
            <strong><?= $total_players ?></strong>
        </div>
    </div>

    <div class="section-head">
        <div>
            <div class="section-label">Created Tournaments</div>
            <div class="section-sub">Open a tournament to make it active, or edit its setup.</div>
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
                    <tr class="<?= $is_active ? 'active-row' : '' ?>">
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
                            <a href="../tournament/delete_tournament.php?id=<?= $t['id'] ?>" 
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
</body>
</html>
