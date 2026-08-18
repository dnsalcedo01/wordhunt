<?php
header('Content-Type: application/json');
$games_dir = '../games/';
$game_code = $_GET['code'] ?? null;
$action = $_GET['action'] ?? null;
$response = ['success' => false];

if (!$game_code || !$action) { $response['message'] = 'No code.'; echo json_encode($response); exit; }
$game_file = $games_dir . $game_code . '.json';
if (!file_exists($game_file)) { $response['message'] = 'No game.'; echo json_encode($response); exit; }

$game_data = json_decode(file_get_contents($game_file), true);

if ($action == 'end') {
    $game_data['game_status'] = 'ended';
} else {
    $response['message'] = 'Unknown action.';
    echo json_encode($response); exit;
}

if (file_put_contents($game_file, json_encode($game_data, JSON_PRETTY_PRINT))) {
    $response['success'] = true;
} else {
    $response['message'] = 'Server error.';
}
echo json_encode($response);
?>