<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';
require_login();

$job_id = (int)($_GET['job_id'] ?? 0);

// Fetch job — must be in_progress
$stmt = $pdo->prepare("SELECT * FROM job_cards WHERE id = ?");
$stmt->execute([$job_id]);
$job = $stmt->fetch();
if (!$job || $job['status'] !== 'in_progress') {
    die('Parts can only be added to in-progress jobs.');
}

// Available parts
$parts = $pdo->query("
    SELECT * FROM parts WHERE is_active = 1 AND qty > 0 ORDER BY name
")->fetchAll();

// Already added parts on this job
$added = $pdo->prepare("
    SELECT jp.*, p.name as part_name, p.part_no
    FROM job_parts jp
    JOIN parts p ON p.id = jp.part_id
    WHERE jp.job_card_id = ?
");
$added->execute([$job_id]);
$added_parts = $added->fetchAll();

// Calculate parts total
$parts_total = array_sum(array_map(
    fn($p) => $p['qty_used'] * $p['price_at_time'],
    $added_parts
));

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $part_id  = (int)($_POST['part_id']  ?? 0);
    $qty_used = (int)($_POST['qty_used'] ?? 0);

    if (!$part_id)        $errors['part_id']  = 'Select a part.';
    if ($qty_used < 1)    $errors['qty_used'] = 'Quantity must be at least 1.';

    // Check sufficient stock
    if (empty($errors)) {
        $pstmt = $pdo->prepare("SELECT * FROM parts WHERE id = ? AND is_active = 1");
        $pstmt->execute([$part_id]);
        $part = $pstmt->fetch();

        if (!$part) {
            $errors['part_id'] = 'Part not found.';
        } elseif ($part['qty'] < $qty_used) {
            $errors['qty_used'] = "Only {$part['qty']} units in stock.";
        }
    }

    if (empty($errors)) {
        $pdo->beginTransaction();
        try {
            // Insert into job_parts
            $ins = $pdo->prepare("
                INSERT INTO job_parts (job_card_id, part_id, qty_used, price_at_time)
                VALUES (?, ?, ?, ?)
            ");
            $ins->execute([
                $job_id,
                $part_id,
                $qty_used,
                $part['unit_price'], // lock price at time of use
            ]);

            // Deduct stock
            $upd = $pdo->prepare("
                UPDATE parts SET qty = qty - ? WHERE id = ?
            ");
            $upd->execute([$qty_used, $part_id]);

            $pdo->commit();
            $_SESSION['flash'] = ['type'=>'success','message'=>'Part added to job.'];
            header("Location: add_part.php?job_id=$job_id");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors['general'] = 'Transaction failed. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Add Parts — Job #<?= $job_id ?></title>
  <link rel="stylesheet" href="/autocare/public/css/style.css">
</head>
<body>
<div class="container">
  <div class="page-header">
    <h2>Add Parts — Job Card #<?= $job_id ?></h2>
    <a href="view.php?id=<?= $job_id ?>" class="btn btn-secondary">← Back to Job</a>
  </div>

  <?php $flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); ?>
  <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?>"><?= htmlspecialchars($flash['message']) ?></div>
  <?php endif; ?>
  <?php if (isset($errors['general'])): ?>
    <div class="alert alert-error"><?= $errors['general'] ?></div>
  <?php endif; ?>

  <!-- Add part form -->
  <form method="POST" class="form-card">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <div class="form-group <?= isset($errors['part_id']) ? 'has-error':'' ?>">
      <label>Select Part *</label>
      <select name="part_id">
        <option value="">-- Choose part --</option>
        <?php foreach ($parts as $p): ?>
          <option value="<?= $p['id'] ?>">
            <?= htmlspecialchars($p['name'] . ' (' . $p['part_no'] . ') — Stock: ' . $p['qty'] . ' — LKR ' . number_format($p['unit_price'], 2)) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <?php if (isset($errors['part_id'])): ?><span class="error"><?= $errors['part_id'] ?></span><?php endif; ?>
    </div>
    <div class="form-group <?= isset($errors['qty_used']) ? 'has-error':'' ?>">
      <label>Quantity Used *</label>
      <input type="number" name="qty_used" min="1" value="1">
      <?php if (isset($errors['qty_used'])): ?><span class="error"><?= $errors['qty_used'] ?></span><?php endif; ?>
    </div>
    <div class="form-actions">
      <button type="submit" class="btn btn-primary">Add Part</button>
    </div>
  </form>

  <!-- Parts already added -->
  <h3 style="margin-top:1.5rem">Parts Used on This Job</h3>
  <table class="data-table">
    <thead>
      <tr>
        <th>Part Name</th>
        <th>Part No</th>
        <th>Qty Used</th>
        <th>Unit Price</th>
        <th>Line Total</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($added_parts)): ?>
        <tr><td colspan="6" class="empty">No parts added yet.</td></tr>
      <?php else: ?>
        <?php foreach ($added_parts as $ap): ?>
          <tr>
            <td><?= htmlspecialchars($ap['part_name']) ?></td>
            <td><?= htmlspecialchars($ap['part_no']) ?></td>
            <td><?= $ap['qty_used'] ?></td>
            <td>LKR <?= number_format($ap['price_at_time'], 2) ?></td>
            <td>LKR <?= number_format($ap['qty_used'] * $ap['price_at_time'], 2) ?></td>
            <td>
              <a href="remove_part.php?id=<?= $ap['id'] ?>&job_id=<?= $job_id ?>"
                 class="danger"
                 onclick="return confirm('Remove this part?')">Remove</a>
            </td>
          </tr>
        <?php endforeach; ?>
        <tr style="font-weight:500">
          <td colspan="4" style="text-align:right">Parts Total:</td>
          <td>LKR <?= number_format($parts_total, 2) ?></td>
          <td></td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
</body>
</html>