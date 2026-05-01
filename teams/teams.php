<?php
include("../config/db.php");
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include("../auth/auth_check.php");
include("../includes/header.php");
include("../includes/footer.php");
include("../includes/player_avatar.php");
include("../includes/player_photos.php");

ensure_player_photo_column($conn);
ensure_team_logo_column($conn);

if(!isset($_SESSION['active_tournament'])){
    header("Location: ../dashboard/maindashboard.php");
    exit();
}

$tournament_id = intval($_SESSION['active_tournament']);

// LOGIC: DELETE TEAM/PLAYER (Same as before)
if (isset($_GET['delete_team_id'])) {
    $tid = intval($_GET['delete_team_id']);
    $team_data = $conn->query("SELECT logo_path FROM teams WHERE id = $tid")->fetch_assoc();
    delete_team_logo_file($team_data['logo_path'] ?? null);

    $photo_res = $conn->query("SELECT photo_path FROM players WHERE team_id = $tid");
    while($photo = $photo_res->fetch_assoc()) {
        delete_player_photo_file($photo['photo_path'] ?? null);
    }
    
    $conn->query("DELETE FROM player_match_stats WHERE tournament_id = $tournament_id AND player_id IN (SELECT id FROM players WHERE team_id = $tid)");
    $conn->query("DELETE FROM standings WHERE team_id = $tid AND tournament_id = $tournament_id");
    $conn->query("DELETE FROM players WHERE team_id = $tid AND tournament_id = $tournament_id");
    $conn->query("DELETE FROM teams WHERE id = $tid AND tournament_id = $tournament_id");
    
    header("Location: teams.php"); exit();
}
if (isset($_GET['delete_player_id'])) {
    $pid = intval($_GET['delete_player_id']);
    $photo = $conn->query("SELECT photo_path FROM players WHERE id = $pid AND tournament_id = $tournament_id")->fetch_assoc();
    delete_player_photo_file($photo['photo_path'] ?? null);
    $conn->query("DELETE FROM player_match_stats WHERE player_id = $pid AND tournament_id = $tournament_id");
    $conn->query("UPDATE matches SET mvp_player_id = NULL WHERE mvp_player_id = $pid AND tournament_id = $tournament_id");
    $conn->query("DELETE FROM players WHERE id = $pid AND tournament_id = $tournament_id");
    header("Location: teams.php"); exit();
}

$t_info = $conn->query("SELECT name, team_count FROM tournaments WHERE id=$tournament_id")->fetch_assoc();
$count_res = $conn->query("SELECT COUNT(*) as total FROM teams WHERE tournament_id=$tournament_id")->fetch_assoc();
$current_teams = intval($count_res['total']);
$max_slots = intval($t_info['team_count']);
$teams = $conn->query("SELECT * FROM teams WHERE tournament_id=$tournament_id");
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
    <div style="margin-bottom: 25px;">
        <?php if ($current_teams < $max_slots): ?>
            <a href="add_team.php" class="app-action primary" style="padding: 12px 25px;">+ REGISTER NEW TEAM</a>
        <?php else: ?>
            <div class="status-badge status-default" style="padding: 10px 20px;">⚠️ TOURNAMENT SLOTS FULL</div>
        <?php endif; ?>
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
            $p_count = $conn->query("SELECT COUNT(*) as total FROM players WHERE team_id={$team['id']}")->fetch_assoc()['total'];
            $active_class = $first ? 'active' : '';
            $first = false;
        ?>
            <!-- TEAM CARD WITH ACCORDION -->
            <div class="team-card <?= $active_class ?>" id="team-<?= $team['id'] ?>" style="margin-bottom: 10px;">
                <div class="team-header" onclick="toggleTeam(<?= $team['id'] ?>)">
                    <div class="team-info-main">
                        <?php $logo = isset($team['logo_path']) ? team_logo_src($team['logo_path'], '../') : '../assets/default_team.png'; ?>
                        <img src="<?= $logo ?>" class="team-logo-mini" onerror="this.src='../assets/default_team.png'">
                        <span class="team-tag-small"><?= strtoupper($team['short_name']) ?></span>
                        <span class="team-name-big"><?= strtoupper($team['name']) ?></span>
                        <span class="player-count-badge">• <?= $p_count ?> / 6 PLAYERS</span>
                    </div>
                    <div class="chevron">▼</div>
                </div>

                <div class="team-body">
                    <div class="inner-content">
                        <!-- PLAYER LIST GRID -->
                        <div class="p-grid">
                            <?php
                            $players = $conn->query("SELECT * FROM players WHERE team_id={$team['id']} ORDER BY is_captain DESC, name ASC");
                            if($players->num_rows == 0):
                                echo "<div class='empty-cell' style='grid-column: 1/-1;'>No players registered in this squad.</div>";
                            endif;
                            while($p = $players->fetch_assoc()):
                            ?>
                                <div class="p-item">
                                    <div style="display:flex; align-items:center; gap:10px;">
                                        <?php $thumb = player_photo_src($p, '../', player_avatar_data_uri($p['name'], $p['role'])); ?>
                                        <img src="<?= $thumb ?>" alt="<?= htmlspecialchars($p['name']) ?> avatar" class="player-avatar-mini">
                                        <div style="display: flex; flex-direction: column;">
                                            <a href="player_profile.php?id=<?= $p['id'] ?>" class="player-profile-link" title="View player profile">
                                                <?= strtoupper($p['name']) ?> <?= ($p['is_captain']) ? '⭐' : '' ?>
                                            </a>
                                            <span style="color:var(--muted); font-size:9px; font-weight:800; letter-spacing: 1px;"><?= $p['role'] ?></span>
                                        </div>
                                    </div>
                                    <div style="display: flex; gap: 8px;">
                                        <a href="edit_player.php?id=<?= $p['id'] ?>" class="table-action muted" style="min-width: unset; padding: 4px 8px;" title="Edit Player">Edit</a>
                                        <a href="?delete_player_id=<?= $p['id'] ?>" class="table-action muted" style="min-width: unset; padding: 4px 8px; color: var(--danger); border-color: rgba(248,113,113,0.2);" 
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
                            <a href="?delete_team_id=<?= $team['id'] ?>" class="table-action muted" style="padding: 8px 16px; color: var(--danger); border-color: rgba(248,113,113,0.2);" onclick="return confirm('Delete whole team?')">DELETE SQUAD</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<?php render_app_footer(); ?>

<script>
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
