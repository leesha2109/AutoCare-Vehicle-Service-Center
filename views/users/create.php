<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';
require_role('admin');

$errors = [];
$old = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $old = $_POST;

    if (empty($_POST['name'])) {
        $errors['name'] = 'Name is required.';
    }
    if (empty($_POST['email'])) {
        $errors['email'] = 'Email is required.';
    }
    if (empty($_POST['password'])) {
        $errors['password'] = 'Password is required.';
    } elseif (strlen($_POST['password']) < 8) {
        $errors['password'] = 'Password must be at least 8 characters.';
    }
    if (empty($_POST['password_confirm']) || $_POST['password'] !== $_POST['password_confirm']) {
        $errors['password_confirm'] = 'Passwords do not match.';
    }

    $role = $_POST['role'] ?? 'advisor';
    if (!in_array($role, ['admin', 'advisor'], true)) {
        $errors['role'] = 'Invalid role selected.';
    }

    if (empty($errors['email'])) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([trim($_POST['email'])]);
        if ($stmt->fetch()) {
            $errors['email'] = 'A user with this email already exists.';
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, is_active) VALUES (?, ?, ?, ?, 1)");
        $stmt->execute([
            trim($_POST['name']),
            trim($_POST['email']),
            password_hash($_POST['password'], PASSWORD_BCRYPT),
            $role,
        ]);

        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Staff member added successfully.'];
        header('Location: /autocare/views/dashboard/admin.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Add Staff — AutoCare</title>
  <link rel="stylesheet" href="/autocare/public/css/style.css">
</head>
<body>
<div class="container">
  <div class="page-header">
    <h2>Add Staff</h2>
    <a href="/autocare/views/dashboard/admin.php" class="btn btn-secondary">← Back</a>
  </div>

  <form method="POST" class="form-card">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

    <div class="form-group <?= isset($errors['name']) ? 'has-error':'' ?>">
      <label>Name *</label>
      <input type="text" name="name" value="<?= htmlspecialchars($old['name'] ?? '') ?>">
      <?php if (isset($errors['name'])): ?><span class="error"><?= $errors['name'] ?></span><?php endif; ?>
    </div>

    <div class="form-group <?= isset($errors['email']) ? 'has-error':'' ?>">
      <label>Email *</label>
      <input type="email" name="email" value="<?= htmlspecialchars($old['email'] ?? '') ?>">
      <?php if (isset($errors['email'])): ?><span class="error"><?= $errors['email'] ?></span><?php endif; ?>
    </div>

    <div class="form-group <?= isset($errors['role']) ? 'has-error':'' ?>">
      <label>Role *</label>
      <select name="role">
        <option value="advisor" <?= ($old['role'] ?? '') === 'advisor' ? 'selected' : '' ?>>Advisor</option>
        <option value="admin" <?= ($old['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
      </select>
      <?php if (isset($errors['role'])): ?><span class="error"><?= $errors['role'] ?></span><?php endif; ?>
    </div>

    <div class="form-group <?= isset($errors['password']) ? 'has-error':'' ?>">
      <label>Password *</label>
      <input type="password" name="password">
      <?php if (isset($errors['password'])): ?><span class="error"><?= $errors['password'] ?></span><?php endif; ?>
    </div>

    <div class="form-group <?= isset($errors['password_confirm']) ? 'has-error':'' ?>">
      <label>Confirm Password *</label>
      <input type="password" name="password_confirm">
      <?php if (isset($errors['password_confirm'])): ?><span class="error"><?= $errors['password_confirm'] ?></span><?php endif; ?>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn btn-primary">Create Staff Member</button>
      <a href="/autocare/views/dashboard/admin.php" class="btn btn-secondary">Cancel</a>
    </div>
  </form>
</div>
</body>
</html>
