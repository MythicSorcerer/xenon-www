<?php
$articleFiles = glob("../news-md/*.md");
rsort($articleFiles); // Reverse alphabetical order (most recent first)
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Xenon | News</title>
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="/styles.css" />
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

    .news-container {
      max-width: 900px;
      margin: 4rem auto;
      padding: 0 1rem;
      position: relative;
      z-index: 10;
    }
    .news-container h2 {
      text-align: center;
      margin-bottom: 2rem;
      text-shadow: 0 0 10px #00ffe1;
    }
    .article {
      background: rgba(0, 0, 0, 0.5);
      border: 1px solid #00ffe1;
      border-radius: 10px;
      padding: 2rem;
      margin-bottom: 2rem;
      backdrop-filter: blur(5px);
    }
    .article h1, .article h2, .article h3 {
      color: #ffffff;
    }
    .article p {
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
  
  <main class="news-container">
    <h2>Latest News</h2>
    <div id="news-articles">
      <?php foreach ($articleFiles as $file): ?>
        <div class="article" data-md="<?php echo htmlspecialchars($file); ?>">
          <noscript><p>Enable JavaScript to read this article.</p></noscript>
        </div>
      <?php endforeach; ?>
    </div>
  </main>

  <footer>
    © 2025 Xenon Minecraft Server. All Rights Reserved.
  </footer>
  <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
  <script src="/loadHeaders.js"></script>
  <script>
    async function renderArticles() {
      const articles = document.querySelectorAll('[data-md]');
      for (const div of articles) {
        const file = div.getAttribute('data-md');
        try {
          const res = await fetch(file);
          const text = await res.text();
          div.innerHTML = marked.parse(text);
        } catch (err) {
          div.innerHTML = "<p style='color:red;'>Failed to load article: " + file + "</p>";
        }
      }
    }
    renderArticles();
  </script>

  <script src="/bg-animation.js"></script>
</body>
</html>
