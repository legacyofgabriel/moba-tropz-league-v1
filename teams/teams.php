<?php
include("../config/db.php");
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include("../auth/auth_check.php");
include("../includes/header.php");
include("../includes/footer.php");
include("../includes/player_avatar.php"); // For player avatar generation
include("../includes/player_photos.php"); // For player photo helpers
include("../includes/team_photos.php"); // For team_logo_src function

ensure_player_photo_column($conn);
ensure_team_logo_column($conn);

if(!isset($_SESSION['active_tournament'])){
    header("Location: ../dashboard/maindashboard.php");
    exit();
}

$tournament_id = intval($_SESSION['active_tournament']);

// LOGIC: DELETE TEAM/PLAYER (Same as before)
if (isset($_GET['delete_team_id'])) {
    if (!verify_csrf_token($_GET['token'] ?? '')) {
        header("Location: teams.php?error=Security token mismatch.");
        exit();
    }

    $tid = intval($_GET['delete_team_id']);
    $stmt = $conn->prepare("SELECT logo_path FROM teams WHERE id = ?");
    $stmt->bind_param("i", $tid);
    $stmt->execute();
    $team_data = $stmt->get_result()->fetch_assoc();
    delete_team_logo_file($team_data['logo_path'] ?? null);

    $stmt = $conn->prepare("SELECT photo_path FROM players WHERE team_id = ?");
    $stmt->bind_param("i", $tid);
    $stmt->execute();
    $photo_res = $stmt->get_result();
    while($photo = $photo_res->fetch_assoc()) {
        delete_player_photo_file($photo['photo_path'] ?? null);
    }
    
    $stmt1 = $conn->prepare("DELETE FROM player_match_stats WHERE tournament_id = ? AND player_id IN (SELECT id FROM players WHERE team_id = ?)");
    $stmt1->bind_param("ii", $tournament_id, $tid);
    $stmt1->execute();

    $stmt2 = $conn->prepare("DELETE FROM standings WHERE team_id = ? AND tournament_id = ?");
    $stmt2->bind_param("ii", $tid, $tournament_id);
    $stmt2->execute();

    $stmt3 = $conn->prepare("DELETE FROM players WHERE team_id = ? AND tournament_id = ?");
    $stmt3->bind_param("ii", $tid, $tournament_id);
    $stmt3->execute();

    $stmt4 = $conn->prepare("DELETE FROM teams WHERE id = ? AND tournament_id = ?");
    $stmt4->bind_param("ii", $tid, $tournament_id);
    $stmt4->execute();
    
    header("Location: teams.php"); exit();
}
if (isset($_GET['delete_player_id'])) {
    if (!verify_csrf_token($_GET['token'] ?? '')) {
        header("Location: teams.php?error=Security token mismatch.");
        exit();
    }

    $pid = intval($_GET['delete_player_id']);
    $stmt = $conn->prepare("SELECT photo_path FROM players WHERE id = ? AND tournament_id = ?");
    $stmt->bind_param("ii", $pid, $tournament_id);
    $stmt->execute();
    $photo = $stmt->get_result()->fetch_assoc();
    delete_player_photo_file($photo['photo_path'] ?? null);

    $stmt1 = $conn->prepare("DELETE FROM player_match_stats WHERE player_id = ? AND tournament_id = ?");
    $stmt1->bind_param("ii", $pid, $tournament_id);
    $stmt1->execute();

    $stmt2 = $conn->prepare("UPDATE matches SET mvp_player_id = NULL WHERE mvp_player_id = ? AND tournament_id = ?");
    $stmt2->bind_param("ii", $pid, $tournament_id);
    $stmt2->execute();

    $stmt3 = $conn->prepare("DELETE FROM players WHERE id = ? AND tournament_id = ?");
    $stmt3->bind_param("ii", $pid, $tournament_id);
    $stmt3->execute();

    header("Location: teams.php"); exit();
}

