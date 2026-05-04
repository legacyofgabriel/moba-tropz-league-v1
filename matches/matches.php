<?php
include("../config/db.php");
include("../auth/auth_check.php");
include("tournament_state.php");
include("../includes/header.php");
include("../includes/player_avatar.php"); // Needed for player_avatar_data_uri in match_intel.php if linked
include("../includes/footer.php");
include("../includes/team_photos.php");

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

$matches = $conn->query("SELECT m.*, t1.name as team1, t1.logo_path as logo1, t2.name as team2, t2.logo_path as logo2,
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

<?php
$match_stats = $conn->query("SELECT COUNT(*) as total, SUM(is_locked) as done FROM matches WHERE tournament_id=$tournament_id")->fetch_assoc();
?>

<div class="wrapper" style="max-width: 98%;">
    <div style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:30px; border-bottom: 1px solid var(--border); padding-bottom: 20px;">
        <div>
            <div class="section-label" style="margin-bottom:0;">ROUND ROBIN FIXTURES</div>
            <div style="font-size:11px; color:var(--muted); margin-top:5px; font-weight:800; letter-spacing:1px;">
                MISSION PROGRESS: <span style="color:var(--cyan);"><?= $match_stats['done'] ?? 0 ?> / <?= $match_stats['total'] ?? 0 ?> TOTAL MATCHES SECURED</span>
            </div>
        </div>
        <div style="display:flex; gap:10px;">
            <button onclick="filterMatches('all')" class="app-action" style="font-size:10px; padding:5px 15px; cursor:pointer;">ALL</button>
            <button onclick="filterMatches('pending')" class="app-action" style="font-size:10px; padding:5px 15px; cursor:pointer; color:var(--gold); border-color:var(--gold-glow);">PENDING</button>
            <button onclick="filterMatches('locked')" class="app-action" style="font-size:10px; padding:5px 15px; cursor:pointer; color:var(--cyan); border-color:var(--cyan-glow);">COMPLETED</button>
        </div>
    </div>

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
                <th>ID</th><th>#</th><th>Type</th><th>Blue Side</th><th>Score</th><th>Red Side</th><th>Outcome</th><th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if($matches->num_rows === 0): ?>
                <tr style="background:rgba(15,23,42,0.7);">
                    <td colspan="7" style="padding:35px; color:#94a3b8;">No Round Robin matches yet.</td>
                </tr>
            <?php else: ?>
                <tr style="background:rgba(15,23,42,0.7);">
                    <td colspan="7" style="padding:15px; color:var(--muted); font-size:11px; text-transform:uppercase; letter-spacing:1px;">
                        <i class="fas fa-info-circle" style="margin-right:8px; color:var(--cyan);"></i>
                        OPERATIONAL INTEL: ROUND ROBIN PHASE
                    </td>
                </tr>
            <?php endif; ?>
            <?php $n=1; while($m = $matches->fetch_assoc()): ?>
            <tr class="match-row" data-locked="<?= $m['is_locked'] ?>" style="background:rgba(2, 6, 23, 0.5); border-bottom: 1px solid var(--border); height:85px;">
                <form method="POST" action="update_match.php">
                    <?php 
                    $t1_win = ($m['is_locked'] && $m['winner_name'] == $m['team1']);
                    $t2_win = ($m['is_locked'] && $m['winner_name'] == $m['team2']);
                    $match_icon = ($m['match_type'] == 'Round Robin') ? 'fas fa-trophy' : 'fas fa-sitemap';
                    ?>
                    <input type="hidden" name="match_id" value="<?= $m['id'] ?>">
                    <td class="code-cell" style="font-size:14px;"><?= $m['id'] ?></td>
                    <td style="font-family:'Rajdhani'; font-size:20px;"><?= $n++ ?></td>
                    <td style="font-size:10px; color:#475569;"><i class="<?= $match_icon ?>" style="margin-right:5px;"></i><?= strtoupper($m['match_type']) ?></td>
                    <td style="font-weight:700; color: <?= $t1_win ? 'var(--cyan)' : 'inherit' ?>; opacity: <?= ($m['is_locked'] && !$t1_win) ? '0.4' : '1' ?>;">
                        <div style="display:flex; align-items:center; justify-content:center; gap:10px;">
                            <img src="<?= team_logo_src($m['logo1'], '../') ?>" style="width:30px; height:30px; object-fit:cover; border-radius:4px; border:1px solid var(--border);"> 
                            <?= strtoupper($m['team1']) ?>
                        </div>
                    </td>
                    <td style="background: #000;">
                        <div style="padding:10px; border:1px solid var(--cyan); display:inline-block; min-width:100px;">
                            <?php if($m['is_locked']): ?>
                                <span style="font-family:'Rajdhani'; font-size:26px; color:var(--cyan); font-weight:800;"><?= $m['score1'] ?> — <?= $m['score2'] ?></span>
                            <?php else: ?>
                                <div style="display:flex; align-items:center; justify-content:center; gap:5px;">
                                    <input type="number" name="score1" value="0" min="0" max="1" class="score-input-rr">
                                    <span style="color:var(--muted); font-weight:800;">:</span>
                                    <input type="number" name="score2" value="0" min="0" max="1" class="score-input-rr">
                                </div>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td style="font-weight:700; color: <?= $t2_win ? 'var(--cyan)' : 'var(--danger)' ?>; opacity: <?= ($m['is_locked'] && !$t2_win) ? '0.4' : '1' ?>;">
                        <div style="display:flex; align-items:center; justify-content:center; gap:10px;">
                            <?= strtoupper($m['team2']) ?>
                            <img src="<?= team_logo_src($m['logo2'], '../') ?>" style="width:30px; height:30px; object-fit:cover; border-radius:4px; border:1px solid var(--border);">
                        </div>
                    </td>
                    <td>
                        <span class="status-badge <?= $m['is_locked'] ? 'completed' : 'pending' ?>">
                            <?= $m['is_locked'] ? 'COMPLETED' : 'PENDING' ?>
                        </span>
                        <?php if($m['is_locked'] && $m['winner_name']): ?>
                            <div style="color:var(--gold); font-weight:800; font-size:10px; margin-top:5px;">WINNER: <?= strtoupper($m['winner_name']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if(!$m['is_locked']): ?>
                            <button type="submit" name="update" class="app-action primary" style="padding:8px 16px; font-size:11px; border-radius:8px;">SAVE</button>
                        <?php else: ?>
                            <a href="match_intel.php?match_id=<?= $m['id'] ?>" class="app-action" 
                               style="padding:8px 16px; font-size:11px; border-radius:8px;
                                      border-color:<?= $m['stats_ready'] > 0 ? 'var(--cyan)' : 'var(--gold)' ?>; color:<?= $m['stats_ready'] > 0 ? 'var(--cyan)' : 'var(--gold)' ?>;">
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
<script>
function filterMatches(status) {
    document.querySelectorAll('.match-row').forEach(row => {
        const isLocked = row.getAttribute('data-locked');
        if (status === 'all') row.style.display = '';
        else if (status === 'pending') row.style.display = (isLocked === '0') ? '' : 'none';
        else if (status === 'locked') row.style.display = (isLocked === '1') ? '' : 'none';
    });
}
</script>
<?php render_app_footer(); ?>
</body>
</html>
