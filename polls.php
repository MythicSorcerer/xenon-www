<?php
session_start();
require_once 'admin_config.php';
require_once 'db_init.php';

// Initialize database
$db = getDatabaseConnection();

// Create polls table if it doesn't exist
$db->exec('CREATE TABLE IF NOT EXISTS polls (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    description TEXT,
    options TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    ends_at DATETIME,
    is_active INTEGER DEFAULT 1,
    created_by TEXT
)');

// Create poll_votes table if it doesn't exist
$db->exec('CREATE TABLE IF NOT EXISTS poll_votes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    poll_id INTEGER NOT NULL,
    user_id INTEGER,
    username TEXT,
    ip_address TEXT,
    option_value TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (poll_id) REFERENCES polls (id)
)');

// Handle vote submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['poll_id'], $_POST['option'])) {
    $poll_id = $_POST['poll_id'];
    $option = $_POST['option'];
    $user_id = $_SESSION['user_id'] ?? null;
    $username = $_SESSION['username'] ?? 'Anonymous';
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    // Check if user has already voted on this poll
    $check_stmt = $db->prepare('SELECT id FROM poll_votes WHERE poll_id = :poll_id AND (user_id = :user_id OR ip_address = :ip_address)');
    $check_stmt->bindValue(':poll_id', $poll_id, SQLITE3_INTEGER);
    $check_stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
    $check_stmt->bindValue(':ip_address', $ip_address, SQLITE3_TEXT);
    $check_result = $check_stmt->execute();
    
    if (!$check_result->fetchArray(SQLITE3_ASSOC)) {
        // User hasn't voted yet, record the vote
        $vote_stmt = $db->prepare('INSERT INTO poll_votes (poll_id, user_id, username, ip_address, option_value) VALUES (:poll_id, :user_id, :username, :ip_address, :option_value)');
        $vote_stmt->bindValue(':poll_id', $poll_id, SQLITE3_INTEGER);
        $vote_stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
        $vote_stmt->bindValue(':username', $username, SQLITE3_TEXT);
        $vote_stmt->bindValue(':ip_address', $ip_address, SQLITE3_TEXT);
        $vote_stmt->bindValue(':option_value', $option, SQLITE3_TEXT);
        $vote_stmt->execute();
    }

    // Return JSON response for AJAX
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }
}

// Initialize some default polls if none exist
$polls_count = $db->querySingle('SELECT COUNT(*) FROM polls');
if ($polls_count == 0) {
    $default_polls = [
        [
            'title' => '🏗️ What server feature would you like to see next?',
            'options' => 'custom-enchants,player-shops,mini-games,dungeons',
            'ends_at' => date('Y-m-d H:i:s', strtotime('+7 days'))
        ],
        [
            'title' => '🎉 Which type of server event do you enjoy most?',
            'options' => 'building-contest,pvp-tournament,treasure-hunt,community-project',
            'ends_at' => date('Y-m-d H:i:s', strtotime('+5 days'))
        ],
        [
            'title' => '⚙️ What quality of life improvement is most needed?',
            'options' => 'teleport-system,inventory-sort,mob-spawner,chat-channels',
            'ends_at' => date('Y-m-d H:i:s', strtotime('+3 days'))
        ]
    ];

    foreach ($default_polls as $poll) {
        $stmt = $db->prepare('INSERT INTO polls (title, options, ends_at, created_by) VALUES (:title, :options, :ends_at, :created_by)');
        $stmt->bindValue(':title', $poll['title'], SQLITE3_TEXT);
        $stmt->bindValue(':options', $poll['options'], SQLITE3_TEXT);
        $stmt->bindValue(':ends_at', $poll['ends_at'], SQLITE3_TEXT);
        $stmt->bindValue(':created_by', 'System', SQLITE3_TEXT);
        $stmt->execute();
    }
}

