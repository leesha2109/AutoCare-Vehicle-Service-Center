<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';
require_role('admin');

$stmt = $pdo->query("
    SELECT m.*,
           COUNT(j.id) as active_jobs
    FROM mechanics m
    LEFT JOIN job_cards j
           ON j.mechanic_id = m.id
          AND j.status IN ('pending','in_progress')
    WHERE m.is_active = 1
    GROUP BY m.id
    ORDER BY m.name
");
$mechanics = $stmt->fetchAll();

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Mechanics — AutoCare</title>
  <link rel="stylesheet" href="/autocare/public/css/style.css">
</head>
<body>
<div class="container">
  <div class="page-header">
    <h2>Mechanics</h2>
    <a href="create.php" class="btn btn-primary">+ Add Mechanic</a>
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
        <th>Name</th>
        <th>Specialization</th>
        <th>Phone</th>
        <th>Status</th>
        <th>Active Jobs</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($mechanics)): ?>
        <tr><td colspan="7" class="empty">No mechanics found.</td></tr>
      <?php else: ?>
        <?php foreach ($mechanics as $i => $m): ?>
          <tr>
            <td><?= $i + 1 ?></td>
            <td><?= htmlspecialchars($m['name']) ?></td>
            <td><?= htmlspecialchars($m['specialization']) ?></td>
            <td><?= htmlspecialchars($m['phone']) ?></td>
            <td>
              <span class="badge badge-<?= $m['status'] ?>">
                <?= ucfirst($m['status']) ?>
              </span>
            </td>
            <td><?= $m['active_jobs'] ?></td>
            <td class="actions">
              <a href="edit.php?id=<?= $m['id'] ?>">Edit</a>
              <a href="toggle.php?id=<?= $m['id'] ?>"
                 onclick="return confirm('Toggle availability?')">
                <?= $m['status'] === 'off' ? 'Activate' : 'Set Off' ?>
              </a>
              <?php if ($m['active_jobs'] == 0): ?>
                <a href="delete.php?id=<?= $m['id'] ?>"
                   class="danger"
                   onclick="return confirm('Remove this mechanic?')">Remove</a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>
</body>
</html>