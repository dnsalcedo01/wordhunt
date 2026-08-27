# Project Memory & Context: Wordhunt

This document serves as a permanent memory bank for the Wordhunt project. It records our previous efforts, resolved issues, and current architectural decisions to ensure context is never lost across sessions or abnormal shutdowns.

## Recent Work History & Milestones

### 1. Mobile Touch Experience & iOS Ghost Click Fixes
**Context ID:** `1ba895c8-208c-4e11-b19d-7d43d3d14179`

**The Issues:** 
- **Overly Sensitive Buttons:** UI buttons triggered instantly upon touch, preventing users from smoothly scrolling the page on mobile devices.
- **Disappearing Grid Selections (iOS Bug):** Tapping grid tiles on iOS triggered "ghost clicks" ~350ms after the initial touch. This ghost click acted as a third, unprompted tap, instantly wiping the user's word selection clean before they could submit it.

**The Resolution:**
- **Standardized Event Listeners:** Completely removed the custom `touchstart` hacks (`addUniversalListener`) from `demo/index.php` and `glimpse/index.php`. Reverted to standard `click` events to allow native, comfortable scrolling.
- **Debounced Grid Clicks:** Implemented a timestamp-based debounce check within `onCellClick` in `game.js`, `demo/index.php`, and `glimpse/index.php`. This securely logs the tap millisecond and strictly ignores any duplicate/ghost clicks on the same cell occurring within a 400ms window.
- **Syntax Patched:** Resolved a broken script block (`function element.addEventListener`) inside `demo/index.php`.

### 2. CI/CD Automated FTP Deployment Setup
**Context ID:** `f4ba31f9-ebc2-4215-9cab-d8cf721c1543`

**The Issue:** 
- Manual file uploads to InfinityFree via their file manager (`filemanager.ai`) were tedious and error-prone. Standard SSH access was unavailable.

**The Resolution:**
- **GitHub Actions Pipeline:** Created `.github/workflows/deploy.yml` utilizing `SamKirkland/FTP-Deploy-Action`.
- **Workflow Trigger:** Pushing to the `main` branch (`git push origin main`) automatically spins up a container to sync only modified files to the live `htdocs` directory via FTP.
- **Exclusions:** The pipeline is intentionally configured to exclude `.git` history, `.github` files, and `.md` files to keep the live server clean.
- **Required User Action:** The user must add their InfinityFree credentials as GitHub Repository Secrets (`FTP_SERVER`, `FTP_USERNAME`, `FTP_PASSWORD`) to enable the action.

## Current State Mastery & Next Steps
- **Codebase Stability:** The core logic now properly handles standard browser click interactions natively on both desktop and mobile without brittle touch hacks.
- **Deployment Status:** The CI/CD infrastructure is fully written.
- **Immediate Next Step:** If the mobile touch bug fixes have not yet been pushed to production, verify that the GitHub FTP secrets are correctly set, then run `git push origin main` to automatically deploy the cleaned-up `game.js`, `demo/index.php`, and `glimpse/index.php` files to the live server.
