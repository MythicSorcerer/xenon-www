<?php
$articleFiles = glob("articles/*.md");
rsort($articleFiles); // Reverse alphabetical order (most recent first)
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Xenon | News</title>
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="styles.css" />
  <style>
    .news-container {
      max-width: 900px;
      margin: 4rem auto;
      padding: 0 1rem;
    }

    .news-container h2 {
      text-align: center;
      margin-bottom: 2rem;
    }

    .article {
      background: rgba(0, 0, 0, 0.2);
      border: 1px solid #00ffe1;
      border-radius: 10px;
      padding: 2rem;
      margin-bottom: 2rem;
    }

    .article h1, .article h2, .article h3 {
      color: #ffffff;
    }

    .article p {
      color: #ccc;
    }
  </style>
</head>
<body>
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
  <script>
    async function loadHeaders() {
      const res = await fetch('headers.php');
      const html = await res.text();
      document.getElementById('headers').innerHTML = html;
    }

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

    loadHeaders();
    renderArticles();
  </script>
</body>
</html>

