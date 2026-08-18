<?php

// --- BEGIN PHP BACKEND API ---
// This block handles all backend logic (create, join, poll, etc.)
// It's triggered when game.js sends a POST request.

// Ensure the 'games' directory exists and is writable.
if (!is_dir('games')) {
    mkdir('games', 0777, true);
}

// Handle POST requests from the client (game.js)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the posted JSON data and decode it
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? null;
    $response = ['success' => false, 'message' => 'Invalid action'];

    // Use file locking to prevent race conditions when writing JSON files
    define('LOCK_FILE', 'games/api.lock');
    $lockFp = fopen(LOCK_FILE, 'w');
    if (!flock($lockFp, LOCK_EX)) {
        echo json_encode(['success' => false, 'message' => 'Could not get file lock.']);
        exit;
    }

    try {
        switch ($action) {
            case 'createGame':
                $response = createGame($input['words'], $input['timer']);
                break;
            case 'joinGame':
                $response = joinGame($input['gameCode']);
                break;
            case 'enterGame':
                $response = enterGame($input['gameCode'], $input['name'], $input['avatar']);
                break;
            case 'pollState':
                $response = pollState($input['gameCode'], $input['playerId'] ?? null);
                break;
            case 'startGame':
                $response = startGame($input['gameCode']);
                break;
            case 'leaveGame':
                $response = leaveGame($input['gameCode'], $input['playerId']);
                break;
            case 'submitWord':
                $response = submitWord($input['gameCode'], $input['playerId'], $input['start'], $input['end']);
                break;
            case 'endGame':
                $response = endGame($input['gameCode']);
                break;
            case 'cancelGame':
                $response = cancelGame($input['gameCode']);
                break;
        }
    } catch (Exception $e) {
        $response = ['success' => false, 'message' => 'Server error: ' . $e->getMessage()];
    }

    // Release the lock
    flock($lockFp, LOCK_UN);
    fclose($lockFp);
    
    // Send the JSON response back to the client
    header('Content-Type: application/json');
    echo json_encode($response);
    exit; // Stop script execution to only send JSON response
}

// --- PHP HELPER FUNCTIONS ---

/**
 * Cleans up game files older than 12 hours (43200 seconds).
 */
function cleanupOldGames() {
    $files = glob('games/*.json');
    if ($files) {
        $now = time();
        foreach ($files as $file) {
            if (is_file($file) && ($now - filemtime($file) >= 43200)) {
                unlink($file);
            }
        }
    }
}

/**
 * Gets the file path for a game's JSON file.
 */
function getGameFile($gameCode) {
    // Sanitize game code to prevent directory traversal
    $gameCode = preg_replace('/[^A-Z0-9]/', '', strtoupper($gameCode));
    if (empty($gameCode)) {
        return null;
    }
    return 'games/' . $gameCode . '.json';
}

/**
 * Reads a game's state from its JSON file.
 */
function readGameData($gameCode) {
    $file = getGameFile($gameCode);
    if ($file === null || !file_exists($file)) {
        return null;
    }
    $data = file_get_contents($file);
    return json_decode($data, true);
}

/**
 * Writes a game's state to its JSON file.
 * Uses file locking for safety.
 */
function writeGameData($gameCode, $data) {
    $file = getGameFile($gameCode);
    if ($file === null) {
        return false;
    }
    // Pretty print for readability, remove for production
    $jsonData = json_encode($data, JSON_PRETTY_PRINT); 
    if (file_put_contents($file, $jsonData, LOCK_EX) === false) {
        return false;
    }
    return true;
}

/**
 * Creates a new game.
 */
function createGame($words, $timer) {
    // Clean up old games first to prevent folder from filling up
    cleanupOldGames();

    // 1. Validate words (10-15 words, max 15 chars)
    $filteredWords = [];
    foreach ($words as $word) {
        $w = strtoupper(preg_replace("/[^a-zA-Z]/", "", $word));
        if (strlen($w) > 0 && strlen($w) <= 15) {
            $filteredWords[] = $w;
        }
    }
    if (count($filteredWords) < 10) {
        return ['success' => false, 'message' => 'Please enter at least 10 valid words (max 15 letters).'];
    }
    $filteredWords = array_unique(array_slice($filteredWords, 0, 15));

    // 2. Generate a unique game code
    $gameCode = '';
    do {
        $gameCode = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 6);
    } while (file_exists(getGameFile($gameCode)));

    // 3. Generate the word hunt grid
    $gridResult = generateWordGrid($filteredWords);
    if (!$gridResult) {
        return ['success' => false, 'message' => 'Error generating game grid. Try different words.'];
    }

    // 4. Create the initial game state
    $gameData = [
        'gameCode' => $gameCode,
        'status' => 'waiting', // 'waiting', 'started', 'ended', 'cancelled'
        'timerDuration' => (int)$timer * 60, // 180, 240, or 300 seconds
        'startTime' => null,
        'words' => $gridResult['placedWords'], // Only words that were successfully placed
        'wordLocations' => $gridResult['locations'],
        'grid' => $gridResult['grid'],
        'players' => [],
        'gmLastSeen' => time(), // Track GM presence
    ];

    // 5. Save the game file
    if (!writeGameData($gameCode, $gameData)) {
        return ['success' => false, 'message' => 'Could not save game file.'];
    }

    return ['success' => true, 'gameCode' => $gameCode];
}

