<?php
$markdownFile = 'rules.md';
$markdownText = file_exists($markdownFile) ? file_get_contents($markdownFile) : '# Rules not found';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Server Rules</title>
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="styles.css" />
</head>
<body>
  <div id="headers"></div>

  <main class="faq-container">
    <div class="faq-title">Server Rules</div>
    <div id="faq-content" class="faq-content">
      <!-- Markdown will be rendered here -->
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

    // Load and render the rules markdown from PHP
    document.addEventListener("DOMContentLoaded", () => {
      const md = <?php echo json_encode($markdownText); ?>;
      document.getElementById('faq-content').innerHTML = marked.parse(md);
    });

    loadHeaders();
  </script>
</body>
</html>

