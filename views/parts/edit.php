<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';
require_role('admin');

$id   = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM parts WHERE id = ? AND is_active = 1");
$stmt->execute([$id]);
$part = $stmt->fetch();
if (!$part) { http_response_code(404); die('Part not found.'); }

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    if (empty($_POST['name']))       $errors['name']       = 'Part name is required.';
    if (!isset($_POST['unit_price']) || $_POST['unit_price'] === '')
                                     $errors['unit_price'] = 'Unit price is required.';
    if (!isset($_POST['qty']) || $_POST['qty'] === '')
                                     $errors['qty']        = 'Quantity is required.';

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            UPDATE parts
            SET name=?, qty=?, unit_price=?, low_stock_threshold=?
            WHERE id=?
        ");
        $stmt->execute([
            trim($_POST['name']),
            (int)$_POST['qty'],
            (float)$_POST['unit_price'],
            (int)($_POST['low_stock_threshold'] ?? 5),
            $id
        ]);
        $_SESSION['flash'] = ['type'=>'success','message'=>'Part updated.'];
        header('Location: index.php');
        exit;
    }
    $part = array_merge($part, $_POST);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Part — AutoCare</title>
  <link rel="stylesheet" href="/autocare/public/css/style.css">
</head>
<body>
<div class="container">
  <div class="page-header">
    <h2>Edit Part</h2>
    <a href="index.php" class="btn btn-secondary">← Back</a>
  </div>
  <form method="POST" class="form-card">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <div class="form-group <?= isset($errors['name']) ? 'has-error':'' ?>">
      <label>Part Name *</label>
      <input type="text" name="name" value="<?= htmlspecialchars($part['name']) ?>">
      <?php if (isset($errors['name'])): ?><span class="error"><?= $errors['name'] ?></span><?php endif; ?>
    </div>
    <div class="form-group">
      <label>Part Number <span class="optional">(cannot change)</span></label>
      <input type="text" value="<?= htmlspecialchars($part['part_no']) ?>" disabled>
    </div>
    <div class="form-group <?= isset($errors['qty']) ? 'has-error':'' ?>">
      <label>Stock Quantity *</label>
      <input type="number" name="qty" min="0" value="<?= htmlspecialchars($part['qty']) ?>">
      <?php if (isset($errors['qty'])): ?><span class="error"><?= $errors['qty'] ?></span><?php endif; ?>
    </div>
    <div class="form-group <?= isset($errors['unit_price']) ? 'has-error':'' ?>">
      <label>Unit Price (LKR) *</label>
      <input type="number" name="unit_price" step="0.01" min="0"
             value="<?= htmlspecialchars($part['unit_price']) ?>">
      <?php if (isset($errors['unit_price'])): ?><span class="error"><?= $errors['unit_price'] ?></span><?php endif; ?>
    </div>
    <div class="form-group">
      <label>Low Stock Threshold</label>
      <input type="number" name="low_stock_threshold" min="1"
             value="<?= htmlspecialchars($part['low_stock_threshold']) ?>">
    </div>
    <div class="form-actions">
      <button type="submit" class="btn btn-primary">Update Part</button>
      <a href="index.php" class="btn btn-secondary">Cancel</a>
    </div>
  </form>
</div>
</body>
</html>