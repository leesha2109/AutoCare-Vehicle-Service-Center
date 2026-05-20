<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';
require_role('admin');

$errors = [];
$old    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $old = $_POST;
    if (empty($_POST['name']))       $errors['name']       = 'Part name is required.';
    if (empty($_POST['part_no']))    $errors['part_no']    = 'Part number is required.';
    if (!isset($_POST['unit_price']) || $_POST['unit_price'] === '')
                                     $errors['unit_price'] = 'Unit price is required.';
    if (!isset($_POST['qty']) || $_POST['qty'] === '')
                                     $errors['qty']        = 'Quantity is required.';

    // Part number unique check
    if (empty($errors['part_no'])) {
        $chk = $pdo->prepare("SELECT id FROM parts WHERE part_no = ?");
        $chk->execute([trim($_POST['part_no'])]);
        if ($chk->fetch()) $errors['part_no'] = 'Part number already exists.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            INSERT INTO parts (name, part_no, qty, unit_price, low_stock_threshold)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            trim($_POST['name']),
            trim($_POST['part_no']),
            (int)$_POST['qty'],
            (float)$_POST['unit_price'],
            (int)($_POST['low_stock_threshold'] ?? 5),
        ]);
        $_SESSION['flash'] = ['type'=>'success','message'=>'Part added to inventory.'];
        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Add Part — AutoCare</title>
  <link rel="stylesheet" href="/autocare/public/css/style.css">
</head>
<body>
<div class="container">
  <div class="page-header">
    <h2>Add New Part</h2>
    <a href="index.php" class="btn btn-secondary">← Back</a>
  </div>
  <form method="POST" class="form-card">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

    <div class="form-group <?= isset($errors['name']) ? 'has-error':'' ?>">
      <label>Part Name *</label>
      <input type="text" name="name"
             value="<?= htmlspecialchars($old['name'] ?? '') ?>"
             placeholder="Engine Oil Filter">
      <?php if (isset($errors['name'])): ?><span class="error"><?= $errors['name'] ?></span><?php endif; ?>
    </div>

    <div class="form-group <?= isset($errors['part_no']) ? 'has-error':'' ?>">
      <label>Part Number *</label>
      <input type="text" name="part_no"
             value="<?= htmlspecialchars($old['part_no'] ?? '') ?>"
             placeholder="EOF-001">
      <?php if (isset($errors['part_no'])): ?><span class="error"><?= $errors['part_no'] ?></span><?php endif; ?>
    </div>

    <div class="form-group <?= isset($errors['qty']) ? 'has-error':'' ?>">
      <label>Initial Stock Quantity *</label>
      <input type="number" name="qty" min="0"
             value="<?= htmlspecialchars($old['qty'] ?? '0') ?>">
      <?php if (isset($errors['qty'])): ?><span class="error"><?= $errors['qty'] ?></span><?php endif; ?>
    </div>

    <div class="form-group <?= isset($errors['unit_price']) ? 'has-error':'' ?>">
      <label>Unit Price (LKR) *</label>
      <input type="number" name="unit_price" step="0.01" min="0"
             value="<?= htmlspecialchars($old['unit_price'] ?? '') ?>"
             placeholder="850.00">
      <?php if (isset($errors['unit_price'])): ?><span class="error"><?= $errors['unit_price'] ?></span><?php endif; ?>
    </div>

    <div class="form-group">
      <label>Low Stock Alert Threshold</label>
      <input type="number" name="low_stock_threshold" min="1"
             value="<?= htmlspecialchars($old['low_stock_threshold'] ?? '5') ?>">
      <small class="hint">Row turns red when stock falls to or below this number.</small>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn btn-primary">Save Part</button>
      <a href="index.php" class="btn btn-secondary">Cancel</a>
    </div>
  </form>
</div>
</body>
</html>