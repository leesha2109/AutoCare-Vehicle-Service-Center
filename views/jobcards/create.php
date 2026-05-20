<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';
require_login();

// Load available mechanics
$mechs = $pdo->query("
    SELECT * FROM mechanics
    WHERE status = 'available' AND is_active = 1
    ORDER BY name
")->fetchAll();

// Load all vehicles with customer name
$vehicles = $pdo->query("
    SELECT v.*, c.name as customer_name
    FROM vehicles v
    JOIN customers c ON c.id = v.customer_id
    WHERE v.is_active = 1
    ORDER BY v.plate_no
")->fetchAll();

$errors = [];
$old    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $old = $_POST;
    if (empty($_POST['vehicle_id']))   $errors['vehicle_id']   = 'Select a vehicle.';
    if (empty($_POST['mechanic_id']))  $errors['mechanic_id']  = 'Assign a mechanic.';
    if (empty($_POST['problem_desc'])) $errors['problem_desc'] = 'Describe the problem.';

    if (empty($errors)) {
        $pdo->beginTransaction();
        try {
            // Create job card
            $stmt = $pdo->prepare("
                INSERT INTO job_cards
                    (vehicle_id, mechanic_id, advisor_id, problem_desc, labour_cost, notes, status)
                VALUES (?, ?, ?, ?, ?, ?, 'pending')
            ");
            $stmt->execute([
                (int)$_POST['vehicle_id'],
                (int)$_POST['mechanic_id'],
                $_SESSION['user_id'],
                trim($_POST['problem_desc']),
                (float)($_POST['labour_cost'] ?? 0),
                trim($_POST['notes']) ?: null,
            ]);

            // Set mechanic to busy
            $upd = $pdo->prepare("UPDATE mechanics SET status='busy' WHERE id=?");
            $upd->execute([(int)$_POST['mechanic_id']]);

            $pdo->commit();
            $_SESSION['flash'] = ['type'=>'success','message'=>'Job card created.'];
            header('Location: index.php');
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
  <title>New Job Card — AutoCare</title>
  <link rel="stylesheet" href="/autocare/public/css/style.css">
</head>
<body>
<div class="container">
  <div class="page-header">
    <h2>Create Job Card</h2>
    <a href="index.php" class="btn btn-secondary">← Back</a>
  </div>

  <?php if (isset($errors['general'])): ?>
    <div class="alert alert-error"><?= $errors['general'] ?></div>
  <?php endif; ?>

  <form method="POST" class="form-card">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

    <div class="form-group <?= isset($errors['vehicle_id']) ? 'has-error':'' ?>">
      <label>Vehicle *</label>
      <select name="vehicle_id">
        <option value="">-- Select vehicle --</option>
        <?php foreach ($vehicles as $v): ?>
          <option value="<?= $v['id'] ?>"
            <?= ($old['vehicle_id'] ?? '') == $v['id'] ? 'selected':'' ?>>
            <?= htmlspecialchars($v['plate_no'] . ' — ' . $v['make'] . ' ' . $v['model'] . ' (' . $v['customer_name'] . ')') ?>
          </option>
        <?php endforeach; ?>
      </select>
      <?php if (isset($errors['vehicle_id'])): ?><span class="error"><?= $errors['vehicle_id'] ?></span><?php endif; ?>
    </div>

    <div class="form-group <?= isset($errors['mechanic_id']) ? 'has-error':'' ?>">
      <label>Assign Mechanic *</label>
      <select name="mechanic_id">
        <option value="">-- Select mechanic --</option>
        <?php foreach ($mechs as $m): ?>
          <option value="<?= $m['id'] ?>"
            <?= ($old['mechanic_id'] ?? '') == $m['id'] ? 'selected':'' ?>>
            <?= htmlspecialchars($m['name'] . ' — ' . $m['specialization']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <?php if (isset($errors['mechanic_id'])): ?><span class="error"><?= $errors['mechanic_id'] ?></span><?php endif; ?>
    </div>

    <div class="form-group <?= isset($errors['problem_desc']) ? 'has-error':'' ?>">
      <label>Problem Description *</label>
      <textarea name="problem_desc" rows="4"
                placeholder="Describe the issue in detail..."><?=
        htmlspecialchars($old['problem_desc'] ?? '')
      ?></textarea>
      <?php if (isset($errors['problem_desc'])): ?><span class="error"><?= $errors['problem_desc'] ?></span><?php endif; ?>
    </div>

    <div class="form-group">
      <label>Labour Cost (LKR)</label>
      <input type="number" name="labour_cost" step="0.01" min="0"
             value="<?= htmlspecialchars($old['labour_cost'] ?? '0') ?>">
    </div>

    <div class="form-group">
      <label>Notes <span class="optional">(optional)</span></label>
      <textarea name="notes" rows="2"><?= htmlspecialchars($old['notes'] ?? '') ?></textarea>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn btn-primary">Create Job Card</button>
      <a href="index.php" class="btn btn-secondary">Cancel</a>
    </div>
  </form>
</div>
</body>
</html>