<?php

if (!function_exists('player_avatar_data_uri')) {
    function player_avatar_data_uri(string $name, string $role = ''): string
    {
        $clean_name = trim($name) !== '' ? trim($name) : 'Player';
        $parts = preg_split('/\s+/', $clean_name);
        $initials = strtoupper(substr($parts[0] ?? 'P', 0, 1) . substr($parts[1] ?? ($parts[0] ?? 'L'), 0, 1));
        $palette = [
            ['#38bdf8', '#6366f1'],
            ['#f0b429', '#f97316'],
            ['#4ade80', '#14b8a6'],
            ['#a78bfa', '#ec4899'],
            ['#f87171', '#fb7185'],
        ];
        $pick = abs(crc32($clean_name . $role)) % count($palette);
        [$c1, $c2] = $palette[$pick];
        $safe_initials = htmlspecialchars($initials, ENT_QUOTES, 'UTF-8');
        $safe_role = htmlspecialchars(strtoupper($role), ENT_QUOTES, 'UTF-8');

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="480" height="560" viewBox="0 0 480 560">
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="$c1"/>
      <stop offset="1" stop-color="$c2"/>
    </linearGradient>
    <radialGradient id="glow" cx="50%" cy="22%" r="70%">
      <stop offset="0" stop-color="#ffffff" stop-opacity=".32"/>
      <stop offset="1" stop-color="#ffffff" stop-opacity="0"/>
    </radialGradient>
  </defs>
  <rect width="480" height="560" rx="34" fill="#020617"/>
  <rect x="18" y="18" width="444" height="524" rx="28" fill="url(#bg)" opacity=".95"/>
  <rect x="18" y="18" width="444" height="524" rx="28" fill="url(#glow)"/>
  <circle cx="240" cy="188" r="88" fill="#020617" opacity=".72"/>
  <circle cx="240" cy="168" r="54" fill="#e2e8f0" opacity=".94"/>
  <path d="M130 382c20-66 68-96 110-96s90 30 110 96c8 27-12 54-40 54H170c-28 0-48-27-40-54z" fill="#e2e8f0" opacity=".94"/>
  <text x="240" y="483" text-anchor="middle" font-family="Arial, sans-serif" font-size="74" font-weight="800" fill="#020617">$safe_initials</text>
  <text x="240" y="522" text-anchor="middle" font-family="Arial, sans-serif" font-size="24" font-weight="800" letter-spacing="4" fill="#020617" opacity=".72">$safe_role</text>
</svg>
SVG;

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}
