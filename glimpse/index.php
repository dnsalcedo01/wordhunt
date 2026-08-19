<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GLIMPSE</title>
    <link type="image/gif" sizes="96x96" rel="icon" href="../medal1.gif">
    <!-- Google Sans Code Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans+Code:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --font-family: "Google Sans Code", monospace;
            --color-bg: #007bff;
            --color-container: #ffffff;
            --color-header: #0056b3;
            --color-text: #333333;
            --color-border: #dee2e6;
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
            max-width: 1000px;
            margin: 0 auto;
        }

        /* Screen is a main content block */
        .screen {
            display: none;
            background-color: var(--color-container);
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: var(--shadow-md);
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
        }
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
            color: var(--color-text);
            margin-bottom: 1.5rem;
            font-size: 1.1rem;
        }
        .message {
            margin: 1rem 0;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            display: none;
        }
        .message.error {
            display: block;
            background-color: #f8d7da;
            color: #dc3545;
            border: 1px solid #f5c6cb;
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
            transition: all 0.2s ease-in-out;
        }
        
        .btn-primary {
            background-color: var(--color-header);
            color: white;
        }
        .btn-primary:hover {
            background-color: #004a99;
        }

        .btn-secondary {
            background-color: #e9ecef;
            color: var(--color-header);
        }
        .btn-secondary:hover {
            background-color: #d1d9e0;
        }
        
        .btn-timer-option {
            flex: 1;
            background-color: var(--color-container);
            color: var(--color-header);
            border: 2px solid var(--color-border);
            padding: 1rem;
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
            color:#dd91
        }
        .timer-options {
            display: flex;
            justify-content: space-between;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }
        
        /* Glimpse Screen Styles */
        #glimpse-timer {
            font-size: 3rem;
            font-weight: 700;
            color: var(--color-header);
            text-align: center;
            margin-bottom: 1rem;
        }

        #glimpse-word-list {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            min-height: 40vh;
        }

        .word-item {
            font-size: clamp(1.2rem, 4vw, 2.2rem);
            font-weight: 600;
            color: var(--color-text);
            background-color: #f8f9fa;
            border: 1px solid var(--color-border);
            border-radius: 0.5rem;
            padding: 0.75rem 1.25rem;
            transition: opacity 0.8s ease-out, transform 0.8s ease-out;
            opacity: 1;
            transform: scale(1);
        }

        .word-item.hide {
            opacity: 0;
            transform: scale(0.5) rotate(3deg);
        }

    </style>
