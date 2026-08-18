<?php
session_start();
header('Content-Type: application/json');
$games_dir = '../games/';

$game_code = $_POST['code'] ?? null;
$submitted_word = $_POST['word'] ?? null;
$player_id = $_SESSION['player_id'] ?? null;

$response = ['success' => false, 'message' => ''];

if (!$game_code || !$submitted_word || !$player_id) {
    $response['message'] = 'Missing data.'; echo json_encode($response); exit;
}
$game_file = $games_dir . $game_code . '.json';
if (!file_exists($game_file)) {
    $response['message'] = 'Game not found.'; echo json_encode($response); exit;
}

// Lock file
$file_handle = fopen($game_file, 'r+');
if (!flock($file_handle, LOCK_EX)) {
    $response['message'] = 'Server busy.'; echo json_encode($response); exit;
}
$game_data = json_decode(fread($file_handle, filesize($game_file)), true);

if ($game_data['game_status'] != 'running') {
    $response['message'] = 'Game is not running.';
    flock($file_handle, LOCK_UN); fclose($file_handle); echo json_encode($response); exit;
}

$player_index = -1;
foreach ($game_data['players'] as $index => $player) {
    if ($player['id'] == $player_id) $player_index = $index;
}
if ($player_index === -1) {
    $response['message'] = 'Player not found.';
    flock($file_handle, LOCK_UN); fclose($file_handle); echo json_encode($response); exit;
}

if (count($game_data['players'][$player_index]['found_words']) >= 2) {
    $response['message'] = 'You already found 2 words.';
    flock($file_handle, LOCK_UN); fclose($file_handle); echo json_encode($response); exit;
}

// Check word list (and reversed word)
$word_found = false;
$correct_word = '';
$reversed_word = strrev($submitted_word);

foreach ($game_data['words'] as $word) {
    if (strtoupper($word) == strtoupper($submitted_word) || strtoupper($word) == strtoupper($reversed_word)) {
        $word_found = true;
        $correct_word = $word;
        break;
    }
}

if (!$word_found) {
    $response['message'] = 'Not a valid word.';
    flock($file_handle, LOCK_UN); fclose($file_handle); echo json_encode($response); exit;
}

foreach ($game_data['players'][$player_index]['found_words'] as $found) {
    if (strtoupper($found['word']) == strtoupper($correct_word)) {
        $response['message'] = 'Already found.';
        flock($file_handle, LOCK_UN); fclose($file_handle); echo json_encode($response); exit;
    }
}

// --- Success! Add points ---
$game_data['players'][$player_index]['score'] += 5;
$game_data['players'][$player_index]['found_words'][] = [
    'word' => $correct_word,
    'timestamp' => time()
];

ftruncate($file_handle, 0);
rewind($file_handle);
fwrite($file_handle, json_encode($game_data, JSON_PRETTY_PRINT));
flock($file_handle, LOCK_UN);
fclose($file_handle);

$response['success'] = true;
echo json_encode($response);
?>