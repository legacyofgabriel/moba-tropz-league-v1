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

$tid = intval($_SESSION['active_tournament']);
$state = getTournamentState($conn, $tid);
clearPlayoffsIfTournamentNotReady($conn, $tid, $state);
$state = getTournamentState($conn, $tid);

function getM($conn, $tid, $round) {
    $round = mysqli_real_escape_string($conn, $round);
    $res = $conn->query("SELECT m.*, t1.name as t1, t2.name as t2 FROM matches m
                         LEFT JOIN teams t1 ON m.team1_id=t1.id
                         LEFT JOIN teams t2 ON m.team2_id=t2.id
                         WHERE m.tournament_id=$tid AND m.round_name='$round' LIMIT 1");
    return ($res && $res->num_rows > 0) ? $res->fetch_assoc() : null;
}

function renderMatch($m, $fallback1 = 'TBD', $fallback2 = 'TBD', $extraClass = '', $extraStyle = '') {
    $t1 = $m['t1'] ?? $fallback1;
    $t2 = $m['t2'] ?? $fallback2;
    $s1 = $m['score1'] ?? 0;
    $s2 = $m['score2'] ?? 0;
    $winner = $m['winner_name'] ?? '';
    ?>
    <div class="match-card <?= $extraClass ?>" style="<?= $extraStyle ?>">
        <div class="team-row <?= ($winner === $t1) ? 'winner' : '' ?>"><span><?= htmlspecialchars($t1) ?></span><span class="score"><?= $s1 ?></span></div>
        <div class="team-row <?= ($winner === $t2) ? 'winner' : '' ?>"><span><?= htmlspecialchars($t2) ?></span><span class="score"><?= $s2 ?></span></div>
    </div>
    <?php
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Official Bracket - MOBA TROPZ</title>
    <link rel="stylesheet" href="../dashboard/maindashboard.css">
    <style>
        /* Overall Layout */
        .wrapper { max-width: 1200px; margin: 0 auto; padding: 40px 20px; }
        h1 {
            text-align: center;
            font-family: 'Rajdhani', sans-serif;
            color: var(--text); /* Use var(--text) for consistency */
            font-size: 48px; /* Slightly larger */
            letter-spacing: 2px;
            margin-bottom: 50px; /* More space */
            text-shadow: 0 0 25px rgba(0,0,0,0.5);
        }

        /* Bracket Grid */
        .bracket-grid {
            display: grid;
            grid-template-columns: 320px 60px 320px 60px 320px; /* Wider cards, same gap */
            justify-content: center;
            align-items: center;
            padding: 60px 0;
            overflow-x: auto;
            position: relative; /* For absolute positioning of lines */
        }

        /* Round Labels */
        .round-label {
            font-family: 'Rajdhani', sans-serif;
            font-weight: 700;
            font-size: 14px; /* Slightly larger */
            text-transform: uppercase;
            text-align: center;
            display: block;
            margin-bottom: 20px; /* More space */
            letter-spacing: 3px; /* More pronounced */
            color: var(--muted); /* Default color */
            text-shadow: 0 0 10px rgba(0,0,0,0.3);
        }
        .round-label[style*="var(--cyan)"] { color: var(--cyan) !important; text-shadow: 0 0 15px var(--cyan-glow); }
        .round-label[style*="#f87171"] { color: var(--danger) !important; text-shadow: 0 0 15px rgba(239, 68, 68, 0.25); }
        .round-label[style*="var(--gold)"] { color: var(--gold) !important; font-size: 16px; text-shadow: 0 0 20px var(--gold-glow); }

        /* Match Card */
        .match-card { background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border); backdrop-filter: blur(12px); border-radius: 16px; padding: 20px; margin: 15px 0; transition: all 0.3s ease-in-out; position: relative; z-index: 10; box-shadow: 0 8px 20px rgba(0,0,0,0.3); }
        .match-card:hover { border-color: var(--cyan); transform: translateY(-5px) scale(1.01); box-shadow: 0 15px 30px rgba(0,0,0,0.5), 0 0 25px var(--cyan-glow); }

        /* Team Row */
        .team-row { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; font-size: 15px; font-weight: 600; color: var(--text); gap: 16px; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .team-row:last-child { border-bottom: none; }
        .team-row span:first-child { flex-grow: 1; text-overflow: ellipsis; overflow: hidden; white-space: nowrap; }

        /* Score */
        .score { font-family: 'Rajdhani', sans-serif; color: var(--cyan); font-size: 22px; font-weight: 900; min-width: 30px; text-align: right; }

        /* Winner */
        .winner { color: var(--gold) !important; text-shadow: 0 0 15px var(--gold-glow); font-weight: 800; }
        .winner .score { color: var(--gold) !important; }

        /* Grand Final Box */
        .gf-box { border: 2px solid var(--gold) !important; transform: scale(1.05); box-shadow: 0 0 50px var(--gold-glow); background: rgba(251, 191, 36, 0.08); }
        .gf-box:hover { transform: scale(1.08) translateY(-5px); box-shadow: 0 0 60px var(--gold-glow), 0 15px 30px rgba(0,0,0,0.5); }

        /* Bracket Lines (Simplified Visual Flow) */
        .bracket-grid > div:nth-child(2n) { /* Selects the empty columns for lines */
            position: relative;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .bracket-grid > div:nth-child(2n)::before {
            content: '';
            position: absolute;
            width: 2px;
            height: 80%; /* Adjust height to connect cards visually */
            background: rgba(148, 163, 184, 0.1); /* Subtle line color */
            border-radius: 1px;
            box-shadow: 0 0 8px rgba(0,0,0,0.2);
        }

        /* Responsive adjustments for bracket grid */
        @media (max-width: 1200px) {
            .bracket-grid {
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); /* Stack on smaller screens */
                gap: 20px;
            }
            .bracket-grid > div:nth-child(2n) { /* Hide vertical lines on stacked layout */
                display: none;
            }
            .round {
                padding: 0 20px; /* Add some padding to rounds */
            }
        }

        /* Champion Trophy Section */
        .champion-container {
            margin-top: 50px;
            text-align: center;
            animation: trophyEntrance 1.2s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            perspective: 1000px;
        }
        @keyframes trophyEntrance {
            0% { opacity: 0; transform: translateY(40px) scale(0.8); }
            100% { opacity: 1; transform: translateY(0) scale(1); }
        }
        .trophy-glow {
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            width: 300px; height: 300px;
            background: radial-gradient(circle, var(--gold-glow) 0%, transparent 70%);
            z-index: -1;
            animation: pulseGlow 3s infinite alternate;
        }
        @keyframes pulseGlow {
            0% { opacity: 0.5; transform: translate(-50%, -50%) scale(1); }
            100% { opacity: 1; transform: translate(-50%, -50%) scale(1.3); }
        }
        .champ-team-name {
            font-family: 'Rajdhani', sans-serif;
            font-size: 42px; font-weight: 800;
            color: #fff; text-transform: uppercase;
            margin-top: -20px; letter-spacing: 4px;
            text-shadow: 0 0 20px var(--gold-glow), 0 0 40px rgba(0,0,0,0.5);
        }
        .champ-label {
            font-family: 'Rajdhani', sans-serif;
            color: var(--gold); font-size: 14px; font-weight: 700;
            letter-spacing: 10px; text-transform: uppercase; margin-top: 10px;
        }
    </style>
</head>
<body>
    <?php render_app_header('matches', [
        ['label' => 'Playoff Control', 'href' => 'playoff_management.php', 'variant' => 'primary']
    ]); ?>

    <div class="wrapper" style="max-width: 1200px;">
        <h1 style="text-align:center; font-family:'Rajdhani'; color:var(--text); font-size:48px; letter-spacing: 2px; margin-bottom: 50px; text-shadow: 0 0 25px rgba(0,0,0,0.5);">MOBA TROPZ LEAGUE PLAY OFFS</h1>
        <?php if(!$state['can_generate_playoffs'] || $state['playoff_matches'] === 0): ?>
            <div style="background:rgba(250,204,21,0.08); color:#facc15; padding:18px; border-radius:8px; margin:30px auto; border:1px solid rgba(250,204,21,0.4); max-width:800px;">
                <div style="font-weight:800; margin-bottom:8px;">No active playoff bracket</div>
                <?php if($state['playoff_matches'] === 0): ?>
                    <div style="font-size:13px;">Bracket is empty. Finish Round Robin and generate playoffs from Playoff Management.</div>
                <?php endif; ?>
                <?php foreach($state['messages'] as $message): ?>
                    <div style="font-size:13px; margin-top:4px;"><?= htmlspecialchars($message) ?></div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
        <div class="bracket-grid">
            <div class="round">
                <span class="round-label" style="color:var(--cyan);">Upper Semis (BO3)</span>
                <?php renderMatch(getM($conn, $tid, 'Upper Bracket Semi Final 1')); ?>
                <?php renderMatch(getM($conn, $tid, 'Upper Bracket Semi Final 2')); ?>

                <div style="height: 50px;"></div>
                <span class="round-label" style="color:#f87171;">Lower Semis (BO1)</span>
                <?php renderMatch(getM($conn, $tid, 'Lower Bracket Semi Final 1'), 'UBS1 Loser', 'Seed 5', '', 'border-color: rgba(248,113,113,0.3);'); ?>
                <?php renderMatch(getM($conn, $tid, 'Lower Bracket Semi Final 2'), 'UBS2 Loser', 'Seed 6', '', 'border-color: rgba(248,113,113,0.3);'); ?>
            </div>

            <div></div>

            <div class="round">
                <span class="round-label" style="color:var(--cyan);">Upper Final (BO5)</span>
                <?php renderMatch(getM($conn, $tid, 'Upper Bracket Final')); ?>

                <div style="height: 100px;"></div>
                <span class="round-label" style="color:#f87171;">Lower Final (BO3)</span>
                <?php renderMatch(getM($conn, $tid, 'Lower Bracket Final'), 'TBD', 'UBF Loser', '', 'border-color: rgba(248,113,113,0.3);'); ?>
            </div>

            <div></div>

            <div class="round">
                <span class="round-label" style="color:var(--gold); font-size:14px;">Grand Final (BO7)</span>
                <?php 
                    $gf = getM($conn, $tid, 'Grand Final');
                    renderMatch($gf, 'UB Champ', 'LB Champ', 'gf-box'); 
                ?>
            </div>
        </div>

        <?php if ($gf && !empty($gf['winner_name'])): ?>
            <div class="champion-container">
                <div style="position: relative; display: inline-block;">
                    <div class="trophy-glow"></div>
                    <!-- Custom M-Series Style Trophy SVG -->
                    <svg width="240" height="240" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="filter: drop-shadow(0 0 15px var(--gold-glow));">
                        <path d="M12 2L14.5 9H9.5L12 2Z" fill="#FDE68A"/>
                        <path d="M6 9H18V12C18 15.3137 15.3137 18 12 18C8.68629 18 6 15.3137 6 12V9Z" fill="url(#trophy_grad)" stroke="#FB injection" stroke-width="0.5"/>
                        <path d="M12 18V22M8 22H16" stroke="#FB injection" stroke-width="2" stroke-linecap="round"/>
                        <path d="M6 10C4.34315 10 3 11.3431 3 13C3 14.6569 4.34315 16 6 16V10ZM18 10V16C19.6569 16 21 14.6569 21 13C21 11.3431 19.6569 10 18 10Z" fill="#F59E0B"/>
                        <defs>
                            <linearGradient id="trophy_grad" x1="12" y1="9" x2="12" y2="18" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#FBBF24"/>
                                <stop offset="1" stop-color="#B45309"/>
                            </linearGradient>
                        </defs>
                    </svg>
                </div>
                <div class="champ-team-name"><?= htmlspecialchars($gf['winner_name']) ?></div>
                <div class="champ-label">CHAMPION</div>
                <div style="color: var(--muted); font-size: 11px; margin-top: 5px; font-weight: 600; letter-spacing: 2px;">
                    <?= htmlspecialchars($state['tournament']['name']) ?> • <?= htmlspecialchars($state['tournament']['tournament_code']) ?>
                </div>
            </div>
        <?php endif; ?>

        <?php endif; ?>
    </div>
    <?php render_app_footer(); ?>
</body>
</html>
