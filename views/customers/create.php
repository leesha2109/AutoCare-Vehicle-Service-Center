<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';
require_login();

$errors = [];
$old    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $old = $_POST;

    // Validation
    if (empty($_POST['name']))  $errors['name']  = 'Name is required.';
    if (empty($_POST['nic']))   $errors['nic']   = 'NIC is required.';
    if (empty($_POST['phone'])) $errors['phone'] = 'Phone is required.';

    // NIC unique check
    if (empty($errors['nic'])) {
        $chk = $pdo->prepare("SELECT id FROM customers WHERE nic = ?");
        $chk->execute([trim($_POST['nic'])]);
        if ($chk->fetch()) $errors['nic'] = 'A customer with this NIC already exists.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            INSERT INTO customers (name, nic, phone, email, address)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            trim($_POST['name']),
            trim($_POST['nic']),
            trim($_POST['phone']),
            trim($_POST['email'])   ?: null,
            trim($_POST['address']) ?: null,
        ]);

        $_SESSION['flash'] = [
            'type'    => 'success',
            'message' => 'Customer added successfully.'
        ];
        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Add Customer — AutoCare</title>
  <link rel="stylesheet" href="/autocare/public/css/style.css">
</head>
<body>
<div class="container">
  <div class="page-header">
    <h2>Add New Customer</h2>
    <a href="index.php" class="btn btn-secondary">← Back</a>
  </div>

  <form method="POST" class="form-card">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

    <div class="form-group <?= isset($errors['name']) ? 'has-error' : '' ?>">
      <label>Full Name *</label>
      <input type="text" name="name"
             value="<?= htmlspecialchars($old['name'] ?? '') ?>"
             placeholder="Kamal Perera">
      <?php if (isset($errors['name'])): ?>
        <span class="error"><?= $errors['name'] ?></span>
      <?php endif; ?>
    </div>

    <div class="form-group <?= isset($errors['nic']) ? 'has-error' : '' ?>">
      <label>NIC Number *</label>
      <input type="text" name="nic"
             value="<?= htmlspecialchars($old['nic'] ?? '') ?>"
             placeholder="199012345678">
      <?php if (isset($errors['nic'])): ?>
        <span class="error"><?= $errors['nic'] ?></span>
      <?php endif; ?>
    </div>

    <div class="form-group <?= isset($errors['phone']) ? 'has-error' : '' ?>">
      <label>Phone *</label>
      <input type="text" name="phone"
             value="<?= htmlspecialchars($old['phone'] ?? '') ?>"
             placeholder="0771234567">
      <?php if (isset($errors['phone'])): ?>
        <span class="error"><?= $errors['phone'] ?></span>
      <?php endif; ?>
    </div>

    <div class="form-group">
      <label>Email <span class="optional">(optional)</span></label>
      <input type="email" name="email"
             value="<?= htmlspecialchars($old['email'] ?? '') ?>"
             placeholder="kamal@gmail.com">
    </div>

    <div class="form-group">
      <label>Address <span class="optional">(optional)</span></label>
      <textarea name="address" rows="3"
                placeholder="No 12, Kandy Road, Colombo"><?=
        htmlspecialchars($old['address'] ?? '')
      ?></textarea>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn btn-primary">Save Customer</button>
      <a href="index.php" class="btn btn-secondary">Cancel</a>
    </div>

  </form>
</div>
</body>
</html>