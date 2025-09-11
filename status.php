<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Xenon | Server Status</title>
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="styles.css" />
  <style>
    .status-container {
      max-width: 600px;
      margin: 5rem auto;
      text-align: center;
      padding: 2rem;
      background: rgba(0, 0, 0, 0.2);
      border: 1px solid #00ffe1;
      border-radius: 12px;
    }
    .status-title {
      font-size: 2rem;
      margin-bottom: 1rem;
    }
    .status-online {
      color: #00ff88;
      font-weight: bold;
    }
    .status-offline {
      color: #ff5555;
      font-weight: bold;
    }
    .player-count {
      font-size: 1.25rem;
      margin-top: 0.5rem;
      color: #ccc;
    }
  </style>
</head>
<body>
  <div id="headers"></div>

  <main>
    <div class="status-container">
      <div class="status-title">Minecraft Server Status</div>
      <div id="status">Loading...</div>
      <div id="players" class="player-count"></div>
    </div>
  </main>

  <footer>
    © 2025 Xenon Minecraft Server. All Rights Reserved.
  </footer>

  <script>
    async function loadHeaders() {
      const res = await fetch('headers.php');
      const html = await res.text();
      document.getElementById('headers').innerHTML = html;
    }

    async function checkServerStatus() {
      try {
        const response = await fetch("https://api.mcsrvstat.us/2/xenon.hopto.org");
        const data = await response.json();

        const statusDiv = document.getElementById("status");
        const playersDiv = document.getElementById("players");

        if (data.online) {
          statusDiv.innerHTML = `<span class="status-online">🟢 Online</span>`;
          playersDiv.textContent = `Players: ${data.players.online} / ${data.players.max}`;
        } else {
          statusDiv.innerHTML = `<span class="status-offline">🔴 Offline</span>`;
          playersDiv.textContent = "";
        }
      } catch (err) {
        document.getElementById("status").innerHTML = `<span class="status-offline">Error checking server status</span>`;
        document.getElementById("players").textContent = "";
        console.error(err);
      }
    }

    loadHeaders();
    checkServerStatus();
    setInterval(checkServerStatus, 5000);
  </script>
</body>
</html>

