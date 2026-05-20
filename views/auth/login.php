<?php
// views/auth/login.php
require_once '../../config/db.php';
require_once '../../includes/auth.php';
start_session();

// Already logged in? Redirect away
if (is_logged_in()) {
    header('Location: /autocare/index.php');
    exit;
}

// Auto-login via remember me cookie
if (!empty($_COOKIE['remember_token'])) {
    $stmt = $pdo->prepare(
        "SELECT * FROM users
         WHERE remember_token = ? AND is_active = 1 LIMIT 1"
    );
    $stmt->execute([$_COOKIE['remember_token']]);
    $user = $stmt->fetch();
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name']    = $user['name'];
        $_SESSION['role']    = $user['role'];
        header('Location: /autocare/index.php');
        exit;
    }
}

$error = '';

// Handle form POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $remember = isset($_POST['remember']);

    if (empty($email) || empty($password)) {
        $error = 'Please enter your email and password.';
    } else {
        $stmt = $pdo->prepare(
            "SELECT * FROM users
             WHERE email = ? AND is_active = 1 LIMIT 1"
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Set session
            session_regenerate_id(true); // prevent session fixation
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name']    = $user['name'];
            $_SESSION['role']    = $user['role'];

            // Remember me cookie (30 days)
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                setcookie('remember_token', $token, time() + (30 * 24 * 3600), '/', '', false, true);
                $stmt2 = $pdo->prepare(
                    "UPDATE users SET remember_token = ? WHERE id = ?"
                );
                $stmt2->execute([$token, $user['id']]);
            }

            // Redirect by role
            header('Location: /autocare/index.php');
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AutoCare — Login</title>
  <link rel="stylesheet" href="/autocare/public/css/style.css">
</head>
<body>
  <div class="login-wrap">
    <div class="auth-card">
      <div class="logo-section">
        <svg class="logo-icon" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg">
          <defs>
            <linearGradient id="carGrad" x1="0%" y1="0%" x2="100%" y2="100%">
              <stop offset="0%" style="stop-color:#0459bb;stop-opacity:1" />
              <stop offset="100%" style="stop-color:#083f7d;stop-opacity:1" />
            </linearGradient>
          </defs>
          <!-- Car body -->
          <rect x="8" y="28" width="48" height="18" rx="4" fill="url(#carGrad)" />
          <!-- Car roof -->
          <path d="M 16 28 L 24 12 L 40 12 L 48 28" fill="url(#carGrad)" />
          <!-- Windows -->
          <rect x="18" y="16" width="8" height="8" rx="1" fill="rgba(255,255,255,0.3)" />
          <rect x="38" y="16" width="8" height="8" rx="1" fill="rgba(255,255,255,0.3)" />
          <!-- Wheels -->
          <circle cx="16" cy="46" r="5" fill="#162f4d" />
          <circle cx="48" cy="46" r="5" fill="#162f4d" />
          <circle cx="16" cy="46" r="3" fill="#f2a800" />
          <circle cx="48" cy="46" r="3" fill="#f2a800" />
          <!-- Headlights -->
          <circle cx="10" cy="32" r="2" fill="#f2a800" />
          <circle cx="14" cy="32" r="2" fill="#f2a800" />
        </svg>
        <h1>AutoCare</h1>
      </div>
      <p class="tagline">Professional Vehicle Service</p>

      <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        
        <div class="form-group">
          <label for="email">Email address</label>
          <input type="email" id="email" name="email"
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                 placeholder="admin@autocare.lk" required autofocus>
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password"
                 placeholder="••••••••" required>
        </div>

        <label class="check-label">
          <input type="checkbox" name="remember">
          <span>Remember me for 30 days</span>
        </label>

        <button type="submit" class="btn btn-primary" style="width:100%;margin-top:1.5rem;">
          Sign in to Dashboard
        </button>
      </form>

      <div class="auth-footer">
        <p>New customer? <a href="signup.php">Create an account</a></p>
      </div>
    </div>
  </div>
</body>
</html>