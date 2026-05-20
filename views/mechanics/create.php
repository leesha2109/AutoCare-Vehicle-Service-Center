<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';
require_role('admin');

$errors = [];
$old    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $old = $_POST;
    if (empty($_POST['name']))           $errors['name']           = 'Name is required.';
    if (empty($_POST['specialization'])) $errors['specialization'] = 'Specialization is required.';
    if (empty($_POST['phone']))          $errors['phone']          = 'Phone is required.';

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            INSERT INTO mechanics (name, specialization, phone, status)
            VALUES (?, ?, ?, 'available')
        ");
        $stmt->execute([
            trim($_POST['name']),
            trim($_POST['specialization']),
            trim($_POST['phone']),
        ]);
        $_SESSION['flash'] = ['type'=>'success','message'=>'Mechanic added.'];
        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Add Mechanic — AutoCare</title>
  <link rel="stylesheet" href="/autocare/public/css/style.css">
</head>
<body>
<div class="container">
  <div class="page-header">
    <h2>Add Mechanic</h2>
    <a href="index.php" class="btn btn-secondary">← Back</a>
  </div>
  <form method="POST" class="form-card">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <div class="form-group <?= isset($errors['name']) ? 'has-error':'' ?>">
      <label>Full Name *</label>
      <input type="text" name="name" value="<?= htmlspecialchars($old['name'] ?? '') ?>">
      <?php if (isset($errors['name'])): ?><span class="error"><?= $errors['name'] ?></span><?php endif; ?>
    </div>
    <div class="form-group <?= isset($errors['specialization']) ? 'has-error':'' ?>">
      <label>Specialization *</label>
      <select name="specialization">
        <option value="">-- Select --</option>
        <?php
        $specs = ['Engine & Transmission','Electrical Systems','Body Work & Paint','Brakes & Suspension','Air Conditioning','General Service'];
        foreach ($specs as $s):
        ?>
          <option value="<?= $s ?>" <?= ($old['specialization'] ?? '') === $s ? 'selected':'' ?>>
            <?= $s ?>
          </option>
        <?php endforeach; ?>
      </select>
      <?php if (isset($errors['specialization'])): ?><span class="error"><?= $errors['specialization'] ?></span><?php endif; ?>
    </div>
    <div class="form-group <?= isset($errors['phone']) ? 'has-error':'' ?>">
      <label>Phone *</label>
      <input type="text" name="phone" value="<?= htmlspecialchars($old['phone'] ?? '') ?>">
      <?php if (isset($errors['phone'])): ?><span class="error"><?= $errors['phone'] ?></span><?php endif; ?>
    </div>
    <div class="form-actions">
      <button type="submit" class="btn btn-primary">Save Mechanic</button>
      <a href="index.php" class="btn btn-secondary">Cancel</a>
    </div>
  </form>
</div>
</body>
</html>