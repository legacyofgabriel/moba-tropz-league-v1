<?php
/**
 * MOBA TROPZ - Statistics Helper
 * Centralized logic for tournament-wide analytics.
 */

if (!function_exists('get_hero_meta_stats')) {
    function get_hero_meta_stats(mysqli $conn, int $tournament_id): array
    {
        $query = "
            SELECT 
                hero_name, 
                COUNT(*) as times_picked,
                SUM(kills) as total_kills,
                SUM(deaths) as total_deaths,
                SUM(assists) as total_assists,
                AVG(hero_damage) as avg_damage
            FROM player_match_stats 
            WHERE tournament_id = $tournament_id
            GROUP BY hero_name 
            ORDER BY times_picked DESC
        ";
        
        $result = $conn->query($query);
        
        $stats = [];
        while ($row = $result->fetch_assoc()) {
            $stats[] = $row;
        }
        
        return $stats;
    }
}
?>