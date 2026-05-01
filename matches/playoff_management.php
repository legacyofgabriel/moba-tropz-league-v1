<?php
include("../config/db.php");
if (session_status() === PHP_SESSION_NONE) { session_start(); }
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
clearPlayoffsIfTournamentNotReady($conn, $tournament_id, $state);
$state = getTournamentState($conn, $tournament_id);

$matches = $conn->query("SELECT m.*, t1.name as team1, t2.name as team2 FROM matches m 
                         LEFT JOIN teams t1 ON m.team1_id = t1.id 
                         LEFT JOIN teams t2 ON m.team2_id = t2.id 
                         WHERE m.tournament_id = $tournament_id AND m.match_type = 'Playoffs' ORDER BY m.id ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>MOBA TROPZ LEAGUE PLAY OFFS</title>
    <link rel="stylesheet" href="../dashboard/maindashboard.css">
    <link href="https://googleapis.com" rel="stylesheet">
    <style>
        .series-badge { padding: 4px 10px; border-radius: 4px; font-family: 'Rajdhani'; font-size: 11px; font-weight: 800; color: #000; }
        .bo1 { background: #94a3b8; } .bo3 { background: #38bdf8; } .bo5 { background: #a78bfa; } .bo7 { background: #facc15; }
        .score-input { width: 55px; background: #020617; border: 2px solid rgba(56, 189, 248, 0.2); color: #fff; text-align: center; font-size: 24px; font-family: 'Rajdhani'; border-radius: 8px; font-weight: 800; }
    </style>
</head>
<body>
<?php render_app_header('matches', [
    ['label' => $state['can_generate_playoffs'] ? 'Generate Bracket' : 'Bracket Locked', 'href' => 'generate_playoffs.php', 'variant' => 'gold', 'disabled' => !$state['can_generate_playoffs'], 'confirm' => 'Generate a fresh playoff bracket from current standings? Existing playoff matches will be replaced.'],
    ['label' => 'View Bracket', 'href' => 'playoffs.php', 'variant' => 'primary'],
    ['label' => 'Back to RR', 'href' => 'matches.php']
]); ?>
<div class="topbar legacy-hidden">
    <div class="topbar-logo">MOBA <span>TROPZ</span></div>
    <div class="topbar-sep"></div>
    <?php if($state['can_generate_playoffs']): ?>
        <a href="generate_playoffs.php" class="btn-logout" style="border-color: var(--gold); color: var(--gold); margin-right:10px;" onclick="return confirm('Generate a fresh playoff bracket from current standings? Existing playoff matches will be replaced.')">GENERATE BRACKET</a>
    <?php else: ?>
        <span class="btn-logout" style="border-color:#475569; color:#64748b; margin-right:10px; cursor:not-allowed;">GENERATE LOCKED</span>
    <?php endif; ?>
    <a href="playoffs.php" class="btn-logout" style="border-color: var(--cyan); color: var(--cyan); margin-right:10px;">VIEW BRACKET</a>
    <a href="matches.php" class="btn-logout">← BACK TO RR</a>
</div>

<div class="wrapper" style="max-width:95%;">
    <div class="section-label">MOBA TROPZ LEAGUE PLAY OFFS</div>

    <?php if(isset($_GET['error'])): ?>
        <div style="background:rgba(248,113,113,0.1); color:#f87171; padding:15px; border-radius:8px; margin-bottom:20px; text-align:center; border:1px solid #f87171;">
            ⚠️ <?= htmlspecialchars($_GET['error']) ?>
        </div>
    <?php endif; ?>

    <?php if(isset($_GET['msg'])): ?>
        <div style="background:rgba(74,222,128,0.1); color:#4ade80; padding:15px; border-radius:8px; margin-bottom:20px; text-align:center; border:1px solid #4ade80;">
            <?= htmlspecialchars($_GET['msg']) ?>
        </div>
    <?php endif; ?>

    <?php if(!$state['can_generate_playoffs']): ?>
        <div style="background:rgba(250,204,21,0.08); color:#facc15; padding:15px; border-radius:8px; margin-bottom:20px; border:1px solid rgba(250,204,21,0.4);">
            <div style="font-weight:800; margin-bottom:8px;">Playoffs are not ready</div>
            <?php foreach($state['messages'] as $message): ?>
                <div style="font-size:13px; margin-top:4px;"><?= htmlspecialchars($message) ?></div>
            <?php endforeach; ?>
            <?php if($state['playoff_matches'] === 0): ?>
                <div style="font-size:13px; margin-top:8px; color:#94a3b8;">Existing playoff bracket is empty or has been cleared because tournament data changed.</div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <table class="tournament-table" style="width:100%; border-collapse:separate; border-spacing:0 10px; text-align:center;">
        <thead>
            <tr style="color:rgba(255,255,255,0.5); font-size:11px;">
                <th style="width:250px;">STAGE & SERIES</th><th>BLUE SIDE</th><th>SERIES SCORE</th><th>RED SIDE</th><th>ACTION</th>
            </tr>
        </thead>
        <tbody>
            <?php if($matches->num_rows === 0): ?>
                <tr style="background:rgba(15, 23, 42, 0.7);">
                    <td colspan="5" style="padding:35px; color:#94a3b8;">No playoff bracket available.</td>
                </tr>
            <?php endif; ?>
            <?php while($m = $matches->fetch_assoc()): 
                $max_wins = ceil($m['series_limit'] / 2);
            ?>
            <tr style="background:rgba(15, 23, 42, 0.7); height:100px;">
                <form method="POST" action="update_match.php">
                    <input type="hidden" name="match_id" value="<?= $m['id'] ?>">
                    <td>
                        <div style="font-weight:800; color:var(--cyan); font-size:12px; margin-bottom:5px;"><?= strtoupper($m['round_name']) ?></div>
                        <span class="series-badge bo<?= $m['series_limit'] ?>">BEST OF <?= $m['series_limit'] ?></span>
                    </td>
                    <td style="font-weight:700;"><?= $m['team1'] ?? 'TBD' ?></td>
                    <td>
                        <?php if($m['is_locked']): ?>
                            <span style="font-size:30px; font-family:'Rajdhani'; font-weight:900; color:var(--gold);"><?= $m['score1'] ?> — <?= $m['score2'] ?></span>
                        <?php else: ?>
                            <div style="display:flex; align-items:center; justify-content:center; gap:10px;">
                                <input type="number" name="score1" value="0" class="score-input" min="0" max="<?= $max_wins ?>">
                                <span style="font-weight:900; color:#475569;">VS</span>
                                <input type="number" name="score2" value="0" class="score-input" min="0" max="<?= $max_wins ?>">
                            </div>
                        <?php endif; ?>
                    </td>
                    <td style="font-weight:700;"><?= $m['team2'] ?? 'TBD' ?></td>
                    <td>
                        <?php if(!$m['is_locked'] && $m['team1_id'] && $m['team2_id']): ?>
                            <button type="submit" name="update" class="btn-logout" style="border-color:var(--cyan); color:var(--cyan);">LOCK RESULT</button>
                        <?php elseif($m['is_locked']): ?>
                            <div style="display:flex; flex-direction:column; align-items:center; gap:8px;">
                                <span style="color:var(--gold); font-weight:800; font-size:11px;">🏆 COMPLETED</span>
                                <a href="input_player_stats.php?match_id=<?= $m['id'] ?>" class="btn-logout" style="border-color:var(--gold); color:var(--gold); text-decoration:none; padding:4px 8px; font-size:10px;">STATS</a>
                            </div>
                        <?php else: ?>
                            <span style="color:#475569; font-size:10px;">WAITING FOR TEAMS</span>
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
