<?php
header('Content-Type: application/json');
$games_dir = '../games/'; // Go up one directory
if (!is_dir($games_dir)) mkdir($games_dir);

function generateGameCode($dir) {
    do {
        $code = (string) rand(100000, 999999);
        $file_path = $dir . $code . '.json';
    } while (file_exists($file_path));
    return $code;
}

$response = ['success' => false, 'message' => ''];
$words_json = $_POST['words'] ?? '[]';
$timer = (int) ($_POST['timer'] ?? 180);
$words = json_decode($words_json, true);

if (empty($words) || count($words) < 2) {
    $response['message'] = 'Please provide at least 10 words.';
    echo json_encode($response); exit;
}

$game_code = generateGameCode($games_dir);
$game_file = $games_dir . $game_code . '.json';

$game_data = [
    'game_code' => $game_code,
    'game_status' => 'waiting',
    'time_limit' => $timer,
    'start_time' => null,
    'words' => $words,
    'players' => []
];

if (file_put_contents($game_file, json_encode($game_data, JSON_PRETTY_PRINT))) {
    $response['success'] = true;
    $response['game_code'] = $game_code;
} else {
    $response['message'] = 'Server error: Could not create game file.';
}
echo json_encode($response);
?>