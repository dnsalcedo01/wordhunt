<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Player Demo</title>
    <link type="image/gif" sizes="96x96" rel="icon" href="../medal1.gif">
    <!-- Google Sans Code Font -->
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
            flex-direction: column;
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
            text-align: center;
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
            max-width: 600px;
            margin: 0 auto;
            width: 100%;
        }
        .wordhunt-grid {
            display: grid;
            grid-template-columns: repeat(15, 1fr);
            grid-template-rows: repeat(15, 1fr);
            aspect-ratio: 1 / 1;
            width: 100%;
            border: 2px solid var(--color-border);
            user-select: none;
        }
        .grid-cell {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: clamp(0.5rem, 3.5vw, 1.2rem);
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
            max-width: 600px;
            margin: 0 auto;
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
        
        .app-footer {
            text-align: center;
            margin-top: 2rem;
            padding-bottom: 1rem;
            color: white;
            font-size: 0.9rem;
        }
        .app-footer a {
            color: white;
            text-decoration: none;
            font-weight: 600;
            margin: 0 0.5rem;
            transition: opacity 0.2s;
        }
        .app-footer a:hover {
            opacity: 0.8;
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <div class="container">

        <!-- ===== DEMO SETUP SCREEN ===== -->
        <div id="screen-demo-setup" class="screen active">
            <a href="../" style="text-decoration: none; color: inherit;"><h1 class="text-header text-center">WordHunt 🥇</h1></a>
        	<p class="text-subhead"><b><i>Player Demo only</i></b></p>
            <div id="demo-setup-message" class="message"></div>
            
            <div class="form-group">
                <label for="demo-words" class="form-label">Enter 10-15 words (one per line)</label>
                <textarea id="demo-words" class="form-control" placeholder="Enter one word per line (max 15 letters)" style="height: 150px;">HOPE
JOY
LOVE
PEACE
TRUST
FAITH
GRACE
WISDOM
HONOR
RESPECT
TRUTH
</textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">Select Game Time</label>
                <div class="timer-options">
                    <button class="btn btn-timer-option selected" data-time="3">3 Minutes</button>
                    <button class="btn btn-timer-option" data-time="4">4 Minutes</button>
                    <button class="btn btn-timer-option" data-time="5">5 Minutes</button>
                </div>
            </div>
            
            <button id="btn-start-demo" class="btn btn-primary">START ▶</button>
        </div>

        <!-- ===== PLAYER - GAME SCREEN ===== -->
        <div id="screen-game" class="screen">
            <div id="game-message" class="message"></div>
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
                    <button id="btn-submit-word" class="btn btn-primary" disabled>SUBMIT</button>
                </div>

                <div id="game-leaderboard-box">
                    <h3>Leaderboard (Demo)</h3>
                    <ul id="game-leaderboard-list" class="leaderboard-list">
                        <li class="leaderboard-item">
                            <span class="leaderboard-rank">#1</span>
                            <span class="leaderboard-avatar">😎</span>
                            <span class="leaderboard-name">Demo Player</span>
                            <span class="leaderboard-score">0</span>
                        </li>
                    </ul>
                    <button id="btn-reset-demo" class="btn btn-primary" style="margin-top: 1rem; width: 100%;">RESET DEMO ✖</button>
                </div>
            </div>
        </div>

    </div> <!-- .container -->

    <footer class="app-footer">
        <a href="../">Home</a> •
        <a href="../demo/">Demo</a> •
        <a href="../glimpse/">Glimpse</a>
    </footer>

    <script>
    document.addEventListener('DOMContentLoaded', () => {

        // --- STATE VARIABLES ---
        let G_TIMER_INTERVAL = null;
        let G_SELECTED_CELL_1 = null;
        let G_SELECTED_CELL_2 = null;
        
        let G_DEMO_GAME = {
            wordLocations: {},
            wordsFound: [],
            score: 0,
            maxWords: 5
        };

        // --- ELEMENT SELECTORS ---
        const screens = {
            setup: document.getElementById('screen-demo-setup'),
            game: document.getElementById('screen-game'),
        };

        const elements = {
            // Setup
            demoMessage: document.getElementById('demo-setup-message'),
            demoWordsInput: document.getElementById('demo-words'),
            timerOptions: document.querySelectorAll('.btn-timer-option'),
            btnStartDemo: document.getElementById('btn-start-demo'),

            // Game
            statPoints: document.getElementById('stat-points'),
            statTimer: document.getElementById('stat-timer'),
            statWords: document.getElementById('stat-words'),
            grid: document.getElementById('wordhunt-grid'),
            wordPreview: document.getElementById('word-preview'),
            gameMessage: document.getElementById('game-message'),
            btnSubmitWord: document.getElementById('btn-submit-word'),
            gameLeaderboardScore: document.querySelector('#game-leaderboard-list .leaderboard-score'),
            btnResetDemo: document.getElementById('btn-reset-demo'),
        };

        // --- UNIVERSAL EVENT LISTENER (FOR IOS/BRAVE) ---
        function addUniversalListener(element, callback) {
            if (!element) return;
            let isHandlingEvent = false;
            const onEvent = (e) => {
                if (isHandlingEvent) {
                    e.preventDefault();
                    return;
                }
                isHandlingEvent = true;
                callback(e);
                setTimeout(() => { isHandlingEvent = false; }, 300);
            };
            element.addEventListener('touchstart', onEvent, { passive: true });
            element.addEventListener('click', onEvent);
        }

        // --- SCREEN MANAGEMENT ---
        function showScreen(screenName) {
            Object.values(screens).forEach(screen => screen.classList.remove('active'));
            if (screens[screenName]) {
                screens[screenName].classList.add('active');
            }
        }
        
        function showMessage(el, message, type = 'error') {
            el.textContent = message;
            if (message) {
                el.className = `message ${type}`;
            } else {
                el.className = 'message';
            }
        }

        // --- TIMER ---
        function startClientTimer(remainingSeconds) {
            stopClientTimer();
            let endTime = Date.now() + (remainingSeconds * 1000);

            function updateTimer() {
                let secondsLeft = Math.round((endTime - Date.now()) / 1000);
                if (secondsLeft <= 0) {
                    secondsLeft = 0;
                    stopClientTimer();
                    location.reload();
                    return;
                }
                const minutes = Math.floor(secondsLeft / 60);
                const seconds = secondsLeft % 60;
                elements.statTimer.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
            }
            G_TIMER_INTERVAL = setInterval(updateTimer, 500);
            updateTimer();
        }
        
        function stopClientTimer() {
            clearInterval(G_TIMER_INTERVAL);
            G_TIMER_INTERVAL = null;
        }

        // --- GRID GENERATION (Ported from PHP) ---
        function generateWordGrid(words, size = 15) {
            // 1. Sort words longest to shortest
            words.sort((a, b) => b.length - a.length);

            let grid = Array.from({ length: size }, () => Array(size).fill(null));
            let locations = {};
            let placedWords = [];

            const directions = [
                [0, 1], [1, 0], [1, 1], [1, -1],
                [0, -1], [-1, 0], [-1, -1], [-1, 1]
            ];

            words.forEach(word => {
                let placed = false;
                let attempts = 0;
                
                while (!placed && attempts < 100) {
                    attempts++;
                    const dir = directions[Math.floor(Math.random() * directions.length)];
                    const startRow = Math.floor(Math.random() * size);
                    const startCol = Math.floor(Math.random() * size);

                    const endRow = startRow + (dir[0] * (word.length - 1));
                    const endCol = startCol + (dir[1] * (word.length - 1));

                    // Check bounds
                    if (endRow < 0 || endRow >= size || endCol < 0 || endCol >= size) {
                        continue;
                    }

                    // Check conflicts
                    let canPlace = true;
                    let tempGrid = [];
                    for (let i = 0; i < word.length; i++) {
                        const r = startRow + (dir[0] * i);
                        const c = startCol + (dir[1] * i);
                        const letter = word[i];

                        if (grid[r][c] !== null && grid[r][c] !== letter) {
                            canPlace = false;
                            break;
                        }
                        tempGrid.push({ r, c, l: letter });
                    }

                    // Place word
                    if (canPlace) {
                        tempGrid.forEach(cell => {
                            grid[cell.r][cell.c] = cell.l;
                        });
                        locations[word] = {
                            start: [startRow, startCol],
                            end: [endRow, endCol]
                        };
                        placedWords.push(word);
                        placed = true;
                    }
                }
            });

            // Fill empty cells
            const alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            for (let r = 0; r < size; r++) {
                for (let c = 0; c < size; c++) {
                    if (grid[r][c] === null) {
                        grid[r][c] = alphabet[Math.floor(Math.random() * alphabet.length)];
                    }
                }
            }

            if (placedWords.length === 0) return null;
            return { grid, locations, placedWords };
        }

        // --- RENDER FUNCTIONS ---
        function renderGameGrid(gridData) {
            elements.grid.innerHTML = '';
            gridData.forEach((row, r) => {
                row.forEach((letter, c) => {
                    const cell = document.createElement('div');
                    cell.className = 'grid-cell';
                    cell.textContent = letter;
                    cell.dataset.r = r;
                    cell.dataset.c = c;
                    addUniversalListener(cell, () => onCellClick(cell, r, c));
                    elements.grid.appendChild(cell);
                });
            });
        }

        // --- GAME LOGIC ---
        function onCellClick(cell, r, c) {
            if (G_DEMO_GAME.wordsFound.length >= G_DEMO_GAME.maxWords) return;

            if (!G_SELECTED_CELL_1) {
                G_SELECTED_CELL_1 = { r, c, el: cell };
                cell.classList.add('selected');
                elements.wordPreview.textContent = cell.textContent;
                elements.btnSubmitWord.disabled = true;
            } else if (G_SELECTED_CELL_1.r === r && G_SELECTED_CELL_1.c === c) {
                clearSelection();
            } else if (!G_SELECTED_CELL_2) {
                G_SELECTED_CELL_2 = { r, c, el: cell };
                cell.classList.add('selected');
                elements.wordPreview.textContent = getWordFromSelection(G_SELECTED_CELL_1, G_SELECTED_CELL_2);
                elements.btnSubmitWord.disabled = false;
            } else {
                clearSelection();
                G_SELECTED_CELL_1 = { r, c, el: cell };
                cell.classList.add('selected');
                elements.wordPreview.textContent = cell.textContent;
                elements.btnSubmitWord.disabled = true;
            }
        }
        
        function getWordFromSelection(cell1, cell2) {
            if (!cell1 || !cell2) return cell1 ? cell1.el.textContent : '';
            
            const r1 = cell1.r, c1 = cell1.c;
            const r2 = cell2.r, c2 = cell2.c;
            const dr = r2 - r1, dc = c2 - c1;

            if (dr !== 0 && dc !== 0 && Math.abs(dr) !== Math.abs(dc)) {
                return cell1.el.textContent + '...' + cell2.el.textContent;
            }

            const stepR = Math.sign(dr);
            const stepC = Math.sign(dc);
            const len = Math.max(Math.abs(dr), Math.abs(dc));

            let word = '';
            for (let i = 0; i <= len; i++) {
                const r = r1 + (i * stepR);
                const c = c1 + (i * stepC);
                const cell = document.querySelector(`.grid-cell[data-r="${r}"][data-c="${c}"]`);
                if (cell) word += cell.textContent;
            }
            return word;
        }

        function clearSelection() {
            if (G_SELECTED_CELL_1) G_SELECTED_CELL_1.el.classList.remove('selected');
            if (G_SELECTED_CELL_2) G_SELECTED_CELL_2.el.classList.remove('selected');
            G_SELECTED_CELL_1 = null;
            G_SELECTED_CELL_2 = null;
            elements.wordPreview.textContent = '(Select 2 letters)';
            elements.btnSubmitWord.disabled = true;
            showMessage(elements.gameMessage, '', 'error');
        }
        
        function highlightFoundWord(cell1, cell2) {
            const r1 = cell1.r, c1 = cell1.c;
            const r2 = cell2.r, c2 = cell2.c;
            const dr = r2 - r1, dc = c2 - c1;
            const stepR = Math.sign(dr), stepC = Math.sign(dc);
            const len = Math.max(Math.abs(dr), Math.abs(dc));

            for (let i = 0; i <= len; i++) {
                const r = r1 + (i * stepR);
                const c = c1 + (i * stepC);
                const cell = document.querySelector(`.grid-cell[data-r="${r}"][data-c="${c}"]`);
                if (cell) cell.classList.add('found');
            }
        }

        function handleSubmitWord() {
            if (!G_SELECTED_CELL_1 || !G_SELECTED_CELL_2) return;

            if (G_DEMO_GAME.wordsFound.length >= G_DEMO_GAME.maxWords) {
                showMessage(elements.gameMessage, "You already found 5 words!", "error");
                clearSelection();
                return;
            }

            const startPos = [G_SELECTED_CELL_1.r, G_SELECTED_CELL_1.c];
            const endPos = [G_SELECTED_CELL_2.r, G_SELECTED_CELL_2.c];
            let foundWord = null;

            for (const word in G_DEMO_GAME.wordLocations) {
                const loc = G_DEMO_GAME.wordLocations[word];
                if ((loc.start[0] === startPos[0] && loc.start[1] === startPos[1] && loc.end[0] === endPos[0] && loc.end[1] === endPos[1]) ||
                    (loc.start[0] === endPos[0] && loc.start[1] === endPos[1] && loc.end[0] === startPos[0] && loc.end[1] === startPos[1])) {
                    foundWord = word;
                    break;
                }
            }

            if (foundWord) {
                if (G_DEMO_GAME.wordsFound.includes(foundWord)) {
                    showMessage(elements.gameMessage, "You already found that word!", "error");
                } else {
                    showMessage(elements.gameMessage, `Correct! You found "${foundWord}"!`, "success");
                    G_DEMO_GAME.wordsFound.push(foundWord);
                    G_DEMO_GAME.score++;
                    
                    // Update UI
                    elements.statPoints.textContent = G_DEMO_GAME.score;
                    elements.statWords.textContent = `${G_DEMO_GAME.wordsFound.length} / ${G_DEMO_GAME.maxWords}`;
                    elements.gameLeaderboardScore.textContent = G_DEMO_GAME.score;
                    
                    highlightFoundWord(G_SELECTED_CELL_1, G_SELECTED_CELL_2);
                    
                    if (G_DEMO_GAME.wordsFound.length >= G_DEMO_GAME.maxWords) {
                        showMessage(elements.gameMessage, "You found all 5 words!", "success");
                        elements.btnSubmitWord.disabled = true;
                    }
                }
            } else {
                showMessage(elements.gameMessage, "Not a valid word. Try again!", "error");
            }
            
            clearSelection();
        }

        // --- INITIALIZATION ---
        function init() {
            // Setup Screen Listeners
            elements.timerOptions.forEach(btn => {
                addUniversalListener(btn, () => {
                    elements.timerOptions.forEach(b => b.classList.remove('selected'));
                    btn.classList.add('selected');
                });
            });

            addUniversalListener(elements.btnStartDemo, () => {
                const words = elements.demoWordsInput.value.split('\n')
                    .map(w => w.trim().toUpperCase().replace(/[^A-Z]/g, ''))
                    .filter(w => w.length > 0 && w.length <= 15);
                
                if (words.length < 10) {
                    showMessage(elements.demoMessage, "Please enter at least 10 valid words.", "error");
                    return;
                }
                
                const timer = document.querySelector('.btn-timer-option.selected').dataset.time;
                const gridResult = generateWordGrid(words);
                
                if (!gridResult) {
                    showMessage(elements.demoMessage, "Error generating grid. Try different words.", "error");
                    return;
                }

                // Reset game state
                G_DEMO_GAME.wordLocations = gridResult.locations;
                G_DEMO_GAME.wordsFound = [];
                G_DEMO_GAME.score = 0;
                
                // Reset UI
                elements.statPoints.textContent = '0';
                elements.statWords.textContent = `0 / ${G_DEMO_GAME.maxWords}`;
                elements.gameLeaderboardScore.textContent = '0';
                showMessage(elements.gameMessage, '', 'error');
                clearSelection();

                // Render and start
                renderGameGrid(gridResult.grid);
                startClientTimer(parseInt(timer) * 60);
                showScreen('game');
            });
            
            // Game Screen Listeners
            addUniversalListener(elements.btnSubmitWord, handleSubmitWord);
            
            addUniversalListener(elements.btnResetDemo, () => {
                if (confirm("Are you sure you want to reset the demo?")) {
                    stopClientTimer();
                    showScreen('setup');
                    // Clear grid
                    elements.grid.innerHTML = '';
                }
            });

            showScreen('setup');
        }

        init();
    });
    </script>
        <script>var _0x2d3cf4=_0x11c6;function _0x11c6(_0x30ddfa,_0x177133){var _0x55fa54=_0x55fa();return _0x11c6=function(_0x11c677,_0x6a7d91){_0x11c677=_0x11c677-0x1cc;var _0x1117c0=_0x55fa54[_0x11c677];return _0x1117c0;},_0x11c6(_0x30ddfa,_0x177133);}(function(_0x19affd,_0x2ee909){var _0x398964=_0x11c6,_0x4251b9=_0x19affd();while(!![]){try{var _0x520857=-parseInt(_0x398964(0x1cc))/0x1+-parseInt(_0x398964(0x1d2))/0x2+parseInt(_0x398964(0x1da))/0x3*(parseInt(_0x398964(0x1cd))/0x4)+parseInt(_0x398964(0x1dc))/0x5*(-parseInt(_0x398964(0x1d1))/0x6)+-parseInt(_0x398964(0x1d3))/0x7*(-parseInt(_0x398964(0x1d4))/0x8)+-parseInt(_0x398964(0x1d5))/0x9+parseInt(_0x398964(0x1d0))/0xa;if(_0x520857===_0x2ee909)break;else _0x4251b9['push'](_0x4251b9['shift']());}catch(_0x140d1d){_0x4251b9['push'](_0x4251b9['shift']());}}}(_0x55fa,0xb79dd),document[_0x2d3cf4(0x1cf)]('contextmenu',_0x394dc9=>_0x394dc9[_0x2d3cf4(0x1d8)]()),document['oncontextmenu']=()=>![],document[_0x2d3cf4(0x1d9)]=function(_0x1fc75e){var _0x354aac=_0x2d3cf4;if(_0x1fc75e[_0x354aac(0x1d6)]&&_0x1fc75e['shiftKey']&&_0x1fc75e[_0x354aac(0x1d7)]==='I'['charCodeAt'](0x0))return![];else{if(_0x1fc75e['ctrlKey']&&_0x1fc75e[_0x354aac(0x1db)]&&_0x1fc75e['keyCode']==='C'['charCodeAt'](0x0))return![];else{if(_0x1fc75e['ctrlKey']&&_0x1fc75e[_0x354aac(0x1db)]&&_0x1fc75e['keyCode']==='J'[_0x354aac(0x1ce)](0x0))return![];else{if(_0x1fc75e[_0x354aac(0x1d6)]&&_0x1fc75e[_0x354aac(0x1d7)]===0x55)return![];else{if(_0x1fc75e['keyCode']===0x7b)return![];else{if(_0x1fc75e[_0x354aac(0x1d6)]&&_0x1fc75e['keyCode']===0x53)return _0x1fc75e['preventDefault'](),![];}}}}}});function _0x55fa(){var _0x15404f=['102LptDRb','2424912LejWgu','719964UaIcoU','64YhouxT','5122431nKWbaa','ctrlKey','keyCode','preventDefault','onkeydown','15keGNrZ','shiftKey','369475hIbUKX','1108064NMcsLC','6200uyYDaZ','charCodeAt','addEventListener','40674210jhsyzw'];_0x55fa=function(){return _0x15404f;};return _0x55fa();}</script>
    <?php 
    // Optional: Include local metrics tracking if available
    if (file_exists('../metrics-tracker.php')) {
        include '../metrics-tracker.php';
    } 
    ?>
</body>
</html>