/**
 * Checks if a game exists and is in the 'waiting' state.
 */
function joinGame($gameCode) {
    $gameData = readGameData($gameCode);
    if ($gameData === null) {
        return ['success' => false, 'message' => 'Game code not found.'];
    }
    if ($gameData['status'] !== 'waiting') {
        return ['success' => false, 'message' => 'This game is no longer waiting for players.'];
    }
    return ['success' => true];
}

/**
 * Adds a player to a game.
 */
function enterGame($gameCode, $name, $avatar) {
    $gameData = readGameData($gameCode);
    if ($gameData === null || $gameData['status'] !== 'waiting') {
        return ['success' => false, 'message' => 'Game not found or already started.'];
    }

    $name = substr(htmlspecialchars($name), 0, 20); // Sanitize and limit name
    if (empty($name)) {
        return ['success' => false, 'message' => 'Please enter a name.'];
    }
    
    $playerId = uniqid('p_'); // Generate a unique ID for the player

    $gameData['players'][] = [
        'id' => $playerId,
        'name' => $name,
        'avatar' => $avatar,
        'score' => 0,
        'wordsFound' => [],
        'firstFindTime' => null, // Timestamp of first correct word
        'lastSeen' => time(), // Track when the player was last active
    ];

    if (!writeGameData($gameCode, $gameData)) {
        return ['success' => false, 'message' => 'Could not add player to game.'];
    }

    return ['success' => true, 'playerId' => $playerId, 'gameData' => $gameData];
}

/**
 * Polls for the current game state.
 */
function pollState($gameCode, $playerId) {
    $gameData = readGameData($gameCode);
    if ($gameData === null) {
        return ['success' => false, 'message' => 'Game not found.'];
    }

    $needsSave = false;

    if ($playerId === null) {
        // It's the GM polling
        $gameData['gmLastSeen'] = time();
        $needsSave = true;
    }

    // Clean up if GM times out (give GM 5 seconds)
    if (isset($gameData['gmLastSeen']) && (time() - $gameData['gmLastSeen'] > 5)) {
        if ($gameData['status'] === 'waiting' || $gameData['status'] === 'started') {
            $gameData['status'] = 'cancelled';
            writeGameData($gameCode, $gameData);
            return ['success' => true, 'gameData' => $gameData]; // Return immediately
        }
    }

    // Update lastSeen for the polling player
    if ($playerId !== null) {
        foreach ($gameData['players'] as &$p) {
            if ($p['id'] === $playerId) {
                $p['lastSeen'] = time();
                $needsSave = true;
                break;
            }
        }
    }

    // Clean up disconnected players if game is waiting or started
    if ($gameData['status'] === 'waiting' || $gameData['status'] === 'started') {
        $currentTime = time();
        $originalPlayerCount = count($gameData['players']);
        
        $gameData['players'] = array_filter($gameData['players'], function($p) use ($currentTime) {
            // Give them 5 seconds to timeout
            return !isset($p['lastSeen']) || ($currentTime - $p['lastSeen'] < 5);
        });
        
        // Re-index array
        $gameData['players'] = array_values($gameData['players']);
        
        if (count($gameData['players']) !== $originalPlayerCount) {
            $needsSave = true;
        }
    }

    // If game has started, calculate remaining time
    if ($gameData['status'] === 'started') {
        $elapsed = time() - $gameData['startTime'];
        $remaining = $gameData['timerDuration'] - $elapsed;
        $gameData['timerRemaining'] = $remaining;

        // If time is up, end the game
        if ($remaining <= 0) {
            $gameData['status'] = 'ended';
            $needsSave = true;
        }
    }
    
    if ($needsSave) {
        writeGameData($gameCode, $gameData);
    }

    // Generate and attach leaderboard
    $gameData['leaderboard'] = generateLeaderboard($gameData['players']);

    return ['success' => true, 'gameData' => $gameData];
}

/**
 * Handles a player explicitly leaving the game.
 */
function leaveGame($gameCode, $playerId) {
    $gameData = readGameData($gameCode);
    if ($gameData === null || $playerId === null) {
        return ['success' => false]; // Silent fail is fine
    }

    if ($gameData['status'] === 'waiting' || $gameData['status'] === 'started') {
        $gameData['players'] = array_filter($gameData['players'], function($p) use ($playerId) {
            return $p['id'] !== $playerId;
        });
        $gameData['players'] = array_values($gameData['players']);
        writeGameData($gameCode, $gameData);
    }
    
    return ['success' => true];
}

