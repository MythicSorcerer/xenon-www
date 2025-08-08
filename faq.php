<?php
$markdownFile = 'faq.md';
$markdownText = file_exists($markdownFile) ? file_get_contents($markdownFile) : '# FAQ not found';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Frequently Asked Questions</title>
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="styles.css" />
</head>
<body>
  <div id="headers"></div>

  <main class="faq-container">
    <div class="faq-title">Frequently Asked Questions</div>
<p style="text-align: center; color: #ccc;">Answers to random questions about Xenon.</p>

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
      const res = await fetch('headers.html');
      const html = await res.text();
      document.getElementById('headers').innerHTML = html;
    }

    document.addEventListener("DOMContentLoaded", () => {
      const md = <?php echo json_encode($markdownText); ?>;
      document.getElementById('faq-content').innerHTML = marked.parse(md);
    });

    loadHeaders();
  </script>
</body>
</html>

