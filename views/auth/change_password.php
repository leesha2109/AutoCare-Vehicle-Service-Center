<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';
require_login();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $current  = $_POST['current_password']  ?? '';
    $new      = $_POST['new_password']      ?? '';
    $confirm  = $_POST['confirm_password']  ?? '';

    if (empty($current))   $errors['current']  = 'Enter your current password.';
    if (strlen($new) < 8)  $errors['new']      = 'Minimum 8 characters.';
    if ($new !== $confirm) $errors['confirm']  = 'Passwords do not match.';

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        if (!password_verify($current, $user['password'])) {
            $errors['current'] = 'Current password is incorrect.';
        } else {
            $upd = $pdo->prepare("
                UPDATE users SET password = ? WHERE id = ?
            ");
            $upd->execute([
                password_hash($new, PASSWORD_BCRYPT),
                $_SESSION['user_id']
            ]);
            $_SESSION['flash'] = ['type'=>'success','message'=>'Password updated.'];
            header('Location: /autocare/index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Change Password — AutoCare</title>
  <link rel="stylesheet" href="/autocare/public/css/style.css">
</head>
<body>
  <div class="login-wrap">
    <div class="auth-card">
      <div class="logo-section">
        <svg class="logo-icon" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg">
          <defs>
            <linearGradient id="carGrad3" x1="0%" y1="0%" x2="100%" y2="100%">
              <stop offset="0%" style="stop-color:#0459bb;stop-opacity:1" />
              <stop offset="100%" style="stop-color:#083f7d;stop-opacity:1" />
            </linearGradient>
          </defs>
          <rect x="8" y="28" width="48" height="18" rx="4" fill="url(#carGrad3)" />
          <path d="M 16 28 L 24 12 L 40 12 L 48 28" fill="url(#carGrad3)" />
          <rect x="18" y="16" width="8" height="8" rx="1" fill="rgba(255,255,255,0.3)" />
          <rect x="38" y="16" width="8" height="8" rx="1" fill="rgba(255,255,255,0.3)" />
          <circle cx="16" cy="46" r="5" fill="#162f4d" />
          <circle cx="48" cy="46" r="5" fill="#162f4d" />
          <circle cx="16" cy="46" r="3" fill="#f2a800" />
          <circle cx="48" cy="46" r="3" fill="#f2a800" />
          <circle cx="10" cy="32" r="2" fill="#f2a800" />
          <circle cx="14" cy="32" r="2" fill="#f2a800" />
        </svg>
        <h1>Change Password</h1>
      </div>
      <p class="tagline">Secure Your Account</p>

      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

        <div class="form-group <?= isset($errors['current']) ? 'has-error':'' ?>">
          <label for="current">Current Password *</label>
          <input type="password" id="current" name="current_password" placeholder="••••••••">
          <?php if (isset($errors['current'])): ?>
            <span class="error"><?= $errors['current'] ?></span>
          <?php endif; ?>
        </div>

        <div class="form-group <?= isset($errors['new']) ? 'has-error':'' ?>">
          <label for="new">New Password * <span class="optional">(min 8 characters)</span></label>
          <input type="password" id="new" name="new_password" placeholder="••••••••">
          <?php if (isset($errors['new'])): ?>
            <span class="error"><?= $errors['new'] ?></span>
          <?php endif; ?>
        </div>

        <div class="form-group <?= isset($errors['confirm']) ? 'has-error':'' ?>">
          <label for="confirm">Confirm New Password *</label>
          <input type="password" id="confirm" name="confirm_password" placeholder="••••••••">
          <?php if (isset($errors['confirm'])): ?>
            <span class="error"><?= $errors['confirm'] ?></span>
          <?php endif; ?>
        </div>

        <div class="form-actions" style="display:flex;gap:0.75rem;margin-top:1.5rem;">
          <button type="submit" class="btn btn-primary" style="flex:1;">Update Password</button>
          <a href="/autocare/index.php" class="btn btn-secondary" style="flex:1;text-align:center;">Cancel</a>
        </div>
      </form>

      <div class="auth-footer">
        <p><a href="/autocare/index.php">Back to dashboard</a></p>
      </div>
    </div>
  </div>
</body>
</html>