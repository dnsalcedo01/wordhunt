<?php
session_start();
header('Content-Type: application/json');
$games_dir = '../games/';

$game_code = $_GET['code'] ?? null;
$player_name = $_GET['name'] ?? null;
$player_emoji = $_GET['emoji'] ?? null;

$response = ['success' => false];

if (!$game_code) {
    $response['message'] = 'No game code provided.';
    echo json_encode($response); exit;
}

$game_file = $games_dir . $game_code . '.json';
if (!file_exists($game_file)) {
    $response['message'] = 'Game not found.';
    echo json_encode($response); exit;
}

// Lock the file
$file_handle = fopen($game_file, 'r+');
if (!flock($file_handle, LOCK_EX)) {
    $response['message'] = 'Server busy.';
    echo json_encode($response); exit;
}

$game_data_json = fread($file_handle, filesize($game_file));
$game_data = json_decode($game_data_json, true);

// --- Check Timer ---
// This is the new, robust server-side timer check
if ($game_data['game_status'] == 'running') {
    $elapsed = time() - $game_data['start_time'];
    if ($elapsed > $game_data['time_limit']) {
        $game_data['game_status'] = 'ended';
    }
}

// --- Check for New Player Joining ---
$player_id = null;
if ($player_name && $player_emoji) {
    if ($game_data['game_status'] != 'waiting') {
        $response['message'] = 'Game has already started.';
    } elseif (count($game_data['players']) >= 20) {
        $response['message'] = 'Game is full.';
    } else {
        $name_taken = false;
        foreach ($game_data['players'] as $p) {
            if (strtolower($p['name']) == strtolower($player_name)) $name_taken = true;
        }
        if ($name_taken) {
            $response['message'] = 'That name is taken.';
        } else {
            // Add new player
            $new_player = [
                'id' => uniqid('p_'),
                'name' => htmlspecialchars($player_name),
                'emoji' => $player_emoji,
                'score' => 0,
                'found_words' => []
            ];
            $game_data['players'][] = $new_player;
            $_SESSION['player_id'] = $new_player['id'];
            $player_id = $new_player['id'];
        }
    }
}

// --- Write updated data (if changed) ---
ftruncate($file_handle, 0);
rewind($file_handle);
fwrite($file_handle, json_encode($game_data, JSON_PRETTY_PRINT));
flock($file_handle, LOCK_UN);
fclose($file_handle);

// --- Send Response ---
$response['success'] = true;
$response['game_status'] = $game_data['game_status'];
$response['start_time'] = $game_data['start_time'];
$response['time_limit'] = $game_data['time_limit'];
$response['words'] = $game_data['words'];
$response['players'] = $game_data['players'];

if ($player_id) {
    $response['player_id'] = $player_id; // Send new player their ID
}

echo json_encode($response);
?>