/**
 * Starts the game (by GM).
 */
function startGame($gameCode) {
    $gameData = readGameData($gameCode);
    if ($gameData === null) {
        return ['success' => false, 'message' => 'Game not found.'];
    }

    $gameData['status'] = 'started';
    $gameData['startTime'] = time(); // Record the exact start time

    if (!writeGameData($gameCode, $gameData)) {
        return ['success' => false, 'message' => 'Could not start game.'];
    }
    return ['success' => true, 'gameData' => $gameData];
}

/**
 * Handles a player submitting a word.
 */
function submitWord($gameCode, $playerId, $start, $end) {
    $gameData = readGameData($gameCode);
    if ($gameData === null || $gameData['status'] !== 'started') {
        return ['success' => false, 'message' => 'Game is not active.'];
    }
    
    // Find the player
    $playerIndex = -1;
    foreach ($gameData['players'] as $i => $p) {
        if ($p['id'] === $playerId) {
            $playerIndex = $i;
            break;
        }
    }
    if ($playerIndex === -1) {
        return ['success' => false, 'message' => 'Player not found.'];
    }
    
    // Max 5 words
    if (count($gameData['players'][$playerIndex]['wordsFound']) >= 5) {
        return ['success' => true, 'correct' => false, 'message' => 'You have already found 5 words!'];
    }

    // Check if this word (defined by start/end) matches any in wordLocations
    $foundWord = null;
    $startPos = [$start['r'], $start['c']];
    $endPos = [$end['r'], $end['c']];

    foreach ($gameData['wordLocations'] as $word => $loc) {
        $locStart = $loc['start'];
        $locEnd = $loc['end'];
        
        // Check both directions (e.g., [0,0] to [0,5] is same as [0,5] to [0,0])
        if (($locStart == $startPos && $locEnd == $endPos) || ($locStart == $endPos && $locEnd == $startPos)) {
            $foundWord = $word;
            break;
        }
    }

    if ($foundWord === null) {
        return ['success' => true, 'correct' => false, 'message' => 'Not a valid word.'];
    }

    // Check if player already found this word
    if (in_array($foundWord, $gameData['players'][$playerIndex]['wordsFound'])) {
        return ['success' => true, 'correct' => false, 'message' => 'You already found that word!'];
    }

    // It's a new, correct word!
    $gameData['players'][$playerIndex]['wordsFound'][] = $foundWord;
    $gameData['players'][$playerIndex]['score'] += 1;
    
    // Record time of first find for tie-breaking
    if ($gameData['players'][$playerIndex]['firstFindTime'] === null) {
        $gameData['players'][$playerIndex]['firstFindTime'] = time();
    }
    
    // Check if game is over (all 5 words found)
    $isGameOver = count($gameData['players'][$playerIndex]['wordsFound']) >= 5;

    if (!writeGameData($gameCode, $gameData)) {
        return ['success' => false, 'message' => 'Could not save score.'];
    }

    return [
        'success' => true, 
        'correct' => true, 
        'word' => $foundWord,
        'newScore' => $gameData['players'][$playerIndex]['score'],
        'wordsFoundCount' => count($gameData['players'][$playerIndex]['wordsFound']),
        'isGameOver' => $isGameOver
    ];
}

/**
 * Ends the game (by GM).
 */
function endGame($gameCode) {
    $gameData = readGameData($gameCode);
    if ($gameData === null) {
        return ['success' => false, 'message' => 'Game not found.'];
    }

    $gameData['status'] = 'ended';

    if (!writeGameData($gameCode, $gameData)) {
        return ['success' => false, 'message' => 'Could not end game.'];
    }
    return ['success' => true, 'gameData' => $gameData];
}

/**
 * Cancels the game (by GM).
 */
function cancelGame($gameCode) {
    $file = getGameFile($gameCode);
    if ($file !== null && file_exists($file)) {
        // Option 1: Delete the file
        // unlink($file);
        
        // Option 2: Mark as 'cancelled' so players polling will know
        $gameData = readGameData($gameCode);
        if ($gameData) {
            $gameData['status'] = 'cancelled';
            writeGameData($gameCode, $gameData);
        }
    }
    return ['success' => true];
}

/**
 * Generates the leaderboard.
 * Sort by score (desc), then by firstFindTime (asc) for tie-breaking.
 */
function generateLeaderboard($players) {
    usort($players, function($a, $b) {
        // 1. Sort by score (higher is better)
        if ($a['score'] != $b['score']) {
            return $b['score'] - $a['score'];
        }
        
        // 2. If score is tied, check firstFindTime
        $timeA = $a['firstFindTime'];
        $timeB = $b['firstFindTime'];
        
        // If neither has found a word, they are equal
        if ($timeA === null && $timeB === null) {
            return 0;
        }
        // If B hasn't found a word, A is better (comes first)
        if ($timeB === null) {
            return -1;
        }
        // If A hasn't found a word, B is better
        if ($timeA === null) {
            return 1;
        }
        
        // 3. Both have found words, sort by time (lower is better)
        return $timeA - $timeB;
    });
    return $players;
}


