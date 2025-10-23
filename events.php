<?php
$eventFiles = glob("events/*.md");
rsort($eventFiles); // Show most recent events first (by filename)
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Xenon | Events</title>
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="styles.css" />
  <style>
    #bgCanvas {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: 0;
      pointer-events: none;
    }
    body {
      position: relative;
    }

    #headers {
      position: relative;
      z-index: 10;
    }

    .events-container {
      max-width: 900px;
      margin: 4rem auto;
      padding: 0 1rem;
      position: relative;
      z-index: 10;
    }

    .events-container h2 {
      text-align: center;
      margin-bottom: 2rem;
    }

    .event {
      background: rgba(0, 0, 0, 0.9);
      border: 1px solid #00ffe1;
      border-radius: 10px;
      padding: 2rem;
      margin-bottom: 2rem;
    }

    .event h1, .event h2, .event h3 {
      color: #ffffff;
    }

    .event p {
      color: #ccc;
    }
    footer {
      position: relative;
      z-index: 10;
    }
  </style>
</head>
<body>
  
  <canvas id="bgCanvas"></canvas>
  <div id="headers"></div>

  <main class="events-container">
    <h2>Upcoming & Past Events</h2>
    <div id="event-list">
      <?php foreach ($eventFiles as $file): ?>
        <div class="event" data-md="<?php echo htmlspecialchars($file); ?>">
          <noscript><p>Enable JavaScript to view event details.</p></noscript>
        </div>
      <?php endforeach; ?>
    </div>
  </main>

  <footer>
    © 2025 Xenon Minecraft Server. All Rights Reserved.
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
  <script>
    async function loadHeaders() {
      const res = await fetch('headers.php');
      const html = await res.text();
      document.getElementById('headers').innerHTML = html;
    }

    async function renderEvents() {
      const events = document.querySelectorAll('[data-md]');
      for (const div of events) {
        const file = div.getAttribute('data-md');
        try {
          const res = await fetch(file);
          const text = await res.text();
          div.innerHTML = marked.parse(text);
        } catch (err) {
          div.innerHTML = "<p style='color:red;'>Failed to load event: " + file + "</p>";
        }
      }
    }

    loadHeaders();
    renderEvents();
  </script>

  <script src="bg-animation.js"></script>
</body>
</html>

