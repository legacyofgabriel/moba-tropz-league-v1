<?php

if (!function_exists('render_app_header')) {
    function render_app_header(string $active = 'dashboard', array $actions = []): void
    {
        $root = "/moba-tropz-league-v1/";
        $nav_items = [
            'dashboard' => ['label' => 'Dashboard', 'href' => $root . 'dashboard/maindashboard.php'],
            'teams' => ['label' => 'Teams', 'href' => $root . 'teams/teams.php'],
            'matches' => ['label' => 'Matches', 'href' => $root . 'matches/matches.php'],
            'standings' => ['label' => 'Standings', 'href' => $root . 'matches/standings.php'],
            'logo-maker' => ['label' => 'Logo Maker', 'href' => $root . 'teams/logo_maker.php'],
            'ai-gen' => ['label' => 'Profile Creator', 'href' => $root . 'teams/prompt_generator.php'],
            'mvp' => ['label' => 'MVP', 'href' => $root . 'matches/mvp_leaderboard.php'],
        ];
        ?>
        <style>
            .app-nav-link {
                position: relative;
                padding: 10px 15px;
                transition: 0.3s;
            }
            .app-nav-link.active {
                color: var(--cyan);
                text-shadow: 0 0 10px rgba(56, 189, 248, 0.5);
            }
            .app-nav-link.active::after {
                content: '';
                position: absolute;
                bottom: -15px;
                left: 0;
                width: 100%;
                height: 2px;
                background: var(--cyan);
                box-shadow: 0 0 15px var(--cyan);
            }
            .brand-mark {
                background: linear-gradient(135deg, #fbbf24, #0ea5e9);
                color: #020617;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-weight: 900;
                font-family: 'Rajdhani', sans-serif;
                font-size: 20px;
                border-radius: 8px;
                padding: 4px 8px;
            }
        </style>
        <header class="app-header">
            <a href="<?= $root ?>dashboard/maindashboard.php" class="app-brand" aria-label="37Y Tournament Maker dashboard">
                <span class="brand-mark">37Y</span>
                <span class="brand-text" style="font-family:'Space Grotesk', sans-serif; font-weight:700; letter-spacing:-0.5px; font-size:20px; color:#fff;">37Y <strong style="color:var(--cyan); font-weight:500;">TOURNAMENT MAKER</strong></span>
            </a>

            <nav class="app-nav" aria-label="Primary navigation">
                <?php foreach ($nav_items as $key => $item): ?>
                    <a href="<?= htmlspecialchars($item['href']) ?>" class="app-nav-link <?= $active === $key ? 'active' : '' ?>">
                        <?= htmlspecialchars($item['label']) ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="app-actions">
                <?php foreach ($actions as $action): ?>
                    <?php
                    $label = $action['label'] ?? '';
                    $href = $action['href'] ?? '#';
                    $variant = $action['variant'] ?? '';
                    $disabled = !empty($action['disabled']);
                    $confirm = $action['confirm'] ?? '';
                    $class = trim('app-action ' . $variant . ($disabled ? ' disabled' : ''));
                    ?>
                    <?php if ($disabled): ?>
                        <span class="<?= htmlspecialchars($class) ?>"><?= htmlspecialchars($label) ?></span>
                    <?php else: ?>
                        <a href="<?= htmlspecialchars($href) ?>" class="<?= htmlspecialchars($class) ?>" <?= $confirm ? 'onclick="return confirm(\'' . htmlspecialchars($confirm, ENT_QUOTES) . '\')"' : '' ?>>
                            <?= htmlspecialchars($label) ?>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
                <a href="<?= $root ?>auth/logout.php" class="app-action logout">Logout</a>
            </div>
        </header>
        <?php
    }
}
