<?php

function getTournamentState(mysqli $conn, int $tournament_id): array
{
    $state = [
        'tournament' => null,
        'target_teams' => 0,
        'actual_teams' => 0,
        'expected_rr_matches' => 0,
        'rr_matches' => 0,
        'rr_completed' => 0,
        'rr_stale' => 0,
        'playoff_matches' => 0,
        'invalid_teams' => [],
        'messages' => [],
        'can_generate_round_robin' => false,
        'can_generate_playoffs' => false,
    ];

    $tournament = $conn->query("SELECT * FROM tournaments WHERE id = $tournament_id")->fetch_assoc();
    if (!$tournament) {
        $state['messages'][] = "Tournament not found. Please select a valid tournament.";
        return $state;
    }

    $state['tournament'] = $tournament;
    $state['target_teams'] = intval($tournament['team_count']);
    $state['expected_rr_matches'] = intval(($state['target_teams'] * ($state['target_teams'] - 1)) / 2);

    $teams = $conn->query("SELECT id, name FROM teams WHERE tournament_id = $tournament_id ORDER BY name ASC");
    $state['actual_teams'] = $teams->num_rows;

    while ($team = $teams->fetch_assoc()) {
        $team_id = intval($team['id']);
        $player_count = intval($conn->query("SELECT COUNT(*) AS total FROM players WHERE team_id = $team_id AND tournament_id = $tournament_id")->fetch_assoc()['total']);

        if ($player_count < 5 || $player_count > 6) {
            $state['invalid_teams'][] = [
                'name' => $team['name'],
                'players' => $player_count,
            ];
        }
    }

    $rr = $conn->query("SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN is_locked = 1 THEN 1 ELSE 0 END) AS completed
        FROM matches
        WHERE tournament_id = $tournament_id AND match_type = 'Round Robin'")->fetch_assoc();
    $state['rr_matches'] = intval($rr['total']);
    $state['rr_completed'] = intval($rr['completed']);

    $state['rr_stale'] = intval($conn->query("SELECT COUNT(*) AS total
        FROM matches m
        LEFT JOIN teams t1 ON m.team1_id = t1.id
        LEFT JOIN teams t2 ON m.team2_id = t2.id
        WHERE m.tournament_id = $tournament_id
          AND m.match_type = 'Round Robin'
          AND (t1.id IS NULL OR t2.id IS NULL)")->fetch_assoc()['total']);

    $state['playoff_matches'] = intval($conn->query("SELECT COUNT(*) AS total FROM matches WHERE tournament_id = $tournament_id AND match_type = 'Playoffs'")->fetch_assoc()['total']);

    if ($state['actual_teams'] !== $state['target_teams']) {
        $state['messages'][] = "Team slots mismatch: kailangan ng {$state['target_teams']} teams pero {$state['actual_teams']} pa lang ang registered.";
    }

    foreach ($state['invalid_teams'] as $team) {
        $state['messages'][] = "Incomplete roster: {$team['name']} has {$team['players']} players. Required: 5 to 6 players.";
    }

    if ($state['rr_stale'] > 0) {
        $state['messages'][] = "May Round Robin matches na may missing/deleted team. Generate Round Robin again after fixing teams.";
    }

    $state['can_generate_round_robin'] = (
        $state['actual_teams'] === $state['target_teams']
        && count($state['invalid_teams']) === 0
    );

    if ($state['rr_matches'] === 0) {
        $state['messages'][] = "Wala pang Round Robin matches. Generate Round Robin first.";
    } elseif ($state['rr_matches'] !== $state['expected_rr_matches']) {
        $state['messages'][] = "Round Robin schedule is out of sync. Expected {$state['expected_rr_matches']} matches, found {$state['rr_matches']}. Generate Round Robin again.";
    } elseif ($state['rr_completed'] < $state['expected_rr_matches']) {
        $remaining = $state['expected_rr_matches'] - $state['rr_completed'];
        $state['messages'][] = "Round Robin is not finished yet. {$remaining} match(es) still pending.";
    }

    if ($state['target_teams'] < 6) {
        $state['messages'][] = "Playoff bracket needs at least 6 tournament teams.";
    }

    $state['can_generate_playoffs'] = (
        $state['can_generate_round_robin']
        && $state['target_teams'] >= 6
        && $state['rr_matches'] === $state['expected_rr_matches']
        && $state['rr_completed'] === $state['expected_rr_matches']
        && $state['rr_stale'] === 0
    );

    return $state;
}

function clearPlayoffsIfTournamentNotReady(mysqli $conn, int $tournament_id, array $state): void
{
    if (!$state['can_generate_playoffs'] && $state['playoff_matches'] > 0) {
        $conn->query("DELETE FROM matches WHERE tournament_id = $tournament_id AND match_type = 'Playoffs'");
    }
}