// Get all polls with vote counts
function getPollsWithVotes($db) {
    $polls_stmt = $db->query('SELECT * FROM polls ORDER BY created_at DESC');
    $polls = [];
    
    while ($poll = $polls_stmt->fetchArray(SQLITE3_ASSOC)) {
        $poll['options_array'] = explode(',', $poll['options']);
        $poll['vote_counts'] = [];
        $poll['total_votes'] = 0;
        $poll['user_voted'] = false;
        
        // Get vote counts for each option
        foreach ($poll['options_array'] as $option) {
            $vote_count_stmt = $db->prepare('SELECT COUNT(*) as count FROM poll_votes WHERE poll_id = :poll_id AND option_value = :option');
            $vote_count_stmt->bindValue(':poll_id', $poll['id'], SQLITE3_INTEGER);
            $vote_count_stmt->bindValue(':option', $option, SQLITE3_TEXT);
            $vote_result = $vote_count_stmt->execute();
            $count = $vote_result->fetchArray(SQLITE3_ASSOC)['count'];
            $poll['vote_counts'][$option] = $count;
            $poll['total_votes'] += $count;
        }
        
        // Check if current user has voted
        $user_id = $_SESSION['user_id'] ?? null;
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        $user_vote_stmt = $db->prepare('SELECT option_value FROM poll_votes WHERE poll_id = :poll_id AND (user_id = :user_id OR ip_address = :ip_address)');
        $user_vote_stmt->bindValue(':poll_id', $poll['id'], SQLITE3_INTEGER);
        $user_vote_stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
        $user_vote_stmt->bindValue(':ip_address', $ip_address, SQLITE3_TEXT);
        $user_vote_result = $user_vote_stmt->execute();
        $user_vote = $user_vote_result->fetchArray(SQLITE3_ASSOC);
        
        if ($user_vote) {
            $poll['user_voted'] = $user_vote['option_value'];
        }
        
        // Check if poll has expired
        $poll['is_expired'] = $poll['ends_at'] && strtotime($poll['ends_at']) < time();
        
        $polls[] = $poll;
    }
    
    return $polls;
}

$polls = getPollsWithVotes($db);

