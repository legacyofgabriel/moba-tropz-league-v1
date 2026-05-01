<?php
include("../config/db.php");
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include("../auth/auth_check.php");
include("../includes/header.php");
include("../includes/footer.php");

if(!isset($_SESSION['active_tournament'])){
    header("Location: ../dashboard/maindashboard.php");
    exit();
}

$tournament_id = intval($_SESSION['active_tournament']);

$t_info = $conn->query("SELECT name FROM tournaments WHERE id = $tournament_id")->fetch_assoc();

// Query para sa lahat ng players at MVP count nila
$mvp_query = $conn->query("
    SELECT p.id, p.name, p.role, p.photo_path, t.name as team_name, t.short_name,
           COUNT(m.mvp_player_id) as total_mvps
    FROM players p
    JOIN teams t ON p.team_id = t.id
    LEFT JOIN matches m ON p.id = m.mvp_player_id AND m.tournament_id = p.tournament_id
    WHERE p.tournament_id = $tournament_id
    GROUP BY p.id
    ORDER BY total_mvps DESC, p.name ASC
");

include_once("../includes/player_avatar.php");
include_once("../includes/player_photos.php");

$all_players = [];
if ($mvp_query) while($row = $mvp_query->fetch_assoc()) {
    $all_players[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MVP Leaderboard — MOBA TROPZ</title>
    <link rel="stylesheet" href="../dashboard/maindashboard.css">
    <style>
        .wrapper { 
            max-width: 1000px; 
            margin: 0 auto; 
            padding: 60px 20px;
            text-align: center;
        }

        .section-label {
            display: inline-block;
            color: var(--cyan);
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 8px;
            text-transform: uppercase;
            margin-bottom: 15px;
        }

        .tournament-title {
            font-family: 'Rajdhani', sans-serif;
            font-size: 56px;
            font-weight: 700;
            margin: 0 0 50px 0;
            text-shadow: 0 0 20px rgba(255,255,255,0.1);
        }

        /* MVP CARD STYLE */
        .mvp-card { 
            background: rgba(15, 23, 42, 0.6); 
            backdrop-filter: blur(12px); 
            border: 1px solid var(--border);
            border-radius: 16px; 
            border: 1px solid rgba(255,255,255,0.05);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            padding: 20px 35px;
            transition: 0.3s ease;
            text-align: left; /* Alignment fix for list items */
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .mvp-card:hover { 
            transform: scale(1.01) translateX(5px); 
            background: rgba(30, 41, 59, 0.7);
            border-color: var(--gold);
        }

        /* ── TOP 3 ELEVATION ── */
        .mvp-card.top-rank {
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.8), rgba(15, 23, 42, 0.9));
            border-width: 2px;
            margin-bottom: 20px;
        }
        .mvp-card.rank-1-card { 
            border-color: var(--gold); 
            box-shadow: 0 0 40px rgba(240, 180, 41, 0.1); 
            padding: 35px 40px; 
            margin-top: 20px;
        }
        .mvp-card.rank-2-card { border-color: #cbd5e1; box-shadow: 0 0 30px rgba(203, 213, 225, 0.05); }
        .mvp-card.rank-3-card { border-color: #fb923c; box-shadow: 0 0 30px rgba(251, 146, 60, 0.05); }

        .mvp-player-avatar {
            width: 70px;
            height: 80px;
            object-fit: cover;
            border-radius: 12px;
            border: 2px solid rgba(255,255,255,0.1);
            margin-right: 15px;
            background: var(--surface);
        }
        
        .rank { font-family: 'Rajdhani'; font-weight: 700; font-size: 32px; width: 60px; color: #475569; }
        .rank-1 { color: var(--gold); text-shadow: 0 0 15px rgba(240,180,41,0.6); font-size: 48px; }
        .rank-2 { color: #cbd5e1; text-shadow: 0 0 10px rgba(203,213,225,0.3); }
        .rank-3 { color: #fb923c; text-shadow: 0 0 8px rgba(251,146,60,0.3); }

        .player-info { flex-grow: 1; }
        .p-name { font-size: 20px; font-weight: 800; color: #fff; text-transform: uppercase; letter-spacing: 1px; }
        .p-team { font-size: 11px; color: var(--cyan); font-weight: 700; text-transform: uppercase; margin-top: 2px; }
        
        .mvp-count-box { text-align: center; min-width: 100px; border-left: 1px solid rgba(255,255,255,0.1); padding-left: 20px; }
        .count-val { font-family: 'Rajdhani'; font-size: 34px; font-weight: 900; color: var(--gold); display: block; line-height: 1; }
        .count-lab { font-size: 10px; color: var(--muted); font-weight: 800; letter-spacing: 1px; margin-top: 5px; display: block; }
        
        /* EMPTY STATE UI */
        .empty-state-card {
            background: rgba(15, 23, 42, 0.4);
            border: 1px dashed rgba(255,255,255,0.1);
            padding: 60px 40px;
            border-radius: 20px;
            display: inline-block;
            width: 100%;
            max-width: 500px;
        }
        .empty-state-card h3 { color: #fff; margin: 15px 0 5px; font-size: 20px; }
        .empty-state-card p { color: var(--muted); font-size: 14px; }
    </style>
</head>
<body>

<?php render_app_header('mvp'); ?>

<div class="wrapper">
    <div class="section-label">Tournament MVP Race</div>
    <h1 class="tournament-title"><?= strtoupper($t_info['name']) ?></h1>

    <?php if(empty($all_players)): ?>
        <div class="empty-state-card">
            <div style="font-size: 60px; filter: grayscale(1); opacity: 0.5;">👥</div>
            <h3>No Players Registered Yet</h3>
            <p>Register teams and players in the Teams section to see them here.</p>
            <a href="../teams/teams.php" style="color: var(--cyan); text-decoration:none; font-size:12px; font-weight:700; display:block; margin-top:20px;">GO TO TEAMS →</a>
        </div>
    <?php else: ?>
        <?php 
        $rank = 0;
        foreach($all_players as $m): 
            $rank++;
            $rank_class = ($rank == 1) ? 'rank-1' : (($rank == 2) ? 'rank-2' : (($rank == 3) ? 'rank-3' : ''));
            $top_class = ($rank <= 3) ? 'top-rank rank-' . $rank . '-card' : '';
            $fallback = function_exists('player_avatar_data_uri') ? player_avatar_data_uri($m['name'], $m['role']) : '';
            $avatar = player_photo_src($m, '../', $fallback);
    ?>
        <div class="mvp-card <?= $top_class ?>">
            <div class="rank <?= $rank_class ?>">#<?= $rank ?></div>
            <img src="<?= $avatar ?>" alt="<?= htmlspecialchars($m['name']) ?> avatar" class="mvp-player-avatar">
            <div class="player-info">
                <div class="p-name"><?= $m['name'] ?> <span style="font-size:12px; color:rgba(255,255,255,0.2); font-weight:400; margin-left:10px;">[<?= $m['role'] ?>]</span></div>
                <div class="p-team"><?= strtoupper($m['team_name']) ?></div>
            </div>
            <div class="mvp-count-box">
                <span class="count-val"><?= $m['total_mvps'] ?></span>
                <span class="count-lab">MVP TITLES</span>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php render_app_footer(); ?>

</body>
</html>
