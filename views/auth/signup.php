<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';
start_session();

// Already logged in? Redirect away
if (is_logged_in()) {
    header('Location: /autocare/index.php');
    exit;
}

$errors = [];
$old    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $old = $_POST;

    // Validation
    if (empty($_POST['name']))
        $errors['name']     = 'Full name is required.';
    if (empty($_POST['nic']))
        $errors['nic']      = 'NIC is required.';
    if (empty($_POST['phone']))
        $errors['phone']    = 'Phone number is required.';
    if (empty($_POST['email']))
        $errors['email']    = 'Email is required.';
    if (empty($_POST['password']))
        $errors['password'] = 'Password is required.';
    if (strlen($_POST['password'] ?? '') < 8)
        $errors['password'] = 'Password must be at least 8 characters.';
    if ($_POST['password'] !== $_POST['password_confirm'])
        $errors['password_confirm'] = 'Passwords do not match.';

    // Email unique check in users table
    if (empty($errors['email'])) {
        $chk = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $chk->execute([trim($_POST['email'])]);
        if ($chk->fetch())
            $errors['email'] = 'An account with this email already exists.';
    }

    // NIC unique check in customers table
    if (empty($errors['nic'])) {
        $chk = $pdo->prepare("SELECT id FROM customers WHERE nic = ?");
        $chk->execute([trim($_POST['nic'])]);
        if ($chk->fetch())
            $errors['nic'] = 'A customer with this NIC already exists.';
    }

    if (empty($errors)) {
        $pdo->beginTransaction();
        try {
            // 1. Create user account (hashed password)
            $hashed = password_hash($_POST['password'], PASSWORD_BCRYPT);

            $u = $pdo->prepare("
                INSERT INTO users (name, email, password, role)
                VALUES (?, ?, ?, 'customer')
            ");
            $u->execute([
                trim($_POST['name']),
                trim($_POST['email']),
                $hashed,
            ]);

            // 2. Create linked customer record in same transaction
            $c = $pdo->prepare("
                INSERT INTO customers (name, nic, phone, email, address)
                VALUES (?, ?, ?, ?, ?)
            ");
            $c->execute([
                trim($_POST['name']),
                trim($_POST['nic']),
                trim($_POST['phone']),
                trim($_POST['email']),
                trim($_POST['address']) ?: null,
            ]);

            $pdo->commit();

            // Auto-login after signup
            $fetch = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $fetch->execute([trim($_POST['email'])]);
            $user = $fetch->fetch();

            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name']    = $user['name'];
            $_SESSION['role']    = $user['role'];
            $_SESSION['email']   = $user['email'];

            $_SESSION['flash'] = [
                'type'    => 'success',
                'message' => 'Account created! Welcome to AutoCare.'
            ];
            header('Location: /autocare/index.php');
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $errors['general'] = 'Something went wrong. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Account — AutoCare</title>
  <link rel="stylesheet" href="/autocare/public/css/style.css">
</head>
<body>
  <div class="login-wrap">
    <div class="auth-card">
      <div class="logo-section">
        <svg class="logo-icon" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg">
          <defs>
            <linearGradient id="carGrad2" x1="0%" y1="0%" x2="100%" y2="100%">
              <stop offset="0%" style="stop-color:#0459bb;stop-opacity:1" />
              <stop offset="100%" style="stop-color:#083f7d;stop-opacity:1" />
            </linearGradient>
          </defs>
          <rect x="8" y="28" width="48" height="18" rx="4" fill="url(#carGrad2)" />
          <path d="M 16 28 L 24 12 L 40 12 L 48 28" fill="url(#carGrad2)" />
          <rect x="18" y="16" width="8" height="8" rx="1" fill="rgba(255,255,255,0.3)" />
          <rect x="38" y="16" width="8" height="8" rx="1" fill="rgba(255,255,255,0.3)" />
          <circle cx="16" cy="46" r="5" fill="#162f4d" />
          <circle cx="48" cy="46" r="5" fill="#162f4d" />
          <circle cx="16" cy="46" r="3" fill="#f2a800" />
          <circle cx="48" cy="46" r="3" fill="#f2a800" />
          <circle cx="10" cy="32" r="2" fill="#f2a800" />
          <circle cx="14" cy="32" r="2" fill="#f2a800" />
        </svg>
        <h1>Create Account</h1>
      </div>
      <p class="tagline">Join AutoCare Community</p>

      <?php if (isset($errors['general'])): ?>
        <div class="alert alert-error"><?= htmlspecialchars($errors['general']) ?></div>
      <?php endif; ?>

      <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

        <div class="form-group <?= isset($errors['name']) ? 'has-error':'' ?>">
          <label for="name">Full Name *</label>
          <input type="text" id="name" name="name"
                 value="<?= htmlspecialchars($old['name'] ?? '') ?>"
                 placeholder="Kamal Perera" autofocus>
          <?php if (isset($errors['name'])): ?>
            <span class="error"><?= $errors['name'] ?></span>
          <?php endif; ?>
        </div>

        <div class="form-group <?= isset($errors['nic']) ? 'has-error':'' ?>">
          <label for="nic">NIC Number *</label>
          <input type="text" id="nic" name="nic"
                 value="<?= htmlspecialchars($old['nic'] ?? '') ?>"
                 placeholder="123456789V">
          <?php if (isset($errors['nic'])): ?>
            <span class="error"><?= $errors['nic'] ?></span>
          <?php endif; ?>
        </div>

        <div class="form-group <?= isset($errors['phone']) ? 'has-error':'' ?>">
          <label for="phone">Phone Number *</label>
          <input type="tel" id="phone" name="phone"
                 value="<?= htmlspecialchars($old['phone'] ?? '') ?>"
                 placeholder="0701234567">
          <?php if (isset($errors['phone'])): ?>
            <span class="error"><?= $errors['phone'] ?></span>
          <?php endif; ?>
        </div>

        <div class="form-group <?= isset($errors['email']) ? 'has-error':'' ?>">
          <label for="email">Email Address *</label>
          <input type="email" id="email" name="email"
                 value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                 placeholder="you@example.com">
          <?php if (isset($errors['email'])): ?>
            <span class="error"><?= $errors['email'] ?></span>
          <?php endif; ?>
        </div>

        <div class="form-group">
          <label for="address">Address <span class="optional">(optional)</span></label>
          <input type="text" id="address" name="address"
                 value="<?= htmlspecialchars($old['address'] ?? '') ?>"
                 placeholder="123 Main Street">
        </div>

        <div class="form-group <?= isset($errors['password']) ? 'has-error':'' ?>">
          <label for="password">Password * <span class="optional">(min 8 characters)</span></label>
          <input type="password" id="password" name="password" placeholder="••••••••">
          <?php if (isset($errors['password'])): ?>
            <span class="error"><?= $errors['password'] ?></span>
          <?php endif; ?>
        </div>

        <div class="form-group <?= isset($errors['password_confirm']) ? 'has-error':'' ?>">
          <label for="password_confirm">Confirm Password *</label>
          <input type="password" id="password_confirm" name="password_confirm" placeholder="••••••••">
          <?php if (isset($errors['password_confirm'])): ?>
            <span class="error"><?= $errors['password_confirm'] ?></span>
          <?php endif; ?>
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%;margin-top:1rem;">
          Create Account
        </button>
      </form>

      <div class="auth-footer">
        <p>Already have an account? <a href="login.php">Sign in here</a></p>
      </div>
    </div>
  </div>
</body>
</html>