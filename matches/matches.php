<?php
include("../config/db.php");
include("../auth/auth_check.php");
include("tournament_state.php");
include("../includes/header.php");
include("../includes/footer.php");

if(!isset($_SESSION['active_tournament'])){
    header("Location: ../dashboard/maindashboard.php");
    exit();
}

$tournament_id = intval($_SESSION['active_tournament']);
$state = getTournamentState($conn, $tournament_id);

// Optimization: Only perform repair if there are actually locked matches missing winner names
$repair_needed = $conn->query("SELECT id FROM matches WHERE tournament_id=$tournament_id AND is_locked=1 AND (winner_name IS NULL OR winner_name = '') LIMIT 1");
if($repair_needed->num_rows > 0) {
    $conn->query("UPDATE matches m
                  JOIN teams t1 ON m.team1_id = t1.id
                  JOIN teams t2 ON m.team2_id = t2.id
                  SET m.winner_name = CASE WHEN m.score1 > m.score2 THEN t1.name ELSE t2.name END, m.status='completed'
                  WHERE m.tournament_id=$tournament_id AND m.is_locked=1 AND (m.winner_name IS NULL OR m.winner_name = '')");
}

$matches = $conn->query("SELECT m.*, t1.name as team1, t2.name as team2,
                         (SELECT COUNT(*) FROM player_match_stats pms WHERE pms.match_id = m.id) as stats_ready
                         FROM matches m
                         JOIN teams t1 ON m.team1_id = t1.id
                         JOIN teams t2 ON m.team2_id = t2.id
                         WHERE m.tournament_id=$tournament_id AND m.match_type='Round Robin'
                         ORDER BY m.id ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Live Fixtures - MOBA TROPZ</title>
    <link rel="stylesheet" href="../dashboard/maindashboard.css">
</head>
<body>
<?php render_app_header('matches', [
    ['label' => 'Manage Playoffs', 'href' => 'playoff_management.php', 'variant' => 'primary'],
    ['label' => $state['can_generate_round_robin'] ? 'Generate RR' : 'RR Locked', 'href' => 'generate.php', 'variant' => 'gold', 'disabled' => !$state['can_generate_round_robin']],
    ['label' => 'Clear', 'href' => 'clear_matches.php', 'variant' => 'danger', 'confirm' => 'Clear all matches and stats for this tournament?']
]); ?>

<div class="wrapper" style="max-width: 98%;">
    <div class="section-label">ROUND ROBIN FIXTURES</div>

    <?php if(isset($_GET['error'])): ?>
        <div style="background:rgba(248,113,113,0.1); color:#f87171; padding:15px; border-radius:8px; margin-bottom:20px; text-align:center; border:1px solid #f87171;">
            <?= htmlspecialchars($_GET['error']) ?>
        </div>
    <?php endif; ?>

    <?php if(isset($_GET['msg'])): ?>
        <div style="background:rgba(74,222,128,0.1); color:#4ade80; padding:15px; border-radius:8px; margin-bottom:20px; text-align:center; border:1px solid #4ade80;">
            <?= htmlspecialchars($_GET['msg']) ?>
        </div>
    <?php endif; ?>

    <?php if(!$state['can_generate_round_robin'] || $state['rr_stale'] > 0): ?>
        <div style="background:rgba(250,204,21,0.08); color:#facc15; padding:15px; border-radius:8px; margin-bottom:20px; border:1px solid rgba(250,204,21,0.4);">
            <div style="font-weight:800; margin-bottom:8px;">Action needed before generating matches</div>
            <?php foreach($state['messages'] as $message): ?>
                <?php if(str_contains($message, 'Team slots') || str_contains($message, 'Incomplete roster') || str_contains($message, 'missing/deleted')): ?>
                    <div style="font-size:13px; margin-top:4px;"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <table class="tournament-table" style="width:100%; border-collapse:collapse; text-align:center; border: 1px solid var(--border);">
        <thead>
            <tr style="color:var(--cyan); text-transform:uppercase; font-size:12px; background:#000;">
                <th>#</th><th>Type</th><th>Blue Side</th><th>Score</th><th>Red Side</th><th>Outcome</th><th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if($matches->num_rows === 0): ?>
                <tr style="background:rgba(15,23,42,0.7);">
                    <td colspan="7" style="padding:35px; color:#94a3b8;">No Round Robin matches yet.</td>
                </tr>
            <?php endif; ?>
            <?php $n=1; while($m = $matches->fetch_assoc()): ?>
            <tr style="background:rgba(2, 6, 23, 0.5); border-bottom: 1px solid var(--border); height:85px;">
                <form method="POST" action="update_match.php">
                    <input type="hidden" name="match_id" value="<?= $m['id'] ?>">
                    <td style="font-family:'Rajdhani'; font-size:20px;"><?= $n++ ?></td>
                    <td style="font-size:10px; color:#475569;"><?= strtoupper($m['match_type']) ?></td>
                    <td style="font-weight:700;"><?= strtoupper($m['team1']) ?></td>
                    <td style="background: #000;">
                        <div style="padding:10px; border:1px solid var(--cyan); display:inline-block; min-width:100px;">
                            <?php if($m['is_locked']): ?>
                                <span style="font-family:'Rajdhani'; font-size:26px; color:var(--cyan); font-weight:800;"><?= $m['score1'] ?> — <?= $m['score2'] ?></span>
                            <?php else: ?>
                                <input type="number" name="score1" value="0" min="0" max="1" style="width:30px; background:none; border:none; color:#fff; text-align:center; font-weight:900; font-size:18px;">
                                <span style="color:#475569;">:</span>
                                <input type="number" name="score2" value="0" min="0" max="1" style="width:30px; background:none; border:none; color:#fff; text-align:center; font-weight:900; font-size:18px;">
                            <?php endif; ?>
                        </div>
                    </td>
                    <td style="font-weight:700; color:#f87171;"><?= strtoupper($m['team2']) ?></td>
                    <td style="color:var(--gold); font-weight:800; font-size:11px;"><?= $m['winner_name'] ? "WINNER: ".strtoupper($m['winner_name']) : "PENDING" ?></td>
                    <td>
                        <?php if(!$m['is_locked']): ?>
                            <button type="submit" name="update" class="btn-logout" style="border-color:var(--cyan); color:var(--cyan);">SAVE</button>
                        <?php else: ?>
                            <a href="input_player_stats.php?match_id=<?= $m['id'] ?>" class="btn-logout" 
                               style="border-color:<?= $m['stats_ready'] > 0 ? 'var(--cyan)' : 'var(--gold)' ?>; 
                                      color:<?= $m['stats_ready'] > 0 ? 'var(--cyan)' : 'var(--gold)' ?>; 
                                      text-decoration:none;">
                                <?= $m['stats_ready'] > 0 ? 'VIEW STATS' : 'ADD STATS' ?>
                            </a>
                        <?php endif; ?>
                    </td>
                </form>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
<?php render_app_footer(); ?>
</body>
</html>
