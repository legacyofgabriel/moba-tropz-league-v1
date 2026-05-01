<?php
include("../config/db.php");
include("../auth/auth_check.php");
include("../includes/header.php");
include("../includes/footer.php");

if(!isset($_SESSION['active_tournament'])){
    header("Location: ../dashboard/maindashboard.php");
    exit();
}

$tournament_id = intval($_SESSION['active_tournament']);

$all_teams = $conn->query("SELECT id FROM teams WHERE tournament_id = $tournament_id");
while($t = $all_teams->fetch_assoc()) {
    $tid = intval($t['id']);
    $check = $conn->query("SELECT id FROM standings WHERE team_id = $tid AND tournament_id = $tournament_id");
    if($check->num_rows == 0) {
        $conn->query("INSERT INTO standings (tournament_id, team_id, played, wins, losses, points) VALUES ($tournament_id, $tid, 0, 0, 0, 0)");
    }
}

$standings = $conn->query("
    SELECT s.*, t.name, t.short_name
    FROM standings s
    JOIN teams t ON s.team_id = t.id
    WHERE s.tournament_id = $tournament_id
    ORDER BY points DESC, wins DESC, (wins - losses) DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Standings - MOBA TROPZ</title>
    <link rel="stylesheet" href="../dashboard/maindashboard.css">
</head>
<body>
<?php render_app_header('standings'); ?>

<div class="wrapper">
    <div class="section-head">
        <div>
            <div class="section-label">League Leaderboard</div>
            <div class="section-sub">Current Round Robin standings for the active tournament.</div>
        </div>
        <a href="matches.php" class="section-action">View Matches</a>
    </div>

    <div class="table-shell">
        <table class="tournament-table">
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Team</th>
                    <th>Played</th>
                    <th>Wins</th>
                    <th>Losses</th>
                    <th>Points</th>
                </tr>
            </thead>
            <tbody>
                <?php $rank = 1; ?>
                <?php if($standings->num_rows === 0): ?>
                    <tr>
                        <td colspan="6" class="empty-cell">No standings data found. Generate matches first.</td>
                    </tr>
                <?php endif; ?>

                <?php while($s = $standings->fetch_assoc()): ?>
                    <tr>
                        <td class="code-cell">#<?= $rank++ ?></td>
                        <td>
                            <div class="table-title"><?= htmlspecialchars($s['name']) ?></div>
                            <div class="table-sub">TAG: <?= htmlspecialchars(strtoupper($s['short_name'])) ?></div>
                        </td>
                        <td><span class="number-pill"><?= intval($s['played']) ?></span></td>
                        <td><span class="number-pill"><?= intval($s['wins']) ?></span></td>
                        <td><span class="number-pill"><?= intval($s['losses']) ?></span></td>
                        <td><span class="status-badge status-active"><?= intval($s['points']) ?> PTS</span></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php render_app_footer(); ?>
</body>
</html>