// Option display names
$option_names = [
    'custom-enchants' => 'Custom Enchantments',
    'player-shops' => 'Player-owned Shops',
    'mini-games' => 'Mini-games Hub',
    'dungeons' => 'Custom Dungeons',
    'building-contest' => 'Building Contests',
    'pvp-tournament' => 'PvP Tournaments',
    'treasure-hunt' => 'Treasure Hunts',
    'community-project' => 'Community Building Projects',
    'teleport-system' => 'Better Teleport System',
    'inventory-sort' => 'Inventory Auto-Sort',
    'mob-spawner' => 'Improved Mob Spawners',
    'chat-channels' => 'Chat Channels System'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Xenon | Community Polls</title>
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="styles.css" />
  <style>
    .polls-container {
      max-width: 900px;
      margin: 4rem auto;
      padding: 0 1rem;
    }
    .polls-container h2 {
      text-align: center;
      margin-bottom: 2rem;
      color: #00ffe1;
      font-family: 'Orbitron', monospace;
    }
    .poll {
      background: rgba(0, 0, 0, 0.2);
      border: 1px solid #00ffe1;
      border-radius: 10px;
      padding: 2rem;
      margin-bottom: 2rem;
      backdrop-filter: blur(10px);
    }
    .poll h3 {
      color: #ffffff;
      margin-bottom: 1.5rem;
      font-family: 'Orbitron', monospace;
    }
    .poll-option {
      display: flex;
      align-items: center;
      margin-bottom: 1rem;
      padding: 0.8rem;
      background: rgba(0, 255, 225, 0.1);
      border-radius: 5px;
      cursor: pointer;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }
    .poll-option:hover {
      background: rgba(0, 255, 225, 0.2);
      border: 1px solid #00ffe1;
    }
    .poll-option input[type="radio"] {
      margin-right: 1rem;
      accent-color: #00ffe1;
    }
    .poll-option label {
      color: #ccc;
      cursor: pointer;
      flex: 1;
    }
    .vote-count {
      color: #00ffe1;
      font-weight: bold;
      margin-left: 1rem;
    }
    .vote-button {
      background: linear-gradient(45deg, #00ffe1, #0080ff);
      border: none;
      color: #000;
      padding: 0.8rem 2rem;
      border-radius: 25px;
      cursor: pointer;
      font-weight: bold;
      margin-top: 1rem;
      transition: all 0.3s ease;
    }
    .vote-button:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(0, 255, 225, 0.3);
    }
    .vote-button:disabled {
      opacity: 0.5;
      cursor: not-allowed;
      transform: none;
    }
    .poll-results {
      margin-top: 1rem;
    }
    .result-bar {
      background: rgba(0, 255, 225, 0.1);
      height: 25px;
      border-radius: 12px;
      margin: 0.5rem 0;
      overflow: hidden;
      position: relative;
    }
    .result-fill {
      height: 100%;
      background: linear-gradient(90deg, #00ffe1, #0080ff);
      border-radius: 12px;
      transition: width 0.5s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #000;
      font-weight: bold;
      font-size: 0.9rem;
    }
    .poll-status {
      text-align: center;
      color: #00ffe1;
      margin-top: 1rem;
      font-style: italic;
    }
    .poll-meta {
      color: #888;
      font-size: 0.9rem;
      margin-top: 1rem;
      text-align: center;
    }
    .total-votes {
      color: #00ffe1;
      font-weight: bold;
    }
    body {
      background: linear-gradient(135deg, #0f0f23 0%, #1a1a2e 100%);
      color: #ffffff;
      font-family: Arial, sans-serif;
      margin: 0;
      min-height: 100vh;
    }
    footer {
      text-align: center;
      padding: 2rem;
      color: #888;
      border-top: 1px solid #333;
      margin-top: 4rem;
    }
  </style>
</head>
<body>
  <div id="headers"></div>
  <main class="polls-container">
    <h2>Community Polls</h2>
    
    <?php if (isset($_SESSION['user_id'])): ?>
      <p style="text-align: center; color: #ccc; margin-bottom: 2rem;">
        Voting as: <strong style="color: #00ffe1;"><?= htmlspecialchars($_SESSION['username']) ?></strong>
        <?php if (is_admin($_SESSION['username'])): ?>
          <span style="background: #ff6b6b; color: white; padding: 0.2rem 0.4rem; border-radius: 3px; font-size: 0.7rem; font-weight: bold; margin-left: 0.3rem;">ADMIN</span>
        <?php endif; ?>
      </p>
    <?php else: ?>
      <p style="text-align: center; color: #888; margin-bottom: 2rem;">
        You are voting anonymously. <a href="auth.php" style="color: #00ffe1;">Login</a> to vote with your username.
      </p>
    <?php endif; ?>

    <?php foreach ($polls as $poll): ?>
      <div class="poll" data-poll-id="<?= $poll['id'] ?>">
        <h3><?= htmlspecialchars($poll['title']) ?></h3>
        
        <?php if ($poll['is_expired']): ?>
          <!-- Show results for expired polls -->
          <div class="poll-results">
            <?php foreach ($poll['options_array'] as $option): ?>
              <?php 
                $vote_count = $poll['vote_counts'][$option];
                $percentage = $poll['total_votes'] > 0 ? round(($vote_count / $poll['total_votes']) * 100) : 0;
                $option_name = $option_names[$option] ?? ucfirst(str_replace('-', ' ', $option));
              ?>
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                <span style="color: #ccc;"><?= htmlspecialchars($option_name) ?></span>
                <span style="color: #00ffe1;"><?= $percentage ?>%</span>
              </div>
              <div class="result-bar">
                <div class="result-fill" style="width: <?= $percentage ?>%;"><?= $vote_count ?> votes</div>
              </div>
            <?php endforeach; ?>
          </div>
          <div class="poll-status">✅ Poll Completed</div>
          <div class="poll-meta">
            Total votes: <span class="total-votes"><?= $poll['total_votes'] ?></span> | 
            Poll ended <?= $poll['ends_at'] ? date('M j, Y', strtotime($poll['ends_at'])) : 'recently' ?>
          </div>
        
        <?php elseif ($poll['user_voted']): ?>
          <!-- Show results if user has already voted -->
          <div class="poll-results">
            <?php foreach ($poll['options_array'] as $option): ?>
              <?php 
                $vote_count = $poll['vote_counts'][$option];
                $percentage = $poll['total_votes'] > 0 ? round(($vote_count / $poll['total_votes']) * 100) : 0;
                $option_name = $option_names[$option] ?? ucfirst(str_replace('-', ' ', $option));
                $is_user_choice = ($poll['user_voted'] === $option);
              ?>
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                <span style="color: #ccc;">
                  <?= htmlspecialchars($option_name) ?>
                  <?php if ($is_user_choice): ?>
                    <span style="color: #00ffe1; font-weight: bold;"> ✓ Your vote</span>
                  <?php endif; ?>
                </span>
                <span style="color: #00ffe1;"><?= $percentage ?>%</span>
              </div>
              <div class="result-bar">
                <div class="result-fill" style="width: <?= $percentage ?>%; <?= $is_user_choice ? 'border: 2px solid #00ffe1;' : '' ?>"><?= $vote_count ?> votes</div>
              </div>
            <?php endforeach; ?>
          </div>
          <div class="poll-status">✅ You have voted</div>
          <div class="poll-meta">
            Total votes: <span class="total-votes"><?= $poll['total_votes'] ?></span> | 
            <?php if ($poll['ends_at']): ?>
              Poll ends <?= date('M j, Y', strtotime($poll['ends_at'])) ?>
            <?php else: ?>
              Ongoing poll
            <?php endif; ?>
          </div>
        
        <?php else: ?>
          <!-- Show voting form -->
          <form class="poll-form" data-poll-id="<?= $poll['id'] ?>">
            <?php foreach ($poll['options_array'] as $index => $option): ?>
              <?php 
                $option_name = $option_names[$option] ?? ucfirst(str_replace('-', ' ', $option));
                $vote_count = $poll['vote_counts'][$option];
              ?>
              <div class="poll-option">
                <input type="radio" id="poll<?= $poll['id'] ?>_option<?= $index ?>" name="poll-option" value="<?= htmlspecialchars($option) ?>">
                <label for="poll<?= $poll['id'] ?>_option<?= $index ?>"><?= htmlspecialchars($option_name) ?></label>
                <span class="vote-count"><?= $vote_count ?> votes</span>
              </div>
            <?php endforeach; ?>
            <button type="submit" class="vote-button">Cast Your Vote</button>
          </form>
          <div class="poll-meta">
            Total votes: <span class="total-votes"><?= $poll['total_votes'] ?></span> | 
            <?php if ($poll['ends_at']): ?>
              Poll ends <?= date('M j, Y', strtotime($poll['ends_at'])) ?>
            <?php else: ?>
              Ongoing poll
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </main>

  <footer>
    © 2025 Xenon Minecraft Server. All Rights Reserved.
  </footer>

  <script>
    async function loadHeaders() {
      try {
        const res = await fetch('headers.php');
        const text = await res.text();
        document.getElementById('headers').innerHTML = text;
      } catch (error) {
        console.error('Failed to load headers:', error);
      }
    }

    function handleVote(form, pollId) {
      const formData = new FormData(form);
      const selectedOption = formData.get('poll-option');
      
      if (!selectedOption) {
        alert('Please select an option before voting!');
        return;
      }

      const button = form.querySelector('.vote-button');
      button.disabled = true;
      button.textContent = 'Submitting...';

      // Create form data for POST request
      const postData = new FormData();
      postData.append('poll_id', pollId);
      postData.append('option', selectedOption);

      fetch(window.location.href, {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: postData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Reload the page to show updated results
          window.location.reload();
        } else {
          alert('Error submitting vote. Please try again.');
          button.disabled = false;
          button.textContent = 'Cast Your Vote';
        }
      })
      .catch(error => {
        console.error('Vote submission error:', error);
        alert('Error submitting vote. Please try again.');
        button.disabled = false;
        button.textContent = 'Cast Your Vote';
      });
    }

    function setupPolls() {
      document.querySelectorAll('.poll-form').forEach(form => {
        form.addEventListener('submit', (e) => {
          e.preventDefault();
          const pollId = form.getAttribute('data-poll-id');
          handleVote(form, pollId);
        });
      });
    }

    // Initialize the page
    loadHeaders();
    setupPolls();
  </script>
</body>
</html>
