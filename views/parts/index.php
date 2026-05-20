<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';
require_role('admin');

$parts = $pdo->query("
    SELECT * FROM parts
    WHERE is_active = 1
    ORDER BY name
")->fetchAll();

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Parts Inventory — AutoCare</title>
  <link rel="stylesheet" href="/autocare/public/css/style.css">
</head>
<body>
<div class="container">
  <div class="page-header">
    <h2>Parts Inventory</h2>
    <a href="create.php" class="btn btn-primary">+ Add Part</a>
  </div>

  <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?>">
      <?= htmlspecialchars($flash['message']) ?>
    </div>
  <?php endif; ?>

  <table class="data-table">
    <thead>
      <tr>
        <th>#</th>
        <th>Part Name</th>
        <th>Part No</th>
        <th>In Stock</th>
        <th>Unit Price (LKR)</th>
        <th>Low Stock At</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($parts)): ?>
        <tr><td colspan="7" class="empty">No parts found.</td></tr>
      <?php else: ?>
        <?php foreach ($parts as $i => $p): ?>
          <?php $low = $p['qty'] <= $p['low_stock_threshold']; ?>
          <tr class="<?= $low ? 'row-danger' : '' ?>">
            <td><?= $i + 1 ?></td>
            <td>
              <?= htmlspecialchars($p['name']) ?>
              <?php if ($low): ?>
                <span class="badge badge-danger">Low Stock</span>
              <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($p['part_no']) ?></td>
            <td><strong><?= $p['qty'] ?></strong></td>
            <td><?= number_format($p['unit_price'], 2) ?></td>
            <td><?= $p['low_stock_threshold'] ?></td>
            <td class="actions">
              <a href="edit.php?id=<?= $p['id'] ?>">Edit</a>
              <a href="delete.php?id=<?= $p['id'] ?>"
                 class="danger"
                 onclick="return confirm('Remove this part?')">Delete</a>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>
</body>
</html>