$stmt_t = $conn->prepare("SELECT name, team_count FROM tournaments WHERE id = ?");
$stmt_t->bind_param("i", $tournament_id);
$stmt_t->execute();
$t_info = $stmt_t->get_result()->fetch_assoc();

$stmt_c = $conn->prepare("SELECT COUNT(*) as total FROM teams WHERE tournament_id = ?");
$stmt_c->bind_param("i", $tournament_id);
$stmt_c->execute();
$count_res = $stmt_c->get_result()->fetch_assoc();

$current_teams = intval($count_res['total']);
$max_slots = intval($t_info['team_count']);

$stmt_teams = $conn->prepare("
    SELECT t.*, s.wins, s.points 
    FROM teams t 
    LEFT JOIN standings s ON t.id = s.team_id AND s.tournament_id = t.tournament_id
    WHERE t.tournament_id = ?
");
$stmt_teams->bind_param("i", $tournament_id);
$stmt_teams->execute();
$teams = $stmt_teams->get_result();

// Prepared statements for optimized inner loops
$stmt_pl_intel = $conn->prepare("SELECT name, role FROM players WHERE team_id = ? ORDER BY name ASC");
$stmt_pl_full = $conn->prepare("SELECT * FROM players WHERE team_id = ? ORDER BY is_captain DESC, name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Squad Management — MOBA TROPZ</title>
    <link rel="stylesheet" href="../dashboard/maindashboard.css">
    <style>
        /* Accordion specific styles */
        .chevron { transition: transform 0.3s ease; color: var(--muted); font-size: 12px; }
        .team-card.active .chevron { transform: rotate(180deg); color: var(--cyan); }
        .team-card.active { border-color: rgba(56, 189, 248, 0.4); box-shadow: 0 10px 30px rgba(0,0,0,0.4); }
        .team-card.active .team-body { max-height: 1000px; padding-bottom: 20px; }
        .team-logo-mini { width: 40px; height: 40px; border-radius: 8px; object-fit: cover; border: 1px solid var(--border); }
    </style>
</head>
<body>

<?php render_app_header('teams', [
    ['label' => '+ Register New Team', 'href' => 'add_team.php', 'variant' => 'primary', 'disabled' => $current_teams >= $max_slots]
]); ?>

<div class="wrapper">
    <!-- HERO SECTION -->
    <div class="hero" style="text-align: center; background: linear-gradient(180deg, rgba(15,23,42,0.8), rgba(15,23,42,0.4));">
        <div class="hero-label">SQUAD MANAGEMENT CENTER</div>
        <h1 class="hero-title"><?= strtoupper($t_info['name']) ?></h1>
        <div class="hero-meta" style="justify-content: center;">
            <span class="status-badge <?= ($current_teams >= $max_slots) ? 'status-default' : 'status-active' ?>">
                SLOTS: <?= $current_teams ?> / <?= $max_slots ?> TEAMS
            </span>
        </div>
    </div>

    <!-- Ibinabalik ang Inline Register Button -->
    <div style="margin-bottom: 25px; display:flex; justify-content:space-between; align-items:center;">
        <?php if ($current_teams < $max_slots): ?>
            <a href="add_team.php" class="app-action primary" style="padding: 12px 25px;">+ REGISTER NEW TEAM</a>
        <?php else: ?>
            <div class="status-badge status-default" style="padding: 10px 20px;">⚠️ TOURNAMENT SLOTS FULL</div>
        <?php endif; ?>
        <input type="text" id="squadFilter" placeholder="Live Squad/Player Filter..." 
               style="background:#000; border:1px solid var(--border); color:var(--cyan); padding:10px 20px; outline:none; font-family:'Space Grotesk'; width:300px;">
    </div>

    <div class="team-container">
        <?php $first = true; ?>
        <?php if($teams->num_rows === 0): ?>
            <div class="hero" style="text-align: center; border-style: dashed; opacity: 0.6;">
                <div class="hero-label">No Teams Found</div>
                <p class="table-sub">Start by registering the first squad for this tournament.</p>
            </div>
        <?php endif; ?>

        <?php while($team = $teams->fetch_assoc()): 
            $stmt_pl_intel->bind_param("i", $team['id']);
            $stmt_pl_intel->execute();
            $intel_res = $stmt_pl_intel->get_result();
            $player_list = $intel_res->fetch_all(MYSQLI_ASSOC);
            
            $p_count = count($player_list);
            $player_json = json_encode($player_list);
            
            $active_class = $first ? 'active' : '';
            $first = false;
        ?>
            <!-- TEAM CARD WITH ACCORDION -->
            <div class="team-card searchable-squad <?= $active_class ?>" id="team-<?= $team['id'] ?>" data-search="<?= strtolower($team['name'] . ' ' . $team['short_name']) ?>" style="margin-bottom: 10px;">
                <div class="team-header" onclick="toggleTeam(<?= $team['id'] ?>)">
                    <div class="team-info-main">
                        <?php $logo = isset($team['logo_path']) ? team_logo_src($team['logo_path'], '../') : '../assets/default_team.png'; ?>
                        <img src="<?= $logo ?>" class="team-logo-mini" onerror="this.src='../assets/default_team.png'">
                        <span class="team-tag-small"><?= strtoupper($team['short_name']) ?></span>
                        <a href="team_profile.php?id=<?= $team['id'] ?>" class="team-name-big" style="text-decoration:none;" onclick="event.stopPropagation()">
                            <?= strtoupper($team['name']) ?>
                        </a>
                        <span class="player-count-badge">• <?= $p_count ?> / 6 OPERATIVES</span>
                        <span class="status-badge" style="background:rgba(245, 158, 11, 0.1); color:var(--gold); border:1px solid var(--gold-glow); font-size:9px; height:20px; padding: 0 12px;">
                            <?= $team['wins'] ?? 0 ?>W — <?= $team['points'] ?? 0 ?> PTS
                        </span>
                        <?php if($p_count < 5): ?>
                            <span class="status-badge" style="background:rgba(248, 113, 113, 0.1); color:var(--danger); border:1px solid rgba(248,113,113,0.3); font-size:9px; height:20px; padding: 0 12px;">INCOMPLETE ROSTER</span>
                        <?php endif; ?>
                    </div>
                    <div class="chevron">▼</div>
                </div>

                <div class="team-body">
                    <div class="inner-content">
                        <!-- PLAYER LIST GRID -->
                        <div class="p-grid">
                            <?php
                            $stmt_pl_full->bind_param("i", $team['id']);
                            $stmt_pl_full->execute();
                            $players = $stmt_pl_full->get_result();
                            if($players->num_rows == 0):
                                echo "<div class='empty-cell' style='grid-column: 1/-1;'>No players registered in this squad.</div>";
                            endif;
                            while($p = $players->fetch_assoc()):
                            ?>
                                <div class="p-item searchable-player" data-player-name="<?= strtolower($p['name']) ?>">
                                    <div style="display:flex; align-items:center; gap:10px;">
                                        <?php $thumb = player_photo_src($p, '../', player_avatar_data_uri($p['name'], $p['role'])); ?>
                                        <img src="<?= $thumb ?>" alt="<?= htmlspecialchars($p['name']) ?> avatar" class="player-avatar-mini">
                                        <div style="display: flex; flex-direction: column;">
                                            <a href="player_profile.php?id=<?= $p['id'] ?>" class="player-profile-link" title="View player profile">
                                                <?= strtoupper($p['name']) ?> <?= ($p['is_captain']) ? '⭐' : '' ?>
                                            </a>
                                            <?php 
                                                $role_color = match($p['role']) {
                                                    'CORE' => '#f87171',
                                                    'ROAM' => 'var(--cyan)',
                                                    'MID' => '#a78bfa',
                                                    'GOLD' => 'var(--gold)',
                                                    'EXP' => '#4ade80',
                                                    default => 'var(--muted)'
                                                };
                                            ?>
                                            <span style="color:<?= $role_color ?>; font-size:9px; font-weight:800; letter-spacing: 1px;"><?= $p['role'] ?></span>
                                        </div>
                                    </div>
                                    <div style="display: flex; gap: 8px;">
                                        <a href="edit_player.php?id=<?= $p['id'] ?>" class="table-action muted" style="min-width: unset; padding: 4px 8px;" title="Edit Player">Edit</a>
                                        <a href="?delete_player_id=<?= $p['id'] ?>&token=<?= $_SESSION['csrf_token'] ?>" class="table-action muted" style="min-width: unset; padding: 4px 8px; color: var(--danger); border-color: rgba(248,113,113,0.2);" 
                                           onclick="return confirm('Remove player?')" title="Delete Player">
                                            &times;
                                        </a>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>

                        <!-- MANAGEMENT ACTIONS -->
                        <div class="manage-bar" style="display: flex; gap: 10px; margin-top: 25px; padding-top: 20px; border-top: 1px solid var(--border);">
                            <a href="add_player.php?team_id=<?= $team['id'] ?>" class="table-action" style="background:var(--cyan); color:#020617; border:none; padding: 8px 16px;">+ ADD PLAYER</a>
                            <a href="edit_team.php?id=<?= $team['id'] ?>" class="table-action muted" style="padding: 8px 16px;">EDIT TEAM</a>
                            <button onclick='copyRoster(this, "<?= addslashes($team['name']) ?>", <?= htmlspecialchars($player_json, ENT_QUOTES) ?>)' class="table-action muted" style="padding: 8px 16px; cursor:pointer; background:none;">COPY ROSTER</button>
                            <a href="?delete_team_id=<?= $team['id'] ?>&token=<?= $_SESSION['csrf_token'] ?>" class="table-action muted" style="padding: 8px 16px; color: var(--danger); border-color: rgba(248,113,113,0.2);" onclick="return confirm('Delete whole team?')">DELETE SQUAD</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<?php render_app_footer(); ?>

<script>
    function copyRoster(btn, teamName, players) {
        let text = `[ROSTER_TRANSMISSION]\nSQUAD: ${teamName.toUpperCase()}\n------------------------\n`;
          players.forEach(p => { text += `> ${p.name.toUpperCase()} | ${p.role}\n`; });
        text += `------------------------\nSTATUS: READY_FOR_DEPLOYMENT`;
        navigator.clipboard.writeText(text).then(() => {
            const original = btn.innerText;
            btn.innerText = "INTEL_COPIED";
            btn.style.color = "var(--cyan)";
            setTimeout(() => {
                btn.innerText = original; btn.style.color = "";
            }, 2000);
        });
    }

    document.getElementById('squadFilter').addEventListener('input', function(e) {
        const term = e.target.value.toLowerCase();
        document.querySelectorAll('.searchable-squad').forEach(card => {
            const squadInfo = card.getAttribute('data-search');
            // Deep search: tingnan din kung yung player name ay kasama sa search
            const playersInSquad = Array.from(card.querySelectorAll('.searchable-player'))
                                       .map(p => p.getAttribute('data-player-name'))
                                       .join(' ');
            card.style.display = (squadInfo.includes(term) || playersInSquad.includes(term)) ? '' : 'none';
        });
    });

    function toggleTeam(id) {
        const card = document.getElementById('team-' + id);
        const wasActive = card.classList.contains('active');

        // Isara ang lahat ng kasalukuyang nakabukas na team card
        document.querySelectorAll('.team-card').forEach(c => {
            c.classList.remove('active');
        });

        // Kung ang pinindot na card ay hindi active dati, buksan ito
        if (!wasActive) {
            card.classList.add('active');
        }
    }
</script>
</body>
</html>
