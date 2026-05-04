<?php

if (!function_exists('validate_team_logo_upload')) {
    function validate_team_logo_upload(array $file): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return '';
        if ($file['error'] !== UPLOAD_ERR_OK) return 'Transmission error. File upload failed.';
        if ($file['size'] > 5 * 1024 * 1024) return 'Intel too heavy. Logo must be 5MB or smaller.';
        
        $allowed_mimes = [
            'image/jpeg' => 'jpg', 
            'image/jpg'  => 'jpg', 
            'image/png'  => 'png', 
            'image/x-png' => 'png',
            'image/webp' => 'webp'
        ];
        
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        
        return isset($allowed_mimes[$mime]) ? '' : 'Invalid data format. Use JPG, PNG, or WEBP only.';
    }
}

if (!function_exists('save_team_logo_upload')) {
    function save_team_logo_upload(array $file, int $team_id): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) return null;
        
        $mimes = [
            'image/jpeg' => 'jpg', 'image/jpg' => 'jpg',
            'image/png' => 'png', 'image/x-png' => 'png',
            'image/webp' => 'webp'
        ];
        
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        $ext = $mimes[$mime] ?? 'png';

        $dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'team_logos';
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        
        $filename = 'team_' . $team_id . '_' . time() . '.' . $ext;
        $target = $dir . DIRECTORY_SEPARATOR . $filename;

        if (move_uploaded_file($file['tmp_name'], $target)) {
            return 'uploads/team_logos/' . $filename;
        }
        return null;
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

if (!function_exists('team_logo_src')) {
    function team_logo_src(?string $logo_path, string $base_path = '') {
        if (empty($logo_path)) return $base_path . 'assets/default_team.png';
        
        // Handle potential different slash styles
        $clean_path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $logo_path);
        $full_server_path = dirname(__DIR__) . DIRECTORY_SEPARATOR . $clean_path;
        
        if (file_exists($full_server_path) && is_file($full_server_path)) {
            return $base_path . $logo_path; 
        }
        return $base_path . 'assets/default_team.png'; // Default logo if none provided or found
    }
}