/**
 * Grid Generation Logic
 */
function generateWordGrid($words, $size = 15) {
    // Sort words: longest to shortest
    usort($words, function($a, $b) { return strlen($b) - strlen($a); });

    $grid = array_fill(0, $size, array_fill(0, $size, null));
    $locations = [];
    $placedWords = [];

    // All 8 directions: [row_change, col_change]
    $directions = [
        [0, 1],  // Horizontal
        [1, 0],  // Vertical
        [1, 1],  // Diagonal down-right
        [1, -1], // Diagonal down-left
        [0, -1], // Horizontal reverse
        [-1, 0], // Vertical reverse
        [-1, -1],// Diagonal up-left
        [-1, 1]  // Diagonal up-right
    ];

    foreach ($words as $word) {
        $wordLen = strlen($word);
        $placed = false;
        $attempts = 0;

        while (!$placed && $attempts < 100) { // Try 100 times to place a word
            $attempts++;
            $dir = $directions[array_rand($directions)];
            
            // Pick a random start position
            $startRow = rand(0, $size - 1);
            $startCol = rand(0, $size - 1);

            // Calculate end position
            $endRow = $startRow + ($dir[0] * ($wordLen - 1));
            $endCol = $startCol + ($dir[1] * ($wordLen - 1));

            // Check if it fits within the grid boundaries
            if ($endRow < 0 || $endRow >= $size || $endCol < 0 || $endCol >= $size) {
                continue; // Doesn't fit
            }

            // Check for conflicts
            $canPlace = true;
            $tempGrid = [];
            for ($i = 0; $i < $wordLen; $i++) {
                $r = $startRow + ($dir[0] * $i);
                $c = $startCol + ($dir[1] * $i);
                $letter = $word[$i];

                if ($grid[$r][$c] !== null && $grid[$r][$c] !== $letter) {
                    $canPlace = false;
                    break; // Conflict
                }
                $tempGrid[] = ['r' => $r, 'c' => $c, 'l' => $letter];
            }

            // If it fits, place it
            if ($canPlace) {
                foreach ($tempGrid as $cell) {
                    $grid[$cell['r']][$cell['c']] = $cell['l'];
                }
                $locations[$word] = [
                    'start' => [$startRow, $startCol],
                    'end' => [$endRow, $endCol]
                ];
                $placedWords[] = $word;
                $placed = true;
            }
        }
    }

    // Fill empty cells with random letters
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    for ($r = 0; $r < $size; $r++) {
        for ($c = 0; $c < $size; $c++) {
            if ($grid[$r][$c] === null) {
                $grid[$r][$c] = $alphabet[rand(0, 25)];
            }
        }
    }
    
    if (empty($placedWords)) {
        return null; // Could not place any words
    }

    return ['grid' => $grid, 'locations' => $locations, 'placedWords' => $placedWords];
}

