# WordHunt 🕵️‍♂️📝

WordHunt is a lightweight, real-time multiplayer word search game built entirely with vanilla PHP, JavaScript, and CSS. It features a completely database-free architecture, relying on JSON file storage and file-locking to handle concurrent player actions efficiently.

## ✨ Features
- **Real-Time Multiplayer:** Play instantly with friends using a unique 6-letter game code.
- **Game Master Controls:** The Game Master (GM) creates the game, inputs words to be guessed, sets the timer, and controls the game flow.
- **Dynamic Leaderboards:** Live updating scoreboard that ranks players based on total words found, using the speed of their first find as a tie-breaker.
- **No Database Required:** Game states are securely stored in a lightweight `games/` folder using JSON, with automatic cleanup of old game files.
- **Responsive Design:** Mobile-friendly UI that scales perfectly to any device.
- **Demo & Glimpse Modes:** Additional modes available for game demo and to show words to guess for a limited time.

## 🚀 Getting Started

Since WordHunt uses vanilla PHP, setting it up is incredibly easy. 

### Prerequisites
- A local web server with PHP support (e.g., XAMPP, WAMP, MAMP, or Laravel Valet).

### Installation
1. Clone this repository into your web server's document root (e.g., `htdocs` for XAMPP):
   ```bash
   git clone https://github.com/yourusername/wordhunt.git
   ```
2. Ensure the `games/` directory has write permissions so the PHP scripts can create and modify JSON game files.
3. Open your browser and navigate to `http://localhost/wordhunt`.

## 🎮 How to Play
1. **Create a Game:** Enter 10-15 words (one per line), select a timer duration, and hit Create. You will be given a 6-letter Game Code.
2. **Join a Game:** Other players visit the site, enter the Game Code, choose an avatar and name, and wait in the lobby.
3. **Start:** The Game Master starts the game. Players finds the word on the grid by selecting the first and last letter of it.
4. **Win:** The player who finds the most words before the timer runs out wins!

## 📜 License & Monetization

This project is licensed under the **MIT License**. 
---
*Built with ❤️ using vanilla PHP, JS, and CSS.*
