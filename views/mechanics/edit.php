<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';
require_role('admin');

$id   = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM mechanics WHERE id = ? AND is_active = 1");
$stmt->execute([$id]);
$mechanic = $stmt->fetch();
if (!$mechanic) { http_response_code(404); die('Mechanic not found.'); }

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    if (empty($_POST['name']))           $errors['name']           = 'Name is required.';
    if (empty($_POST['specialization'])) $errors['specialization'] = 'Specialization is required.';
    if (empty($_POST['phone']))          $errors['phone']          = 'Phone is required.';

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            UPDATE mechanics SET name=?, specialization=?, phone=? WHERE id=?
        ");
        $stmt->execute([
            trim($_POST['name']),
            trim($_POST['specialization']),
            trim($_POST['phone']),
            $id
        ]);
        $_SESSION['flash'] = ['type'=>'success','message'=>'Mechanic updated.'];
        header('Location: index.php');
        exit;
    }
    $mechanic = array_merge($mechanic, $_POST);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Mechanic — AutoCare</title>
  <link rel="stylesheet" href="/autocare/public/css/style.css">
</head>
<body>
<div class="container">
  <div class="page-header">
    <h2>Edit Mechanic</h2>
    <a href="index.php" class="btn btn-secondary">← Back</a>
  </div>
  <form method="POST" class="form-card">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <div class="form-group <?= isset($errors['name']) ? 'has-error':'' ?>">
      <label>Full Name *</label>
      <input type="text" name="name" value="<?= htmlspecialchars($mechanic['name']) ?>">
      <?php if (isset($errors['name'])): ?><span class="error"><?= $errors['name'] ?></span><?php endif; ?>
    </div>
    <div class="form-group <?= isset($errors['specialization']) ? 'has-error':'' ?>">
      <label>Specialization *</label>
      <select name="specialization">
        <?php
        $specs = ['Engine & Transmission','Electrical Systems','Body Work & Paint','Brakes & Suspension','Air Conditioning','General Service'];
        foreach ($specs as $s):
        ?>
          <option value="<?= $s ?>" <?= $mechanic['specialization'] === $s ? 'selected':'' ?>>
            <?= $s ?>
          </option>
        <?php endforeach; ?>
      </select>
      <?php if (isset($errors['specialization'])): ?><span class="error"><?= $errors['specialization'] ?></span><?php endif; ?>
    </div>
    <div class="form-group <?= isset($errors['phone']) ? 'has-error':'' ?>">
      <label>Phone *</label>
      <input type="text" name="phone" value="<?= htmlspecialchars($mechanic['phone']) ?>">
      <?php if (isset($errors['phone'])): ?><span class="error"><?= $errors['phone'] ?></span><?php endif; ?>
    </div>
    <div class="form-actions">
      <button type="submit" class="btn btn-primary">Update</button>
      <a href="index.php" class="btn btn-secondary">Cancel</a>
    </div>
  </form>
</div>
</body>
</html>