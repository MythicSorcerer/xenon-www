<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Xenon | Homepage</title>
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="styles.css" />
  <style>
    html {
      background: #000000;
    }

    #bgCanvas {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: -1;
      pointer-events: none;
    }

    body {
      position: relative;
    }

    @keyframes glow {
      from {
        text-shadow: 0 0 10px #00ffe1, 0 0 20px #00ffe1;
      }
      to {
        text-shadow: 0 0 20px #00ffe1, 0 0 30px #00ffe1, 0 0 40px #00ffe1;
      }
    }

    header h1 {
      animation: glow 2s ease-in-out infinite alternate;
    }

    /* Click to copy functionality */
    .ip-box {
      position: relative;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .ip-box:hover {
      box-shadow: 0 0 20px rgba(0, 255, 225, 0.3);
    }

    .ip-box code {
      user-select: all;
    }

    .copy-hint {
      position: absolute;
      top: 10px;
      right: 15px;
      font-size: 0.85rem;
      color: #00ffe1;
      opacity: 0;
      transition: opacity 0.3s ease;
      pointer-events: none;
    }

    .ip-box:hover .copy-hint {
      opacity: 0.7;
    }

    .copy-hint.copied {
      opacity: 1 !important;
      color: #00ff88;
    }
  </style>
</head>
<body>
  <canvas id="bgCanvas"></canvas>
  
  <!-- Headers will be loaded here -->
  <div id="headers"></div>
  
  <header>
    <h1>XENON</h1>
    <p>Season 1 - SMP/Lifesteal</p>
  </header>
  
  <div class="ip-box" onclick="copyIP()">
    <span class="copy-hint" id="copyHint">📋 Click to copy</span>
    <p>Connect Now:</p>
    <code id="serverIP">xenon.hopto.org</code>
  </div>
  
  <section class="features">
    <div class="feature">
      <h3>🌎 Custom World Generation</h3>
      <p>Vast world generation with loads of structures and features.</p>
    </div>
    <div class="feature">
      <h3>💎 Eternal Grind</h3>
      <p>Grind loads of gear to easily destroy any other player and rein supreme.</p>
    </div>
    <div class="feature">
      <h3>🛡️ Custom PvP Mechanics</h3>
      <p>Engage in skill-based combat with modern weaponry and enhanced anti-cheat systems.</p>
    </div>
    <div class="feature">
      <h3>🌌 Unique World Generation</h3>
      <p>Explore bioluminescent lush caves, floating islands, and ancient ruins.</p>
    </div>
  </section>
  
  <footer>
    © 2025 Xenon Minecraft Server. All Rights Reserved.
  </footer>
  
  <script>
    async function loadHeaders() {
      const res = await fetch('headers.php');
      const text = await res.text();
      document.getElementById('headers').innerHTML = text;
    }
    loadHeaders();

    // Copy IP functionality
    let copyTimeout;
    function copyIP() {
      const ip = document.getElementById('serverIP').textContent;
      const hint = document.getElementById('copyHint');
      
      navigator.clipboard.writeText(ip).then(() => {
        hint.textContent = '✓ Copied to clipboard!';
        hint.classList.add('copied');
        
        clearTimeout(copyTimeout);
        copyTimeout = setTimeout(() => {
          hint.textContent = '📋 Click to copy';
          hint.classList.remove('copied');
        }, 4000);
      }).catch(err => {
        console.error('Failed to copy:', err);
      });
    }
  </script>

  <script src="bg-animation.js"></script>
</body>
</html>
