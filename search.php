<?php
session_start();
require_once 'admin_config.php';
require_once 'db_init.php';

// Initialize database with automatic table creation
$db = getDatabaseConnection();

$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$search_type = isset($_GET['type']) ? $_GET['type'] : 'all';
$results = [];
$total_results = 0;

if (!empty($search_query)) {
    try {
        if ($search_type === 'threads' || $search_type === 'all') {
            // Search threads by title and tags
            $thread_stmt = $db->prepare('
                SELECT DISTINCT t.*, "thread" as result_type
                FROM threads t
                LEFT JOIN thread_tags tt ON t.id = tt.thread_id
                LEFT JOIN tags tag ON tt.tag_id = tag.id
                WHERE t.is_deleted = 0 AND (
                    t.title LIKE :query 
                    OR t.username LIKE :query
                    OR tag.name LIKE :query
                )
                ORDER BY t.created_at DESC
            ');
            $thread_stmt->bindValue(':query', '%' . $search_query . '%', SQLITE3_TEXT);
            $thread_result = $thread_stmt->execute();
            
            while ($row = $thread_result->fetchArray(SQLITE3_ASSOC)) {
                $results[] = $row;
                $total_results++;
            }
        }
        
        if ($search_type === 'posts' || $search_type === 'all') {
            // Search posts by content and tags
            $post_stmt = $db->prepare('
                SELECT DISTINCT p.*, t.title as thread_title, "post" as result_type
                FROM posts p
                JOIN threads t ON p.thread_id = t.id
                LEFT JOIN post_tags pt ON p.id = pt.post_id
                LEFT JOIN tags tag ON pt.tag_id = tag.id
                WHERE p.is_deleted = 0 AND t.is_deleted = 0 AND (
                    p.content LIKE :query 
                    OR p.username LIKE :query
                    OR tag.name LIKE :query
                )
                ORDER BY p.created_at DESC
            ');
            $post_stmt->bindValue(':query', '%' . $search_query . '%', SQLITE3_TEXT);
            $post_result = $post_stmt->execute();
            
            while ($row = $post_result->fetchArray(SQLITE3_ASSOC)) {
                $results[] = $row;
                $total_results++;
            }
        }
        
        if ($search_type === 'tags') {
            // Search by tags only
            $tag_stmt = $db->prepare('
                SELECT DISTINCT t.*, "thread" as result_type
                FROM threads t
                JOIN thread_tags tt ON t.id = tt.thread_id
                JOIN tags tag ON tt.tag_id = tag.id
                WHERE t.is_deleted = 0 AND tag.name LIKE :query
                UNION
                SELECT DISTINCT t.*, "thread" as result_type
                FROM threads t
                JOIN posts p ON t.id = p.thread_id
                JOIN post_tags pt ON p.id = pt.post_id
                JOIN tags tag ON pt.tag_id = tag.id
                WHERE t.is_deleted = 0 AND p.is_deleted = 0 AND tag.name LIKE :query
                ORDER BY created_at DESC
            ');
            $tag_stmt->bindValue(':query', '%' . $search_query . '%', SQLITE3_TEXT);
            $tag_result = $tag_stmt->execute();
            
            while ($row = $tag_result->fetchArray(SQLITE3_ASSOC)) {
                $results[] = $row;
                $total_results++;
            }
        }
        
    } catch (Exception $e) {
        error_log("Search error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Search - Xenon Forum</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .search-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .search-form {
            background: rgba(0, 255, 225, 0.1);
            border: 1px solid #00ffe1;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .search-form h2 {
            color: #00ffe1;
            margin-top: 0;
        }
        .search-input {
            width: 100%;
            padding: 1rem;
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid #00ffe1;
            border-radius: 5px;
            color: #fff;
            font-family: 'Arial', sans-serif;
            font-size: 16px;
            box-sizing: border-box;
            margin-bottom: 1rem;
        }
        .search-filters {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }
        .search-filter {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid #00ffe1;
            color: #fff;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .search-filter.active {
            background: #00ffe1;
            color: #000;
        }
        .search-filter:hover {
            background: rgba(0, 255, 225, 0.2);
        }
        .search-filter.active:hover {
            background: #00ccb8;
        }
        .search-btn {
            background: #00ffe1;
            color: #000;
            border: none;
            padding: 1rem 2rem;
            border-radius: 5px;
            font-family: 'Orbitron', sans-serif;
            font-weight: bold;
            font-size: 14px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .search-btn:hover {
            background: #00ccb8;
        }
        .search-results {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid #444;
            border-radius: 10px;
            padding: 1.5rem;
        }
        .search-result {
            border-bottom: 1px solid #333;
            padding: 1rem 0;
        }
        .search-result:last-child {
            border-bottom: none;
        }
        .result-title {
            color: #00ffe1;
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
        }
        .result-title a {
            color: #00ffe1;
            text-decoration: none;
        }
        .result-title a:hover {
            text-decoration: underline;
        }
        .result-meta {
            color: #888;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        .result-content {
            color: #ccc;
            line-height: 1.5;
        }
        .result-type {
            background: rgba(0, 255, 225, 0.2);
            color: #00ffe1;
            padding: 0.2rem 0.5rem;
            border-radius: 3px;
            font-size: 0.7rem;
            margin-right: 0.5rem;
        }
        .no-results {
            text-align: center;
            color: #888;
            padding: 2rem;
        }
    </style>
</head>
<body>
    <!-- Headers will be loaded here -->
    <div id="headers"></div>
    
    <header>
        <h1>Xenon Forum</h1>
        <p>Search</p>
    </header>

    <div class="search-container">
        <div class="search-form">
            <h2>Search Forum</h2>
            <form method="get">
                <input type="text" name="q" class="search-input" placeholder="Search threads, posts, tags..." value="<?= htmlspecialchars($search_query) ?>" required>
                
                <div class="search-filters">
                    <label class="search-filter <?= $search_type === 'all' ? 'active' : '' ?>">
                        <input type="radio" name="type" value="all" <?= $search_type === 'all' ? 'checked' : '' ?> style="display: none;">
                        All
                    </label>
                    <label class="search-filter <?= $search_type === 'threads' ? 'active' : '' ?>">
                        <input type="radio" name="type" value="threads" <?= $search_type === 'threads' ? 'checked' : '' ?> style="display: none;">
                        Threads
                    </label>
                    <label class="search-filter <?= $search_type === 'posts' ? 'active' : '' ?>">
                        <input type="radio" name="type" value="posts" <?= $search_type === 'posts' ? 'checked' : '' ?> style="display: none;">
                        Posts
                    </label>
                    <label class="search-filter <?= $search_type === 'tags' ? 'active' : '' ?>">
                        <input type="radio" name="type" value="tags" <?= $search_type === 'tags' ? 'checked' : '' ?> style="display: none;">
                        Tags
                    </label>
                </div>
                
                <button type="submit" class="search-btn">Search</button>
            </form>
        </div>

        <?php if (!empty($search_query)): ?>
            <div class="search-results">
                <h3 style="color: #00ffe1; margin-top: 0;">
                    <?= $total_results ?> result<?= $total_results !== 1 ? 's' : '' ?> for "<?= htmlspecialchars($search_query) ?>"
                </h3>
                
                <?php if (empty($results)): ?>
                    <div class="no-results">
                        <p>No results found. Try different keywords or search terms.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($results as $result): ?>
                        <div class="search-result">
                            <?php if ($result['result_type'] === 'thread'): ?>
                                <div class="result-title">
                                    <span class="result-type">THREAD</span>
                                    <a href="thread.php?id=<?= $result['id'] ?>">
                                        <?= htmlspecialchars($result['title']) ?>
                                    </a>
                                </div>
                                <div class="result-meta">
                                    Started by <strong><?= htmlspecialchars($result['username']) ?></strong>
                                    <?php if (is_admin($result['username'])): ?>
                                        <span style="background: #ff6b6b; color: white; padding: 0.1rem 0.3rem; border-radius: 2px; font-size: 0.6rem; margin-left: 0.3rem;">ADMIN</span>
                                    <?php endif; ?>
                                    on <?= date('M j, Y \a\t g:i A', strtotime($result['created_at'])) ?>
                                </div>
                                <?php
                                $thread_tags = getThreadTags($db, $result['id']);
                                if (!empty($thread_tags)):
                                ?>
                                    <div style="margin-top: 0.5rem;">
                                        <?php foreach ($thread_tags as $tag): ?>
                                            <span style="background: rgba(0, 255, 225, 0.2); color: #00ffe1; padding: 0.2rem 0.5rem; border-radius: 12px; font-size: 0.7rem; margin-right: 0.3rem; border: 1px solid #00ffe1;"><?= htmlspecialchars($tag) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="result-title">
                                    <span class="result-type">POST</span>
                                    <a href="thread.php?id=<?= $result['thread_id'] ?>#post-<?= $result['id'] ?>">
                                        Re: <?= htmlspecialchars($result['thread_title']) ?>
                                    </a>
                                </div>
                                <div class="result-meta">
                                    Posted by <strong><?= htmlspecialchars($result['username']) ?></strong>
                                    <?php if (is_admin($result['username'])): ?>
                                        <span style="background: #ff6b6b; color: white; padding: 0.1rem 0.3rem; border-radius: 2px; font-size: 0.6rem; margin-left: 0.3rem;">ADMIN</span>
                                    <?php endif; ?>
                                    on <?= date('M j, Y \a\t g:i A', strtotime($result['created_at'])) ?>
                                </div>
                                <div class="result-content">
                                    <?= htmlspecialchars(substr($result['content'], 0, 200)) ?><?= strlen($result['content']) > 200 ? '...' : '' ?>
                                </div>
                                <?php
                                $post_tags = getPostTags($db, $result['id']);
                                if (!empty($post_tags)):
                                ?>
                                    <div style="margin-top: 0.5rem;">
                                        <?php foreach ($post_tags as $tag): ?>
                                            <span style="background: rgba(0, 255, 225, 0.2); color: #00ffe1; padding: 0.2rem 0.5rem; border-radius: 12px; font-size: 0.7rem; margin-right: 0.3rem; border: 1px solid #00ffe1;"><?= htmlspecialchars($tag) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <footer>
        &copy; <?= date("Y") ?> Xenon Forum
    </footer>

    <script>
        async function loadHeaders() {
            const res = await fetch('headers.php');
            const text = await res.text();
            document.getElementById('headers').innerHTML = text;
        }
        loadHeaders();
        
        // Handle filter clicks
        document.querySelectorAll('.search-filter').forEach(filter => {
            filter.addEventListener('click', function() {
                document.querySelectorAll('.search-filter').forEach(f => f.classList.remove('active'));
                this.classList.add('active');
                this.querySelector('input[type="radio"]').checked = true;
            });
        });
    </script>
</body>
</html>