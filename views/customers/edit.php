<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ? AND is_active = 1");
$stmt->execute([$id]);
$customer = $stmt->fetch();
if (!$customer) { http_response_code(404); die('Customer not found.'); }

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    if (empty($_POST['name']))  $errors['name']  = 'Name is required.';
    if (empty($_POST['phone'])) $errors['phone'] = 'Phone is required.';

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            UPDATE customers
            SET name=?, phone=?, email=?, address=?
            WHERE id=?
        ");
        $stmt->execute([
            trim($_POST['name']),
            trim($_POST['phone']),
            trim($_POST['email'])   ?: null,
            trim($_POST['address']) ?: null,
            $id
        ]);
        $_SESSION['flash'] = ['type'=>'success','message'=>'Customer updated.'];
        header('Location: index.php');
        exit;
    }
    $customer = array_merge($customer, $_POST);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Customer — AutoCare</title>
  <link rel="stylesheet" href="/autocare/public/css/style.css">
</head>
<body>
<div class="container">
  <div class="page-header">
    <h2>Edit Customer</h2>
    <a href="index.php" class="btn btn-secondary">← Back</a>
  </div>
  <form method="POST" class="form-card">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <div class="form-group <?= isset($errors['name']) ? 'has-error':'' ?>">
      <label>Full Name *</label>
      <input type="text" name="name" value="<?= htmlspecialchars($customer['name']) ?>">
      <?php if (isset($errors['name'])): ?><span class="error"><?= $errors['name'] ?></span><?php endif; ?>
    </div>
    <div class="form-group">
      <label>NIC <span class="optional">(cannot change)</span></label>
      <input type="text" value="<?= htmlspecialchars($customer['nic']) ?>" disabled>
    </div>
    <div class="form-group <?= isset($errors['phone']) ? 'has-error':'' ?>">
      <label>Phone *</label>
      <input type="text" name="phone" value="<?= htmlspecialchars($customer['phone']) ?>">
      <?php if (isset($errors['phone'])): ?><span class="error"><?= $errors['phone'] ?></span><?php endif; ?>
    </div>
    <div class="form-group">
      <label>Email</label>
      <input type="email" name="email" value="<?= htmlspecialchars($customer['email'] ?? '') ?>">
    </div>
    <div class="form-group">
      <label>Address</label>
      <textarea name="address" rows="3"><?= htmlspecialchars($customer['address'] ?? '') ?></textarea>
    </div>
    <div class="form-actions">
      <button type="submit" class="btn btn-primary">Update Customer</button>
      <a href="index.php" class="btn btn-secondary">Cancel</a>
    </div>
  </form>
</div>
</body>
</html>