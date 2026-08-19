document.addEventListener('DOMContentLoaded', () => {

    // --- STATE VARIABLES ---
    let G_GAME_STATE = {}; // Holds the entire game state from the server
    let G_PLAYER_ID = null;
    let G_GAME_CODE = null;
    let G_IS_GM = false;
    let G_POLL_INTERVAL = null; // Holds the setInterval ID
    let G_TIMER_INTERVAL = null; // Holds the timer countdown interval ID

    let G_SELECTED_CELL_1 = null; // { r, c, el }
    let G_SELECTED_CELL_2 = null;
    let G_FOUND_WORDS_COORDS = []; // Stores {start, end} of found words

    const AVATARS = ['🤣', '😜', '😍', '😇', '🧐', '😎', '🤨', '🤡', '👽', '🤖', '👾', '🐷', '🐶', '🐼', '🐯', '🐠', '❤️', '💛', '💙', '🤍'];

    // --- ELEMENT SELECTORS ---
    const screens = {
        home: document.getElementById('screen-home'),
        waiting: document.getElementById('screen-waiting'),
        game: document.getElementById('screen-game'),
        gmCode: document.getElementById('screen-gm-code'),
        gmControls: document.getElementById('screen-gm-controls'),
        results: document.getElementById('screen-results'),
    };

    const popups = {
        join: document.getElementById('popup-join-game'),
        enterName: document.getElementById('popup-enter-name'),
        create: document.getElementById('popup-create-game'),
        loading: document.getElementById('popup-loading'),
        instructions: document.getElementById('popup-instructions'),
    };

    const elements = {
        // Home
        btnShowJoin: document.getElementById('btn-show-join'),
        btnShowCreate: document.getElementById('btn-show-create'),
        linkInstructions: document.getElementById('link-instructions'),

        // Popups
        popupCloseBtns: document.querySelectorAll('.popup-close'),
        
        // Join Popup
        joinMessage: document.getElementById('join-message'),
        joinGameCodeInput: document.getElementById('join-game-code'),
        btnJoinGame: document.getElementById('btn-join-game'),
        
        // Enter Name Popup
        avatarGrid: document.getElementById('avatar-grid'),
        enterNameMessage: document.getElementById('enter-name-message'),
        enterPlayerNameInput: document.getElementById('enter-player-name'),
        btnEnterGame: document.getElementById('btn-enter-game'),

        // Create Popup
        createMessage: document.getElementById('create-message'),
        createGameWordsInput: document.getElementById('create-game-words'),
        timerOptions: document.querySelectorAll('.btn-timer-option'),
        btnCreateGame: document.getElementById('btn-create-game'),

        // Waiting Screen
        waitingGameCode: document.getElementById('waiting-game-code'),
        waitingPlayerList: document.getElementById('waiting-player-list'),
        
        // GM Code Screen
        gmGameCode: document.getElementById('gm-game-code'),
        gmPlayerList: document.getElementById('gm-player-list'),
        btnStartGame: document.getElementById('btn-start-game'),
        
        // GM Controls Screen
        gmTimer: document.getElementById('gm-timer'),
        btnEndGame: document.getElementById('btn-end-game'),
        btnCancelGame: document.getElementById('btn-cancel-game'),
        gmLeaderboardList: document.getElementById('gm-leaderboard-list'), // Added GM leaderboard

        // Game Screen
        statPoints: document.getElementById('stat-points'),
        statTimer: document.getElementById('stat-timer'),
        statWords: document.getElementById('stat-words'),
        grid: document.getElementById('wordhunt-grid'),
        wordPreview: document.getElementById('word-preview'),
        gameMessage: document.getElementById('game-message'),
        btnSubmitWord: document.getElementById('btn-submit-word'),
        gameLeaderboardList: document.getElementById('game-leaderboard-list'),

        // Results Screen
        podium: [
            { avatar: document.getElementById('podium-1-avatar'), name: document.getElementById('podium-1-name'), score: document.getElementById('podium-1-score') },
            { avatar: document.getElementById('podium-2-avatar'), name: document.getElementById('podium-2-name'), score: document.getElementById('podium-2-score') },
            { avatar: document.getElementById('podium-3-avatar'), name: document.getElementById('podium-3-name'), score: document.getElementById('podium-3-score') },
        ],
        resultsList: document.getElementById('results-list'),
        btnPlayAgain: document.getElementById('btn-play-again'),
    };

    // --- API HELPER ---
    /**
     * Sends a request to the backend API (index.php)
     * @param {object} body - The data to send (action, payload, etc.)
     * @returns {Promise<object>} - The JSON response from the server
     */
    async function apiRequest(body) {
        showLoading(true);
        let responseData = { success: false, message: 'An unknown error occurred.' };
        try {
            const response = await fetch('index.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify(body),
            });
            
            if (response.ok) {
                responseData = await response.json();
            } else {
                responseData.message = `Server error: ${response.statusText}`;
            }
        } catch (error) {
            console.error('API Request Error:', error);
            responseData.message = 'Network error. Could not reach server.';
        }
        
        showLoading(false);
        return responseData;
    }

    // --- SCREEN & POPUP MANAGEMENT ---
    function showScreen(screenName) {
        Object.values(screens).forEach(screen => screen.classList.remove('active'));
        if (screens[screenName]) {
            screens[screenName].classList.add('active');
        }
    }

    function showPopup(popupName, show = true) {
        Object.values(popups).forEach(popup => popup.classList.add('hidden'));
        if (show && popups[popupName]) {
            popups[popupName].classList.remove('hidden');
        }
    }
    
    function showLoading(show = true) {
        if (show) {
            popups.loading.classList.remove('hidden');
        } else {
            popups.loading.classList.add('hidden');
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

     // --- REVISED: Universal Event Listener ---
    /**
     * Adds a universal event listener for both 'click' and 'touchstart'
     * to ensure responsiveness on all devices and browsers (like iOS/Brave).
     * Prevents the 300ms "ghost click" delay on touch devices.
     * @param {HTMLElement} element The DOM element to attach the listener to.
     * @param {Function} callback The function to execute on tap/click.
     */
    function addUniversalListener(element, callback) {
        if (!element) return; // Safety check

        let isHandlingEvent = false;

        const onEvent = (e) => {
            // If we are already handling an event (e.g., click fired after touchstart)
            // prevent its default action and stop.
            if (isHandlingEvent) {
                e.preventDefault();
                return;
            }

            isHandlingEvent = true;
            callback(e); // Run the actual function

            // Reset the flag after a standard "ghost click" delay
            setTimeout(() => {
                isHandlingEvent = false;
            }, 300); // 300ms is the standard delay
        };

        // We make touchstart PASSIVE. This tells the browser we will NOT
        // preventDefault() on it, which keeps scrolling fast and happy.
        // The touchstart will fire the event for a fast UI response.
        element.addEventListener('touchstart', onEvent, { passive: true });

        // The click event will either fire 300ms later (on old browsers)
        // or immediately (on new browsers with viewport tag).
        // In either case, our isHandlingEvent flag will catch it and stop it.
        element.addEventListener('click', onEvent);
    }
    // --- End of revised helper ---



    // --- POLLING & TIMERS ---
    function startPolling() {
        stopPolling(); // Clear any existing poll
        G_POLL_INTERVAL = setInterval(pollGameState, 2000); // Poll every 2 seconds
    }

    function stopPolling() {
        clearInterval(G_POLL_INTERVAL);
        G_POLL_INTERVAL = null;
    }
    
    function startClientTimer(remainingSeconds) {
        stopClientTimer(); // Clear existing timer
        
        let endTime = Date.now() + (remainingSeconds * 1000);

        function updateTimer() {
            let secondsLeft = Math.round((endTime - Date.now()) / 1000);
            if (secondsLeft <= 0) {
                secondsLeft = 0;
                stopClientTimer();
                // Polling will handle the game end state
            }
            
            const minutes = Math.floor(secondsLeft / 60);
            const seconds = secondsLeft % 60;
            const timeString = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
            
            elements.statTimer.textContent = timeString;
            elements.gmTimer.textContent = timeString;
        }

        G_TIMER_INTERVAL = setInterval(updateTimer, 500);
        updateTimer(); // Run immediately
    }
    
    function stopClientTimer() {
        clearInterval(G_TIMER_INTERVAL);
        G_TIMER_INTERVAL = null;
    }

    // --- GAME LOGIC FUNCTIONS ---
    
    async function pollGameState() {
        if (!G_GAME_CODE) return;

        // Note: Polling is lighter, so we don't use the global loading spinner
        try {
            const response = await fetch('index.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ action: 'pollState', gameCode: G_GAME_CODE, playerId: G_PLAYER_ID }),
            });
            if (!response.ok) throw new Error('Poll response not ok');
            
            const data = await response.json();
            if (data.success) {
                const oldStatus = G_GAME_STATE.status;
                G_GAME_STATE = data.gameData;
                
                // --- Handle Game State Transitions ---
                
                // 1. Waiting Room (Player)
                if (G_GAME_STATE.status === 'waiting' && !G_IS_GM) {
                    showScreen('waiting');
                    renderPlayerList(elements.waitingPlayerList, G_GAME_STATE.players);
                }
                
                // 2. Waiting Room (GM)
                if (G_GAME_STATE.status === 'waiting' && G_IS_GM) {
                    showScreen('gmCode');
                    renderPlayerList(elements.gmPlayerList, G_GAME_STATE.players);
                }
                
                // 3. Game Start (Transition from 'waiting' to 'started')
                if (oldStatus === 'waiting' && G_GAME_STATE.status === 'started') {
                    stopPolling(); // Stop fast polling
                    showScreen(G_IS_GM ? 'gmControls' : 'game');
                    
                    if (!G_IS_GM) {
                        // Player setup
                        renderGameGrid(G_GAME_STATE.grid);
                        startClientTimer(G_GAME_STATE.timerRemaining);
                        updateGameUI();
                    } else {
                        // GM setup
                        startClientTimer(G_GAME_STATE.timerRemaining);
                    }
                    startPolling(); // Restart polling at a normal rate
                }
                
                // 4. Game In Progress (Player)
                if (G_GAME_STATE.status === 'started' && !G_IS_GM) {
                    // Update leaderboard
                    renderLeaderboard(elements.gameLeaderboardList, G_GAME_STATE.leaderboard);
                }
                
                // NEW: Game In Progress (GM)
                if (G_GAME_STATE.status === 'started' && G_IS_GM) {
                    // Update GM leaderboard
                    renderLeaderboard(elements.gmLeaderboardList, G_GAME_STATE.leaderboard);
                }
                
                // 5. Game End (Transition from 'started' to 'ended')
                if (oldStatus === 'started' && G_GAME_STATE.status === 'ended') {
                    stopPolling();
                    stopClientTimer();
                    showScreen('results');
                    renderResults(G_GAME_STATE.leaderboard);
                }
                
                // 6. Game Cancelled
                if (G_GAME_STATE.status === 'cancelled') {
                    stopPolling();
                    stopClientTimer();
                    alert('The Game Master has cancelled the game.');
                    resetGame();
                }

            } else {
                // Handle poll error (e.g., game deleted)
                stopPolling();
                stopClientTimer();
                alert(data.message || 'Error polling game state. Returning to home.');
                resetGame();
            }
        } catch (error) {
            console.error('Polling failed:', error);
            // Don't stop polling, just retry
        }
    }

    /** Resets all state and returns to home screen */
    function resetGame() {
        if (G_PLAYER_ID && G_GAME_CODE && !G_IS_GM) {
            // Explicitly tell the server we are leaving, but don't wait for response
            fetch('index.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ action: 'leaveGame', gameCode: G_GAME_CODE, playerId: G_PLAYER_ID }),
            }).catch(e => console.error("Could not send leave event:", e));
        }

        G_GAME_STATE = {};
        G_PLAYER_ID = null;
        G_GAME_CODE = null;
        G_IS_GM = false;
        G_SELECTED_CELL_1 = null;
        G_SELECTED_CELL_2 = null;
        G_FOUND_WORDS_COORDS = [];

        stopPolling();
        stopClientTimer();
        
        // Clear all dynamic content
        elements.waitingPlayerList.innerHTML = '';
        elements.gmPlayerList.innerHTML = '';
        elements.grid.innerHTML = '';
        elements.gameLeaderboardList.innerHTML = '';
        elements.gmLeaderboardList.innerHTML = ''; // Added to clear GM leaderboard
        elements.resultsList.innerHTML = '';
        elements.joinGameCodeInput.value = '';
        elements.enterPlayerNameInput.value = '';
        elements.createGameWordsInput.value = '';
        
        showScreen('home');
        showPopup(null, false); // Hide all popups
    }

    // --- EVENT HANDLERS ---

    // Home Screen
    addUniversalListener(elements.btnShowJoin, () => { // REPLACED
        showMessage(elements.joinMessage, '', 'error');
        showPopup('join');
    });
    addUniversalListener(elements.btnShowCreate, () => { // REPLACED
        showMessage(elements.createMessage, '', 'error');
        showPopup('create');
    });
    if (elements.linkInstructions) {
        addUniversalListener(elements.linkInstructions, (e) => {
            e.preventDefault();
            showPopup('instructions');
        });
    }
    elements.popupCloseBtns.forEach(btn => {
        addUniversalListener(btn, () => showPopup(null, false)); // REPLACED
    });
    
    // Play Again
    addUniversalListener(elements.btnPlayAgain, resetGame); // REPLACED

    // Join Game Flow
    addUniversalListener(elements.btnJoinGame, async () => { // REPLACED
        const gameCode = elements.joinGameCodeInput.value.toUpperCase();
        if (!gameCode || gameCode.length !== 6) {
            showMessage(elements.joinMessage, 'Please enter a 6-character game code.', 'error');
            return;
        }
        
        const response = await apiRequest({ action: 'joinGame', gameCode: gameCode });
        if (response.success) {
            G_GAME_CODE = gameCode;
            showPopup('enterName');
        } else {
            showMessage(elements.joinMessage, response.message, 'error');
        }
    });

    addUniversalListener(elements.btnEnterGame, async () => { // REPLACED
        const name = elements.enterPlayerNameInput.value;
        const selectedAvatar = document.querySelector('.avatar-option.selected');
        
        if (!name) {
            showMessage(elements.enterNameMessage, 'Please enter your name.', 'error');
            return;
        }
        if (!selectedAvatar) {
            showMessage(elements.enterNameMessage, 'Please choose an avatar.', 'error');
            return;
        }
        
        const avatar = selectedAvatar.textContent;
        const response = await apiRequest({
            action: 'enterGame',
            gameCode: G_GAME_CODE,
            name: name,
            avatar: avatar
        });
        
        if (response.success) {
            G_PLAYER_ID = response.playerId;
            G_IS_GM = false;
            G_GAME_STATE = response.gameData;
            
            elements.waitingGameCode.textContent = `Game Code: ${G_GAME_CODE}`;
            showScreen('waiting');
            showPopup(null, false);
            startPolling();
        } else {
            showMessage(elements.enterNameMessage, response.message, 'error');
        }
    });

    // Create Game Flow
    elements.timerOptions.forEach(btn => {
        addUniversalListener(btn, () => { // REPLACED
            elements.timerOptions.forEach(b => b.classList.remove('selected'));
            btn.classList.add('selected');
        });
    });
    
    addUniversalListener(elements.btnCreateGame, async () => { // REPLACED
        const words = elements.createGameWordsInput.value.split('\n').filter(w => w.trim().length > 0);
        const timer = document.querySelector('.btn-timer-option.selected').dataset.time;
        
        
        if (words.length < 10) {
            showMessage(elements.createMessage, 'Please enter at least 10 words.', 'error');
            return;
        }
        
        const response = await apiRequest({
            action: 'createGame',
            words: words,
            timer: timer
        });
        
        if (response.success) {
            G_GAME_CODE = response.gameCode;
            G_IS_GM = true;
            
            elements.gmGameCode.textContent = G_GAME_CODE;
            showScreen('gmCode');
            showPopup(null, false);
            startPolling(); // Start polling for players
        } else {
            showMessage(elements.createMessage, response.message, 'error');
        }
    });
    
    // GM Controls
    addUniversalListener(elements.btnStartGame, async () => { // REPLACED
        const response = await apiRequest({ action: 'startGame', gameCode: G_GAME_CODE });
        if (response.success) {
            G_GAME_STATE = response.gameData;
            showScreen('gmControls');
            startClientTimer(G_GAME_STATE.timerDuration); // Start GM timer
            // Polling will continue
        } else {
            alert(`Error starting game: ${response.message}`);
        }
    });
    
    addUniversalListener(elements.btnEndGame, async () => { // REPLACED
        if (!confirm('Are you sure you want to end the game early?')) return;
        
        const response = await apiRequest({ action: 'endGame', gameCode: G_GAME_CODE });
        if (response.success) {
            // Polling will detect the 'ended' state and move to results
            stopClientTimer();
        } else {
            alert(`Error ending game: ${response.message}`);
        }
    });

    addUniversalListener(elements.btnCancelGame, async () => { // REPLACED
        if (!confirm('Are you sure you want to cancel this game? This cannot be undone.')) return;
        
        const response = await apiRequest({ action: 'cancelGame', gameCode: G_GAME_CODE });
        if (response.success) {
            // Polling will detect the 'cancelled' state and reset for all players
        } else {
            alert(`Error cancelling game: ${response.message}`);
        }
    });
    
    // Player Game Screen Logic
    addUniversalListener(elements.btnSubmitWord, async () => { // REPLACED
        if (!G_SELECTED_CELL_1 || !G_SELECTED_CELL_2) return;
        
        const response = await apiRequest({
            action: 'submitWord',
            gameCode: G_GAME_CODE,
            playerId: G_PLAYER_ID,
            start: G_SELECTED_CELL_1,
            end: G_SELECTED_CELL_2
        });
        
        if (response.success) {
            if (response.correct) {
                showMessage(elements.gameMessage, `Correct! You found "${response.word}"!`, 'success');
                // Store found word to highlight it
                G_FOUND_WORDS_COORDS.push({ start: G_SELECTED_CELL_1, end: G_SELECTED_CELL_2 });
                highlightFoundWords();
                
                // Update stats immediately
                elements.statPoints.textContent = response.newScore;
                elements.statWords.textContent = `${response.wordsFoundCount} / 5`;
                
                if (response.isGameOver) {
                    alert("You found all 5 words! Waiting for game to end.");
                }
            } else {
                showMessage(elements.gameMessage, response.message || 'Try again!', 'error');
            }
        } else {
            showMessage(elements.gameMessage, response.message, 'error');
        }
        
        // Clear selection after submit
        clearSelection();
    });

    // --- RENDER FUNCTIONS ---
    
    function initAvatarGrid() {
        elements.avatarGrid.innerHTML = '';
        AVATARS.forEach((avatar, index) => {
            const div = document.createElement('div');
            div.className = 'avatar-option';
            div.textContent = avatar;
            addUniversalListener(div, () => { // REPLACED
                document.querySelectorAll('.avatar-option').forEach(el => el.classList.remove('selected'));
                div.classList.add('selected');
            });
            if (index === 0) {
                div.classList.add('selected'); // Select first by default
            }
            elements.avatarGrid.appendChild(div);
        });
    }

    /**
     * Reads the letters from the grid between two selected cells.
     * @param {object} cell1 - { r, c, el }
     * @param {object} cell2 - { r, c, el }
     * @returns {string} - The word formed, or a "..." string if invalid.
     */
    function getWordFromSelection(cell1, cell2) {
        if (!cell1 || !cell2) return cell1 ? cell1.el.textContent : '';
        
        const r1 = cell1.r, c1 = cell1.c;
        const r2 = cell2.r, c2 = cell2.c;
        
        const dr = r2 - r1;
        const dc = c2 - c1;

        // Check for valid line
        // 1. Horizontal: dr = 0
        // 2. Vertical: dc = 0
        // 3. Diagonal: abs(dr) = abs(dc)
        if (dr !== 0 && dc !== 0 && Math.abs(dr) !== Math.abs(dc)) {
            // Invalid line (not straight or 45-deg diagonal)
            return cell1.el.textContent + '...' + cell2.el.textContent;
        }

        // Get step direction (e.g., 0, 1, -1)
        const stepR = Math.sign(dr);
        const stepC = Math.sign(dc);
        const len = Math.max(Math.abs(dr), Math.abs(dc)); // Length of word

        let word = '';
        for (let i = 0; i <= len; i++) {
            const r = r1 + (i * stepR);
            const c = c1 + (i * stepC);
            const cell = document.querySelector(`.grid-cell[data-r="${r}"][data-c="${c}"]`);
            if (cell) {
                word += cell.textContent;
            } else {
                // Should not happen if logic is correct
                return cell1.el.textContent + '...' + cell2.el.textContent;
            }
        }
        return word;
    }

    function renderPlayerList(listElement, players) {
        listElement.innerHTML = ''; // Clear existing list
        if (players.length === 0) {
            listElement.innerHTML = '<li class="player-list-item">No players yet...</li>';
            return;
        }
        players.forEach(player => {
            const li = document.createElement('li');
            li.className = 'player-list-item';
            li.innerHTML = `
                <span class="player-avatar">${player.avatar}</span>
                <span class="player-name">${player.name}</span>
            `;
            listElement.appendChild(li);
        });
    }
    
    function renderGameGrid(gridData) {
        elements.grid.innerHTML = '';
        elements.grid.style.gridTemplateColumns = `repeat(${gridData.length}, 1fr)`;
        elements.grid.style.gridTemplateRows = `repeat(${gridData.length}, 1fr)`;
        
        gridData.forEach((row, r) => {
            row.forEach((letter, c) => {
                const cell = document.createElement('div');
                cell.className = 'grid-cell';
                cell.textContent = letter;
                cell.dataset.r = r;
                cell.dataset.c = c;
                addUniversalListener(cell, () => onCellClick(cell, r, c)); // REPLACED
                elements.grid.appendChild(cell);
            });
        });
    }
    
    function onCellClick(cell, r, c) {
        if (!G_SELECTED_CELL_1) {
            // First selection
            G_SELECTED_CELL_1 = { r, c, el: cell };
            cell.classList.add('selected');
            elements.wordPreview.textContent = cell.textContent;
            elements.btnSubmitWord.disabled = true;
        } else if (G_SELECTED_CELL_1.r === r && G_SELECTED_CELL_1.c === c) {
            // Clicked same cell, de-select
            clearSelection();
        } else if (!G_SELECTED_CELL_2) {
            // Second selection
            G_SELECTED_CELL_2 = { r, c, el: cell };
            cell.classList.add('selected');
            elements.wordPreview.textContent = getWordFromSelection(G_SELECTED_CELL_1, G_SELECTED_CELL_2); // Fixed: Show full word
            elements.btnSubmitWord.disabled = false;
        } else {
            // Third selection, clear all and start over
            clearSelection();
            G_SELECTED_CELL_1 = { r, c, el: cell }; // Select this as new first
            cell.classList.add('selected');
            elements.wordPreview.textContent = cell.textContent;
            elements.btnSubmitWord.disabled = true;
        }
    }
    
    function clearSelection() {
        if (G_SELECTED_CELL_1) G_SELECTED_CELL_1.el.classList.remove('selected');
        if (G_SELECTED_CELL_2) G_SELECTED_CELL_2.el.classList.remove('selected');
        G_SELECTED_CELL_1 = null;
        G_SELECTED_CELL_2 = null;
        elements.wordPreview.textContent = '(Select 2 letters)';
        elements.btnSubmitWord.disabled = true;
        showMessage(elements.gameMessage, '', 'error'); // Clear message
    }
    
    function highlightFoundWords() {
        // Fixed: This now highlights the full word, not just start/end
        G_FOUND_WORDS_COORDS.forEach(coords => {
            const r1 = coords.start.r, c1 = coords.start.c;
            const r2 = coords.end.r, c2 = coords.end.c;

            const dr = r2 - r1;
            const dc = c2 - c1;

            const stepR = Math.sign(dr);
            const stepC = Math.sign(dc);
            const len = Math.max(Math.abs(dr), Math.abs(dc));

            for (let i = 0; i <= len; i++) {
                const r = r1 + (i * stepR);
                const c = c1 + (i * stepC);
                const cell = document.querySelector(`.grid-cell[data-r="${r}"][data-c="${c}"]`);
                if (cell) {
                    cell.classList.add('found'); // Highlight all cells in the line
                }
            }
        });
    }
    
    function updateGameUI() {
        // Find current player's data
        const me = G_GAME_STATE.players.find(p => p.id === G_PLAYER_ID);
        if (me) {
            elements.statPoints.textContent = me.score;
            elements.statWords.textContent = `${me.wordsFound.length} / 5`;
        }
        renderLeaderboard(elements.gameLeaderboardList, G_GAME_STATE.leaderboard);
    }
    
    function renderLeaderboard(listElement, leaderboard) {
        listElement.innerHTML = '';
        leaderboard.forEach((player, index) => {
            const rank = index + 1;
            const li = document.createElement('li');
            li.className = 'leaderboard-item';
            li.innerHTML = `
                <span class="leaderboard-rank">#${rank}</span>
                <span class="leaderboard-avatar">${player.avatar}</span>
                <span class="leaderboard-name">${player.name}</span>
                <span class="leaderboard-score">${player.score}</span>
            `;
            listElement.appendChild(li);
        });
    }
    
    function renderResults(leaderboard) {
        // Clear podium
        elements.podium.forEach((p, i) => {
            const player = leaderboard[i] || null;
            p.avatar.textContent = player ? player.avatar : '❓';
            p.name.textContent = player ? player.name : '...';
            p.score.textContent = player ? `${player.score} pts` : '';
        });
        
        // Fill full ranking list
        elements.resultsList.innerHTML = '';
        renderLeaderboard(elements.resultsList, leaderboard);
    }

    // --- INITIALIZATION ---
    function init() {
        showScreen('home');
        showPopup(null, false); // Hide all popups
        initAvatarGrid();
    }

    init(); // Start the app
});