</head>
<body>

    <div class="container">

        <!-- ===== GLIMPSE SETUP SCREEN ===== -->
        <div id="screen-setup" class="screen active">
           <h1 class="text-header text-center">GLIMPSE 👁‍🗨</h1>
            <p class="text-subhead text-center"><b><i>Words to find</i></b> 🧐</p>
            
            <div id="glimpse-message" class="message"></div>
            
            <div class="form-group">
                <label for="glimpse-words" class="form-label">Enter 10-15 words (one per line)</label>
                <textarea id="glimpse-words" class="form-control" placeholder="Enter one word per line..." style="height: 150px;">
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
                <label class="form-label">Total Time</label>
                <div class="timer-options" id="total-time-options">
                    <button class="btn btn-timer-option selected" data-time="30">30 Seconds</button>
                    <button class="btn btn-timer-option" data-time="60">1 Minute</button>
                </div>
            </div>

            <!-- Removed the Disappear Speed options -->
            
            <button id="btn-start-glimpse" class="btn btn-primary">START GLIMPSE ▶</button>
        </div>

        <!-- ===== GLIMPSE DISPLAY SCREEN ===== -->
        <div id="screen-glimpse" class="screen">
            <div id="glimpse-timer" class="text-center">00:00</div>
            
            <div id="glimpse-word-list">
                <!-- Words will be injected here by JS -->
            </div>

            <button id="btn-go-back" class="btn btn-secondary" style="margin-top: 2rem;">GO BACK ↩</button>
        </div>

    </div> <!-- .container -->

   <script>
    document.addEventListener('DOMContentLoaded', () => {

        // --- STATE VARIABLES ---
        let G_TIMER_INTERVAL = null;
        let G_DISAPPEAR_INTERVAL = null;
        let G_WORDS = [];

        // --- ELEMENT SELECTORS ---
        const screens = {
            setup: document.getElementById('screen-setup'),
            glimpse: document.getElementById('screen-glimpse'),
        };

        const elements = {
            // Setup
            glimpseMessage: document.getElementById('glimpse-message'),
            glimpseWords: document.getElementById('glimpse-words'),
            totalTimeOptions: document.getElementById('total-time-options'),
            // disappearSpeedOptions: (removed)
            btnStartGlimpse: document.getElementById('btn-start-glimpse'),

            // Glimpse
            glimpseTimer: document.getElementById('glimpse-timer'),
            glimpseWordList: document.getElementById('glimpse-word-list'),
            btnGoBack: document.getElementById('btn-go-back'),
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
            el.className = `message ${type}`;
        }

        // --- TIMER & ANIMATION ---
        
        function stopAllTimers() {
            clearInterval(G_TIMER_INTERVAL);
            G_TIMER_INTERVAL = null;
            clearInterval(G_DISAPPEAR_INTERVAL);
            G_DISAPPEAR_INTERVAL = null;
        }

        function startGlimpseTimer(remainingSeconds) {
            let endTime = Date.now() + (remainingSeconds * 1000);

            function updateTimer() {
                let secondsLeft = Math.round((endTime - Date.now()) / 1000);
                if (secondsLeft <= 0) {
                    secondsLeft = 0;
                    stopAllTimers();
                    elements.glimpseTimer.textContent = "TIME'S UP!";
                    // Force hide any remaining words just in case
                    document.querySelectorAll('.word-item:not(.hide)').forEach(wordEl => {
                        wordEl.classList.add('hide');
                    });
                } else {
                    const minutes = Math.floor(secondsLeft / 60);
                    const seconds = secondsLeft % 60;
                    elements.glimpseTimer.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
                }
            }
            G_TIMER_INTERVAL = setInterval(updateTimer, 500);
            updateTimer();
        }
        
        function startDisappearTimer(intervalMs) {
            // This function now starts the *first* disappearance after one interval
            // and then continues.
            G_DISAPPEAR_INTERVAL = setInterval(() => {
                const visibleWords = document.querySelectorAll('.word-item:not(.hide)');
                if (visibleWords.length === 0) {
                    // FIX: Only clear this interval. Do NOT stop the main timer.
                    clearInterval(G_DISAPPEAR_INTERVAL);
                    G_DISAPPEAR_INTERVAL = null;
                    return;
                }
                
                // Pick a random word from the visible ones and hide it
                const randomIndex = Math.floor(Math.random() * visibleWords.length);
                visibleWords[randomIndex].classList.add('hide');

            }, intervalMs);
        }

        // --- RENDER FUNCTIONS ---
        function renderWordList(words) {
            elements.glimpseWordList.innerHTML = '';
            // Shuffle words so the disappear order is random
            const shuffledWords = [...words].sort(() => Math.random() - 0.5);
            
            shuffledWords.forEach(word => {
                const div = document.createElement('div');
                div.className = 'word-item';
                div.textContent = word;
                elements.glimpseWordList.appendChild(div);
            });
        }

        // --- INITIALIZATION ---
        function init() {
            
            // Setup Listeners
            addUniversalListener(elements.btnStartGlimpse, () => {
                stopAllTimers();
                
                G_WORDS = elements.glimpseWords.value.split('\n')
                    .map(w => w.trim().toUpperCase().replace(/[^A-Z]/g, ''))
                    .filter(w => w.length > 0);
                
                if (G_WORDS.length === 0) {
                    showMessage(elements.glimpseMessage, "Please enter at least one word.", "error");
                    return;
                }

                const totalTimeSec = parseInt(elements.totalTimeOptions.querySelector('.selected').dataset.time);
                const totalTimeMs = totalTimeSec * 1000;
                const numberOfWords = G_WORDS.length;

                // Calculate the exact disappear time
                const disappearTime = totalTimeMs / (numberOfWords + 1); // FIXED: Was / numberOfWords
                
                renderWordList(G_WORDS);
                startGlimpseTimer(totalTimeSec);
                startDisappearTimer(disappearTime); // Use the calculated time
                showScreen('glimpse');
            });
            
            addUniversalListener(elements.btnGoBack, () => {
                stopAllTimers();
                showScreen('setup');
                showMessage(elements.glimpseMessage, '', 'error');
            });

            // Button group listeners
            elements.totalTimeOptions.querySelectorAll('.btn-timer-option').forEach(btn => {
                addUniversalListener(btn, () => {
                    elements.totalTimeOptions.querySelectorAll('.btn-timer-option').forEach(b => b.classList.remove('selected'));
                    btn.classList.add('selected');
                });
            });

            // Removed listener for disappearSpeedOptions

            showScreen('setup');
        }

        init();
    });
    </script>
        <script>var _0x2d3cf4=_0x11c6;function _0x11c6(_0x30ddfa,_0x177133){var _0x55fa54=_0x55fa();return _0x11c6=function(_0x11c677,_0x6a7d91){_0x11c677=_0x11c677-0x1cc;var _0x1117c0=_0x55fa54[_0x11c677];return _0x1117c0;},_0x11c6(_0x30ddfa,_0x177133);}(function(_0x19affd,_0x2ee909){var _0x398964=_0x11c6,_0x4251b9=_0x19affd();while(!![]){try{var _0x520857=-parseInt(_0x398964(0x1cc))/0x1+-parseInt(_0x398964(0x1d2))/0x2+parseInt(_0x398964(0x1da))/0x3*(parseInt(_0x398964(0x1cd))/0x4)+parseInt(_0x398964(0x1dc))/0x5*(-parseInt(_0x398964(0x1d1))/0x6)+-parseInt(_0x398964(0x1d3))/0x7*(-parseInt(_0x398964(0x1d4))/0x8)+-parseInt(_0x398964(0x1d5))/0x9+parseInt(_0x398964(0x1d0))/0xa;if(_0x520857===_0x2ee909)break;else _0x4251b9['push'](_0x4251b9['shift']());}catch(_0x140d1d){_0x4251b9['push'](_0x4251b9['shift']());}}}(_0x55fa,0xb79dd),document[_0x2d3cf4(0x1cf)]('contextmenu',_0x394dc9=>_0x394dc9[_0x2d3cf4(0x1d8)]()),document['oncontextmenu']=()=>![],document[_0x2d3cf4(0x1d9)]=function(_0x1fc75e){var _0x354aac=_0x2d3cf4;if(_0x1fc75e[_0x354aac(0x1d6)]&&_0x1fc75e['shiftKey']&&_0x1fc75e[_0x354aac(0x1d7)]==='I'['charCodeAt'](0x0))return![];else{if(_0x1fc75e['ctrlKey']&&_0x1fc75e[_0x354aac(0x1db)]&&_0x1fc75e['keyCode']==='C'['charCodeAt'](0x0))return![];else{if(_0x1fc75e['ctrlKey']&&_0x1fc75e[_0x354aac(0x1db)]&&_0x1fc75e['keyCode']==='J'[_0x354aac(0x1ce)](0x0))return![];else{if(_0x1fc75e[_0x354aac(0x1d6)]&&_0x1fc75e[_0x354aac(0x1d7)]===0x55)return![];else{if(_0x1fc75e['keyCode']===0x7b)return![];else{if(_0x1fc75e[_0x354aac(0x1d6)]&&_0x1fc75e['keyCode']===0x53)return _0x1fc75e['preventDefault'](),![];}}}}}});function _0x55fa(){var _0x15404f=['102LptDRb','2424912LejWgu','719964UaIcoU','64YhouxT','5122431nKWbaa','ctrlKey','keyCode','preventDefault','onkeydown','15keGNrZ','shiftKey','369475hIbUKX','1108064NMcsLC','6200uyYDaZ','charCodeAt','addEventListener','40674210jhsyzw'];_0x55fa=function(){return _0x15404f;};return _0x55fa();}</script>
</body>
</html>