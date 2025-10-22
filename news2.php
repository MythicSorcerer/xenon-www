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
      background: rgba(0, 0, 0, 0.9);
      border: 1px solid #00ffe1;
      border-radius: 10px;
      padding: 2rem;
      margin-bottom: 2rem;
      backdrop-filter: blur(10px);
    }
    .article h1, .article h2, .article h3 {
      color: #ffffff;
    }
    .article p {
      color: #ccc;
    }

    footer {
      position: relative;
      z-index: 1;
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

  <script>
    const canvas = document.getElementById('bgCanvas');
    const ctx = canvas.getContext('2d');

    function resize() {
      canvas.width = window.innerWidth;
      canvas.height = window.innerHeight;
    }
    resize();
    window.addEventListener('resize', resize);

    // Particle system for flowing energy
    class Particle {
      constructor() {
        this.reset();
      }

      reset() {
        this.x = Math.random() * canvas.width;
        this.y = Math.random() * canvas.height;
        this.vx = (Math.random() - 0.5) * 0.5;
        this.vy = (Math.random() - 0.5) * 0.5;
        this.life = Math.random() * 100 + 100;
        this.maxLife = this.life;
        this.size = Math.random() * 2 + 1;
      }

      update() {
        this.x += this.vx;
        this.y += this.vy;
        this.life--;

        if (this.life <= 0 || this.x < 0 || this.x > canvas.width || 
            this.y < 0 || this.y > canvas.height) {
          this.reset();
        }
      }

      draw() {
        const alpha = this.life / this.maxLife;
        ctx.fillStyle = `rgba(0, 255, 225, ${alpha * 0.6})`;
        ctx.beginPath();
        ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
        ctx.fill();
      }
    }

    // Grid lines with wave distortion
    class GridLine {
      constructor(isVertical, position) {
        this.isVertical = isVertical;
        this.position = position;
        this.offset = Math.random() * Math.PI * 2;
      }

      draw(time) {
        ctx.strokeStyle = 'rgba(0, 255, 225, 0.1)';
        ctx.lineWidth = 1;
        ctx.beginPath();

        if (this.isVertical) {
          for (let y = 0; y < canvas.height; y += 5) {
            const wave = Math.sin(y * 0.01 + time * 0.001 + this.offset) * 15;
            const x = this.position + wave;
            if (y === 0) ctx.moveTo(x, y);
            else ctx.lineTo(x, y);
          }
        } else {
          for (let x = 0; x < canvas.width; x += 5) {
            const wave = Math.sin(x * 0.01 + time * 0.001 + this.offset) * 15;
            const y = this.position + wave;
            if (x === 0) ctx.moveTo(x, y);
            else ctx.lineTo(x, y);
          }
        }
        ctx.stroke();
      }
    }

    // Hexagon pattern
    class Hexagon {
      constructor(x, y, size) {
        this.x = x;
        this.y = y;
        this.size = size;
        this.rotation = 0;
        this.rotationSpeed = (Math.random() - 0.5) * 0.001;
        this.pulseOffset = Math.random() * Math.PI * 2;
      }

      draw(time) {
        const pulse = Math.sin(time * 0.002 + this.pulseOffset) * 0.3 + 0.7;
        const alpha = 0.08 * pulse;
        
        ctx.save();
        ctx.translate(this.x, this.y);
        ctx.rotate(this.rotation);
        
        ctx.strokeStyle = `rgba(0, 255, 225, ${alpha})`;
        ctx.lineWidth = 1.5;
        ctx.beginPath();
        
        for (let i = 0; i < 6; i++) {
          const angle = (Math.PI / 3) * i;
          const x = Math.cos(angle) * this.size * pulse;
          const y = Math.sin(angle) * this.size * pulse;
          if (i === 0) ctx.moveTo(x, y);
          else ctx.lineTo(x, y);
        }
        ctx.closePath();
        ctx.stroke();
        
        ctx.restore();
        this.rotation += this.rotationSpeed;
      }
    }

    // Initialize elements
    const particles = Array.from({length: 100}, () => new Particle());
    
    const gridLines = [];
    for (let x = 100; x < canvas.width; x += 100) {
      gridLines.push(new GridLine(true, x));
    }
    for (let y = 100; y < canvas.height; y += 100) {
      gridLines.push(new GridLine(false, y));
    }

    const hexagons = [];
    for (let x = 150; x < canvas.width; x += 200) {
      for (let y = 150; y < canvas.height; y += 200) {
        hexagons.push(new Hexagon(x, y, 40));
      }
    }

    // Scanning line effect
    let scanY = 0;

    function animate(time) {
      ctx.fillStyle = 'rgba(15, 15, 26, 0.1)';
      ctx.fillRect(0, 0, canvas.width, canvas.height);

      // Draw grid
      gridLines.forEach(line => line.draw(time));

      // Draw hexagons
      hexagons.forEach(hex => hex.draw(time));

      // Update and draw particles
      particles.forEach(p => {
        p.update();
        p.draw();
      });

      // Draw connections between close particles
      ctx.strokeStyle = 'rgba(0, 255, 225, 0.15)';
      ctx.lineWidth = 0.5;
      for (let i = 0; i < particles.length; i++) {
        for (let j = i + 1; j < particles.length; j++) {
          const dx = particles[i].x - particles[j].x;
          const dy = particles[i].y - particles[j].y;
          const dist = Math.sqrt(dx * dx + dy * dy);
          if (dist < 100) {
            ctx.beginPath();
            ctx.moveTo(particles[i].x, particles[i].y);
            ctx.lineTo(particles[j].x, particles[j].y);
            ctx.stroke();
          }
        }
      }

      // Scanning line
      scanY = (scanY + 2) % canvas.height;
      ctx.fillStyle = 'rgba(0, 255, 225, 0.05)';
      ctx.fillRect(0, scanY, canvas.width, 2);

      requestAnimationFrame(animate);
    }

    animate(0);
  </script>
</body>
</html>
