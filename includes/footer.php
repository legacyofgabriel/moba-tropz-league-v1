<?php

if (!function_exists('render_app_footer')) {
    function render_app_footer(): void
    {
        ?>
        <footer class="app-footer">
            <div>MOBA TROPZ League Manager</div>
            <div class="app-footer-meta">Built for tournament operations &middot; <?= date('Y') ?></div>
        </footer>
        <?php
    }
}

