<?php

if (!function_exists('ensure_player_photo_column')) {
    function ensure_player_photo_column(mysqli $conn): void
    {
        $check = $conn->query("SHOW COLUMNS FROM players LIKE 'photo_path'");
        if ($check && $check->num_rows === 0) {
            $conn->query("ALTER TABLE players ADD COLUMN photo_path VARCHAR(255) NULL AFTER is_captain");
        }
    }
}

if (!function_exists('ensure_team_logo_column')) {
    function ensure_team_logo_column(mysqli $conn): void
    {
        $check = $conn->query("SHOW COLUMNS FROM teams LIKE 'logo_path'");
        if ($check && $check->num_rows === 0) {
            $conn->query("ALTER TABLE teams ADD COLUMN logo_path VARCHAR(255) NULL AFTER short_name");
        }
    }
}

if (!function_exists('validate_player_photo_upload')) {
    function validate_player_photo_upload(array $file): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return '';
        }

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            return 'Photo upload failed. Please try again.';
        }

        if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
            return 'Profile photo must be 2MB or smaller.';
        }

        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $mime = mime_content_type($file['tmp_name']);
        if (!isset($allowed[$mime])) {
            return 'Profile photo must be JPG, PNG, or WEBP.';
        }

        return '';
    }
}

if (!function_exists('save_player_photo_upload')) {
    function save_player_photo_upload(array $file, int $player_id): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $mime = mime_content_type($file['tmp_name']);
        $ext = $allowed[$mime] ?? null;
        if (!$ext) {
            return null;
        }

        $root = dirname(__DIR__);
        $dir = $root . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'players_profile';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $filename = 'player_' . $player_id . '_' . time() . '.' . $ext;
        $target = $dir . DIRECTORY_SEPARATOR . $filename;

        if (!move_uploaded_file($file['tmp_name'], $target)) {
            return null;
        }

        return 'uploads/players_profile/' . $filename;
    }
}

if (!function_exists('validate_team_logo_upload')) {
    function validate_team_logo_upload(array $file): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return '';
        if ($file['size'] > 2 * 1024 * 1024) return 'Team logo must be 2MB or smaller.';
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $mime = mime_content_type($file['tmp_name']);
        return isset($allowed[$mime]) ? '' : 'Logo must be JPG, PNG, or WEBP.';
    }
}

if (!function_exists('save_team_logo_upload')) {
    function save_team_logo_upload(array $file, int $team_id): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
        $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][mime_content_type($file['tmp_name'])];
        $dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'team_logos';
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        $filename = 'team_' . $team_id . '_' . time() . '.' . $ext;
        return move_uploaded_file($file['tmp_name'], $dir . DIRECTORY_SEPARATOR . $filename) ? 'uploads/team_logos/' . $filename : null;
    }
}

if (!function_exists('team_logo_src')) {
    function team_logo_src(?string $path, string $prefix): string
    {
        if (!$path) return $prefix . 'assets/default_team.png'; // Make sure you have a default icon
        $full = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        return is_file($full) ? $prefix . $path : $prefix . 'assets/default_team.png';
    }
}

if (!function_exists('delete_team_logo_file')) {
    function delete_team_logo_file(?string $path): void
    {
        if (!$path) return;
        $full = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        if (is_file($full)) unlink($full);
    }
}

if (!function_exists('player_photo_src')) {
    function player_photo_src(array $player, string $prefix, string $fallback): string
    {
        $path = $player['photo_path'] ?? '';
        if ($path === '') {
            return $fallback;
        }

        // Handle transition: Kung ang lumang path ay nasa database, i-redirect sa bagong folder
        $path = str_replace('player_photos', 'players_profile', $path);

        $full = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        if (!is_file($full)) {
            return $fallback;
        }

        return $prefix . $path;
    }
}

if (!function_exists('delete_player_photo_file')) {
    function delete_player_photo_file(?string $path): void
    {
        if (!$path) {
            return;
        }

        $full = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        $root = realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'players_profile');
        $file = realpath($full);

        if ($root && $file && str_starts_with($file, $root) && is_file($file)) {
            unlink($file);
        }
    }
}