// --- END PHP BACKEND API ---

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WordHunt</title>
    <link type="image/gif" sizes="96x96" rel="icon" href="medal1.gif">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans+Code:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --font-family: "Google Sans Code", monospace;
            --color-bg: #007bff; /* Blue background */
            --color-container: #ffffff; /* White containers */
            --color-header: #0056b3; /* Blue headers */
            --color-text: #333333; /* Black for normal text */
            --color-error: #dc3545; /* Red for error */
            --color-success: #28a745; /* Green for success */
            --color-border: #dee2e6;
            --color-grid-bg: #f8f9fa;
            --color-grid-cell: #e9ecef;
            --color-grid-selected: #007bff;
            --color-grid-text: #495057;
            --color-grid-selected-text: #ffffff;
            --shadow-sm: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
            --shadow-md: 0 0.5rem 1rem rgba(0,0,0,0.15);
        }

        /* Basic Reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: var(--font-family);
            background-color: var(--color-bg);
            color: var(--color-text);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 1rem;
            overflow-y: auto;
        }

        /* Page Container */
        .container {
            width: 100%;
            max-width: 1000px; /* Max width for larger screens */
            margin: 0 auto;
        }

        /* Screen is a main content block */
        .screen {
            display: none; /* Hidden by default */
            background-color: var(--color-container);
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: var(--shadow-md);
            width: 100%;
            max-height: 90vh; /* Max height */
            overflow-y: auto; /* Scrollable */
        }

        /* Show the active screen */
        .screen.active {
            display: block;
        }

        /* Popups */
        .popup {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        
        .popup-content {
            background-color: var(--color-container);
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: var(--shadow-md);
            width: 90%;
            max-width: 500px;
            position: relative;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .popup-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 1.5rem;
            font-weight: bold;
            cursor: pointer;
            color: #aaa;
        }
        .popup-close:hover {
            color: var(--color-text);
        }

        /* Hide popup by default */
        .popup.hidden {
            display: none;
        }

        /* Utility Classes */
        .text-center {
            text-align: center;
        }
        .text-header {
            color: var(--color-header);
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        .text-subhead {
            color: var(--color-text);
            margin-bottom: 1.5rem;
            font-size: 1.1rem;
        }
        .message {
            margin: 1rem 0;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            display: none; /* Hidden by default */
        }
        .message.error {
            display: block;
            background-color: #f8d7da;
            color: var(--color-error);
            border: 1px solid #f5c6cb;
        }
        .message.success {
            display: block;
            background-color: #d4edda;
            color: var(--color-success);
            border: 1px solid #c3e6cb;
        }

        /* Buttons */
        .btn {
            display: inline-block;
            width: 100%;
            padding: 0.75rem 1rem;
            font-family: var(--font-family);
            font-size: 1rem;
            font-weight: 600;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            transition: all 0.2s ease-in-out;
        }
        
        .btn-primary {
            background-color: var(--color-header);
            color: white;
            box-shadow: var(--shadow-sm);
        }
        .btn-primary:hover {
            background-color: #004a99;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background-color: var(--color-grid-cell);
            color: var(--color-header);
            border: 1px solid var(--color-grid-cell);
        }
        .btn-secondary:hover {
            background-color: #d1d9e0;
        }
        
        .btn-danger {
            background-color: var(--color-error);
            color: white;
        }
        .btn-danger:hover {
            background-color: #c82333;
        }

        .btn-group {
            display: flex;
            gap: 1rem;
            flex-direction: column;
        }
        @media (min-width: 576px) {
            .btn-group {
                flex-direction: row;
            }
        }
        
        .btn-timer-option {
            flex: 1;
            background-color: var(--color-container);
            color: var(--color-header);
            border: 2px solid var(--color-border);
            padding: clamp(0.5rem, 1vw, 1rem);
            font-size: clamp(0.8rem, 3vw, 1rem);
        }
        .btn-timer-option.selected {
            background-color: var(--color-header);
            color: white;
            border-color: var(--color-header);
        }


        /* Forms */
        .form-group {
            margin-bottom: 1rem;
        }
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            font-family: var(--font-family);
            font-size: 1rem;
            border: 1px solid var(--color-border);
            border-radius: 0.5rem;
        }
        .form-control::placeholder {
            color: #aaa;
        }

        /* Home Screen */
        #screen-home h1 {
            font-size: 3rem;
            font-weight: 800;
        }

        /* Create Game Popup */
        #create-game-words {
            height: 150px;
            resize: vertical;
        }
        .timer-options {
            display: flex;
            justify-content: space-between;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }

        /* Avatar Selection */
        .avatar-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(40px, 1fr));
            gap: 0.5rem;
            margin: 1rem 0;
            max-width: 400px;
            margin: 1.5rem auto;
        }
        .avatar-option {
            font-size: 1.3rem;
            text-align: center;
            cursor: pointer;
            padding: 0.25rem;
            border-radius: 0.5rem;
            border: 3px solid transparent; /* Fixed: Thicker border for better visibility */
            transition: all 0.2s, transform 0.1s; /* Fixed: Added transform transition */
        }
        .avatar-option.selected {
            border-color: var(--color-header);
            background-color: #e6f0ff;
            transform: scale(1.1); /* Fixed: Added scale to make selection pop */
        }
        
        /* How to Play Box */
        .tip-box {
            background-color: var(--color-grid-bg);
            padding: 1rem;
            border-radius: 0.5rem;
            border: 1px solid var(--color-border);
            margin: 1.5rem 0;
        }
        .tip-box h4 {
            color: var(--color-header);
            margin-bottom: 0.5rem;
        }
        .tip-box p {
            font-size: 0.9rem;
            line-height: 1.4;
        }

        /* Waiting Room / Game Code Screen */
        .game-code-box {
            background-color: var(--color-grid-bg);
            padding: 1.5rem;
            border-radius: 0.5rem;
            border: 2px dashed var(--color-border);
            font-size: 2.5rem;
            font-weight: 700;
            letter-spacing: 0.5rem;
            margin-bottom: 1.5rem;
        }
        
        .player-list {
            list-style: none;
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid var(--color-border);
            border-radius: 0.5rem;
            padding: 0.5rem;
        }
        .player-list-item {
            display: flex;
            align-items: center;
            padding: 0.5rem 0.75rem;
            border-bottom: 1px solid var(--color-border);
        }
        .player-list-item:last-child {
            border-bottom: none;
        }
        .player-avatar {
            font-size: 1.5rem;
            margin-right: 0.75rem;
        }
        .player-name {
            font-weight: 600;
        }

        /* Game Screen */
        #screen-game {
            max-width: 1200px; /* Wider for game layout */
        }
        
        .game-layout {
            display: grid;
            grid-template-areas:
                "stats"
                "grid"
                "submission"
                "leaderboard";
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }

        @media (min-width: 992px) { /* Desktop layout */
            .game-layout {
                grid-template-areas:
                    "stats stats"
                    "grid leaderboard"
                    "submission leaderboard";
                grid-template-columns: 2fr 1fr;
                grid-template-rows: auto 1fr auto;
            }
            #game-leaderboard-box {
                align-self: start; /* Stick to top */
            }
        }
        
        /* Game Stats */
        .game-stats {
            grid-area: stats;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: clamp(0.5rem, 2vw, 1rem);
        }
        .stat-box {
            background-color: var(--color-container);
            border: 1px solid var(--color-border);
            padding: clamp(0.5rem, 2vw, 1rem);
            border-radius: 0.5rem;
            text-align: center;
            box-shadow: var(--shadow-sm);
        }
        .stat-label {
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            color: var(--color-header);
        }
        .stat-value {
            font-size: clamp(1.2rem, 5vw, 2rem);
            font-weight: 700;
            white-space: nowrap;
        }

        /* Game Grid */
        .game-grid-container {
            grid-area: grid;
            background-color: var(--color-grid-bg);
            border: 1px solid var(--color-border);
            border-radius: 0.5rem;
            padding: 0.5rem;
            box-shadow: var(--shadow-sm);
            max-width: 600px; /* Limit max size */
            margin: 0 auto; /* Center on mobile */
            width: 100%;
        }
        .wordhunt-grid {
            display: grid;
            grid-template-columns: repeat(15, 1fr);
            grid-template-rows: repeat(15, 1fr);
            aspect-ratio: 1 / 1; /* Keep grid perfectly square */
            width: 100%;
            border: 2px solid var(--color-border);
            user-select: none; /* Prevent text selection */
        }
        .grid-cell {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: clamp(0.5rem, 3.5vw, 1.2rem); /* Responsive font size */
            font-weight: 600;
            color: var(--color-grid-text);
            background-color: var(--color-grid-cell);
            border: 1px solid #d1d9e0;
            cursor: pointer;
            transition: background-color 0.1s;
            min-height: 0;
            min-width: 0;
            overflow: hidden;
        }
        .grid-cell:hover {
            background-color: #c5d1db;
        }
        .grid-cell.selected {
            background-color: var(--color-grid-selected);
            color: var(--color-grid-selected-text);
        }
        .grid-cell.found {
            background-color: var(--color-success);
            color: white;
        }

        /* Game Submission */
        .game-submission {
            grid-area: submission;
            max-width: 600px; /* Match grid width */
            margin: 0 auto; /* Center on mobile */
            width: 100%;
        }
        .word-preview {
            width: 100%;
            min-height: 48px;
            padding: 0.75rem 1rem;
            border: 1px solid var(--color-border);
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            font-size: 1.2rem;
            font-weight: 600;
            text-align: center;
            letter-spacing: 0.2rem;
        }
        
        /* Leaderboard Box */
        #game-leaderboard-box {
            grid-area: leaderboard;
            background-color: var(--color-container);
            border: 1px solid var(--color-border);
            border-radius: 0.5rem;
            padding: 1rem;
            box-shadow: var(--shadow-sm);
        }
        #game-leaderboard-box h3 {
            color: var(--color-header);
            margin-bottom: 1rem;
        }
        .leaderboard-list {
            list-style: none;
            max-height: 400px;
            overflow-y: auto;
        }
        .leaderboard-item {
            display: flex;
            align-items: center;
            padding: 0.5rem;
            border-bottom: 1px solid var(--color-border);
        }
        .leaderboard-item:last-child {
            border-bottom: none;
        }
        .leaderboard-rank {
            font-size: 1rem;
            font-weight: 700;
            color: var(--color-header);
            width: 30px;
        }
        .leaderboard-avatar {
            font-size: 1.5rem;
            margin: 0 0.5rem;
        }
        .leaderboard-name {
            font-weight: 600;
            flex-grow: 1;
        }
        .leaderboard-score {
            font-size: 1.2rem;
            font-weight: 700;
        }

        /* Results Screen */
        .results-layout {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
        }
        @media (min-width: 768px) {
            .results-layout {
                grid-template-columns: 1fr 1fr;
            }
        }
        
        .winners-podium {
            display: flex;
            align-items: flex-end;
            justify-content: center;
            gap: 5%;
            height: 300px;
            border: 1px solid var(--color-border);
            border-radius: 0.5rem;
            padding: 1rem;
        }
        .podium-stand {
            flex: 1;
            background-color: var(--color-grid-cell);
            border: 2px solid var(--color-header);
            border-radius: 0.5rem 0.5rem 0 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-end;
            padding: 1rem 0.5rem;
            text-align: center;
        }
        .podium-stand[data-rank="1"] { height: 100%; background-color: #ffd700; }
        .podium-stand[data-rank="2"] { height: 80%; background-color: #c0c0c0; }
        .podium-stand[data-rank="3"] { height: 65%; background-color: #cd7f32; }
        
        .podium-avatar {
            font-size: 1.8rem;
            background-color: white;
            border-radius: 50%;
            width: 3.5rem;
            height: 3.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.5rem;
            line-height: 1;
        }
        .podium-name {
            font-weight: 700;
            word-break: break-all;
        }
        .podium-score {
            font-weight: 600;
            font-size: 0.9rem;
        }
        .podium-rank {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--color-header);
        }

        .results-ranking-list {
            border: 1px solid var(--color-border);
            border-radius: 0.5rem;
            padding: 1rem;
        }
        .results-ranking-list h3 {
            color: var(--color-header);
            margin-bottom: 1rem;
        }
        .results-list {
            list-style: none;
            max-height: 300px;
            overflow-y: auto;
        }
        
        /* GM Control Screen */
        .gm-controls {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            max-width: 400px;
            margin: 0 auto;
        }
        
        .gm-timer {
            font-size: 4rem;
            font-weight: 700;
            color: var(--color-header);
            text-align: center;
            margin-bottom: 1rem;
        }

    </style>
</head>
<body>

    <div class="container">

        <!-- ===== HOME SCREEN ===== -->
        <div id="screen-home" class="screen active text-center">
            <h1 class="text-header">WordHunt 🥇</h1>
            <p class="text-subhead"><b>Find it first. Claim the top spot! 🎉</b></p>
            <div class="btn-group">
                <button id="btn-show-join" class="btn btn-primary">JOIN GAME</button>
                <button id="btn-show-create" class="btn btn-secondary">CREATE GAME</button>
            </div>
        </div>

        <!-- ===== PLAYER - WAITING ROOM ===== -->
        <div id="screen-waiting" class="screen text-center">
            <h2 class="text-header">WAITING ROOM ⏲</h2>
            <p class="text-subhead" id="waiting-game-code"></p>
            <h3>Players Joined:</h3>
            <ul id="waiting-player-list" class="player-list">
                <!-- Players will be added here by JS -->
            </ul>
            <p class="tip-box">Waiting for the gamemaster to start the game... ⏳</p>
        </div>

        <!-- ===== PLAYER - GAME SCREEN ===== -->
        <div id="screen-game" class="screen">
            <div class="game-layout">
                <div class="game-stats">
                    <div class="stat-box">
                        <div class="stat-label">Points</div>
                        <div id="stat-points" class="stat-value">0</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-label">Time Left</div>
                        <div id="stat-timer" class="stat-value">00:00</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-label">Words Found</div>
                        <div id="stat-words" class="stat-value">0 / 5</div>
                    </div>
                </div>

                <div class="game-grid-container">
                    <div id="wordhunt-grid" class="wordhunt-grid">
                        <!-- Grid cells will be added here by JS -->
                    </div>
                </div>
                
                <div class="game-submission">
                    <div id="word-preview" class="word-preview">(Select 2 letters)</div>
                    <div id="game-message" class="message"></div>
                    <button id="btn-submit-word" class="btn btn-primary" disabled>SUBMIT</button>
                </div>

                <div id="game-leaderboard-box">
                    <h3>Leaderboard</h3>
                    <ul id="game-leaderboard-list" class="leaderboard-list">
                        <!-- Leaderboard items will be added here by JS -->
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- ===== GM - GAME CODE SCREEN ===== -->
        <div id="screen-gm-code" class="screen text-center">
            <h2 class="text-header">GAME CODE 🎰</h2>
            <p class="text-subhead">Use this code to join the game!</p>
            <div id="gm-game-code" class="game-code-box">000000</div>
            <h3>Players Joined:</h3>
            <ul id="gm-player-list" class="player-list">
                <!-- Players will be added here by JS -->
            </ul>
            <button id="btn-start-game" class="btn btn-primary" style="margin-top: 1.5rem;">START GAME ▶</button>
        </div>
        
        <!-- ===== GM - CONTROLS SCREEN ===== -->
        <div id="screen-gm-controls" class="screen text-center">
            <h2 class="text-header">Game In Progress</h2>
            <div id="gm-timer" class="gm-timer">00:00</div>
            <div class="gm-controls">
                <button id="btn-end-game" class="btn btn-primary">END GAME ✖</button>
                <button id="btn-cancel-game" class="btn btn-danger">CANCEL GAME</button>
            </div>

            <!-- ===== NEW GM LEADERBOARD ===== -->
            <div id="gm-leaderboard-box" style="margin-top: 2rem;">
                <h3>Live Leaderboard</h3>
                <ul id="gm-leaderboard-list" class="leaderboard-list" style="max-height: 250px;">
                    <!-- GM Leaderboard items will be added here by JS -->
                </ul>
            </div>
            <!-- ============================== -->

        </div>

        <!-- ===== RESULTS SCREEN ===== -->
        <div id="screen-results" class="screen">
            <h1 class="text-header text-center">RESULTS 🏆</h1>
            <div class="results-layout">
                <div class="winners-podium">
                    <div class="podium-stand" data-rank="2">
                        <div id="podium-2-avatar" class="podium-avatar"></div>
                        <div id="podium-2-name" class="podium-name"></div>
                        <div id="podium-2-score" class="podium-score"></div>
                        <div class="podium-rank">#2</div>
                    </div>
                    <div class="podium-stand" data-rank="1">
                        <div id="podium-1-avatar" class="podium-avatar"></div>
                        <div id="podium-1-name" class="podium-name"></div>
                        <div id="podium-1-score" class="podium-score"></div>
                        <div class="podium-rank">#1</div>
                    </div>
                    <div class="podium-stand" data-rank="3">
                        <div id="podium-3-avatar" class="podium-avatar"></div>
                        <div id="podium-3-name" class="podium-name"></div>
                        <div id="podium-3-score" class="podium-score"></div>
                        <div class="podium-rank">#3</div>
                    </div>
                </div>
                <div class="results-ranking-list">
                    <h3>All Rankings</h3>
                    <ul id="results-list" class="results-list leaderboard-list">
                        <!-- Full ranking list will be added here by JS -->
                    </ul>
                </div>
            </div>
            <button id="btn-play-again" class="btn btn-primary" style="margin-top: 2rem;">PLAY AGAIN ▶</button>
        </div>

    </div> <!-- .container -->
    

    <!-- ====== POPUPS ====== -->
    
    <!-- ===== JOIN GAME POPUP ===== -->
    <div id="popup-join-game" class="popup hidden">
        <div class="popup-content">
            <span class="popup-close">&times;</span>
            <h2 class="text-header text-center">JOIN GAME 🎲</h2>
            <div id="join-message" class="message"></div>
            <div class="form-group">
                <label for="join-game-code" class="form-label">Enter code here</label>
                <input type="text" id="join-game-code" class="form-control text-center" placeholder="123456" maxlength="6">
            </div>
            <button id="btn-join-game" class="btn btn-primary">JOIN</button>
        </div>
    </div>
    
    <!-- ===== ENTER NAME/AVATAR POPUP ===== -->
    <div id="popup-enter-name" class="popup hidden">
        <div class="popup-content">
            <!-- No close button, must enter -->
            <h2 class="text-header text-center">YOU'RE IN! 👋</h2>
            <div id="enter-name-message" class="message"></div>
            <div class="form-group">
                <label for="enter-player-name" class="form-label">Enter Your Name</label>
                <input type="text" id="enter-player-name" class="form-control" placeholder="Enter your name here" maxlength="20">
            </div>
            <div class="form-group">
                <label class="form-label">Choose Your Avatar</label>
                <div id="avatar-grid" class="avatar-grid">
                    <!-- Avatars added by JS -->
                </div>
            </div>
            <div class="tip-box">
                <h4>HOW TO PLAY:</h4>
                <p>Find words in the grid. Click the first letter and the last letter of a word, then click 'Submit'. Find up to 5 words to win!</p>
            </div>
            <button id="btn-enter-game" class="btn btn-primary">ENTER GAME ▶</button>
        </div>
    </div>

    <!-- ===== CREATE GAME POPUP ===== -->
    <div id="popup-create-game" class="popup hidden">
        <div class="popup-content">
            <span class="popup-close">&times;</span>
            <h2 class="text-header text-center">CREATE GAME 🕹️</h2>
            <div id="create-message" class="message"></div>
            <div class="form-group">
                <label for="create-game-words" class="form-label">Enter 10-15 words</label>
                <textarea id="create-game-words" class="form-control" placeholder="Enter one word per line (max 15 letters)"></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Select Game Time</label>
                <div class="timer-options">
                    <button class="btn btn-timer-option selected" data-time="3">3 Minutes</button>
                    <button class="btn btn-timer-option" data-time="4">4 Minutes</button>
                    <button class="btn btn-timer-option" data-time="5">5 Minutes</button>
                </div>
            </div>
            <button id="btn-create-game" class="btn btn-primary">CREATE GAME ▶</button>
        </div>
    </div>

    <!-- ===== LOADING SPINNER (hidden) ===== -->
    <div id="popup-loading" class="popup hidden">
        <div style="font-size: 3rem; color: white;">⏳</div>
        <!-- Simple spinner -->
    </div>


    <!-- JavaScript File -->
    <script src="game.js"></script>

</body>
</html>