<?php
session_start();

// Use absolute path for database to work with Apache
$db_path = __DIR__ . '/db.sqlite';

try {
    $db = new SQLite3($db_path);
} catch (Exception $e) {
    die("Database connection failed. Please ensure the database file exists and is writable.");
}

// Handle registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    $errors = [];
    
    if (empty($username) || empty($email) || empty($password)) {
        $errors[] = "All fields are required.";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }
    
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long.";
    }
    
    // Check if username or email already exists
    $stmt = $db->prepare('SELECT id FROM users WHERE username = :username OR email = :email');
    $stmt->bindValue(':username', $username, SQLITE3_TEXT);
    $stmt->bindValue(':email', $email, SQLITE3_TEXT);
    $result = $stmt->execute();
    
    if ($result->fetchArray()) {
        $errors[] = "Username or email already exists.";
    }
    
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare('INSERT INTO users (username, email, password) VALUES (:username, :email, :password)');
        $stmt->bindValue(':username', $username, SQLITE3_TEXT);
        $stmt->bindValue(':email', $email, SQLITE3_TEXT);
        $stmt->bindValue(':password', $hashed_password, SQLITE3_TEXT);
        
        if ($stmt->execute()) {
            $_SESSION['user_id'] = $db->lastInsertRowID();
            $_SESSION['username'] = $username;
            header('Location: forum.php');
            exit;
        } else {
            $errors[] = "Registration failed. Please try again.";
        }
    }
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    $stmt = $db->prepare('SELECT id, username, password FROM users WHERE username = :username');
    $stmt->bindValue(':username', $username, SQLITE3_TEXT);
    $result = $stmt->execute();
    $user = $result->fetchArray(SQLITE3_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        header('Location: forum.php');
        exit;
    } else {
        $login_error = "Invalid username or password.";
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: forum.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login / Register - Xenon Forum</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .auth-container {
            max-width: 500px;
            margin: 2rem auto;
            padding: 2rem;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid #00ffe1;
            border-radius: 10px;
        }
        .auth-form {
            margin-bottom: 2rem;
        }
        .auth-form h2 {
            color: #00ffe1;
            text-align: center;
            margin-bottom: 1rem;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group label {
            display: block;
            color: #ccc;
            margin-bottom: 0.5rem;
        }
        .form-group input {
            width: 100%;
            padding: 0.8rem;
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid #00ffe1;
            border-radius: 5px;
            color: #fff;
            font-family: 'Orbitron', sans-serif;
        }
        .form-group input:focus {
            outline: none;
            box-shadow: 0 0 10px rgba(0, 255, 225, 0.3);
        }
        .btn {
            width: 100%;
            padding: 1rem;
            background: #00ffe1;
            color: #000;
            border: none;
            border-radius: 5px;
            font-family: 'Orbitron', sans-serif;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #00ccb8;
        }
        .error {
            color: #ff4444;
            margin-bottom: 1rem;
            text-align: center;
        }
        .toggle-form {
            text-align: center;
            color: #ccc;
            margin-top: 1rem;
        }
        .toggle-form a {
            color: #00ffe1;
            text-decoration: none;
        }
        .toggle-form a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <!-- Headers will be loaded here -->
    <div id="headers"></div>
    
    <header>
        <h1>Xenon Forum</h1>
        <p>Login or Register</p>
    </header>

    <div class="auth-container">
        <!-- Login Form -->
        <div class="auth-form" id="login-form">
            <h2>Login</h2>
            <?php if (isset($login_error)): ?>
                <div class="error"><?= htmlspecialchars($login_error) ?></div>
            <?php endif; ?>
            <form method="post">
                <div class="form-group">
                    <label for="login-username">Username:</label>
                    <input type="text" id="login-username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="login-password">Password:</label>
                    <input type="password" id="login-password" name="password" required>
                </div>
                <button type="submit" name="login" class="btn">Login</button>
            </form>
            <div class="toggle-form">
                Don't have an account? <a href="#" onclick="toggleForms()">Register here</a>
            </div>
        </div>

        <!-- Registration Form -->
        <div class="auth-form" id="register-form" style="display: none;">
            <h2>Register</h2>
            <?php if (!empty($errors)): ?>
                <div class="error">
                    <?php foreach ($errors as $error): ?>
                        <div><?= htmlspecialchars($error) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <form method="post">
                <div class="form-group">
                    <label for="register-username">Username:</label>
                    <input type="text" id="register-username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="register-email">Email:</label>
                    <input type="email" id="register-email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="register-password">Password:</label>
                    <input type="password" id="register-password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="confirm-password">Confirm Password:</label>
                    <input type="password" id="confirm-password" name="confirm_password" required>
                </div>
                <button type="submit" name="register" class="btn">Register</button>
            </form>
            <div class="toggle-form">
                Already have an account? <a href="#" onclick="toggleForms()">Login here</a>
            </div>
        </div>
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

        function toggleForms() {
            const loginForm = document.getElementById('login-form');
            const registerForm = document.getElementById('register-form');
            
            if (loginForm.style.display === 'none') {
                loginForm.style.display = 'block';
                registerForm.style.display = 'none';
            } else {
                loginForm.style.display = 'none';
                registerForm.style.display = 'block';
            }
        }

        // Show register form if there were registration errors
        <?php if (!empty($errors)): ?>
        toggleForms();
        <?php endif; ?>
    </script>
</body>
</html>