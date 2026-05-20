<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';
require_login();

$id   = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("
    SELECT j.*,
           v.plate_no, v.make, v.model, v.year, v.mileage,
           c.name   AS customer_name, c.phone AS customer_phone,
           m.name   AS mechanic_name, m.specialization,
           u.name   AS advisor_name
    FROM job_cards j
    JOIN vehicles  v ON v.id = j.vehicle_id
    JOIN customers c ON c.id = v.customer_id
    JOIN mechanics m ON m.id = j.mechanic_id
    JOIN users     u ON u.id = j.advisor_id
    WHERE j.id = ?
");
$stmt->execute([$id]);
$job = $stmt->fetch();
if (!$job) { http_response_code(404); die('Job card not found.'); }

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Job Card #<?= $id ?> — AutoCare</title>
  <link rel="stylesheet" href="/autocare/public/css/style.css">
</head>
<body>
<div class="container">
  <div class="page-header">
    <h2>Job Card #<?= $id ?></h2>
    <a href="index.php" class="btn btn-secondary">← Back</a>
  </div>

  <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?>"><?= htmlspecialchars($flash['message']) ?></div>
  <?php endif; ?>

  <div class="detail-grid">
    <div class="detail-card">
      <h3>Vehicle</h3>
      <p><strong>Plate:</strong> <?= htmlspecialchars($job['plate_no']) ?></p>
      <p><strong>Vehicle:</strong> <?= htmlspecialchars($job['make'] . ' ' . $job['model'] . ' (' . $job['year'] . ')') ?></p>
      <p><strong>Customer:</strong> <?= htmlspecialchars($job['customer_name']) ?></p>
      <p><strong>Phone:</strong> <?= htmlspecialchars($job['customer_phone']) ?></p>
    </div>
    <div class="detail-card">
      <h3>Assignment</h3>
      <p><strong>Mechanic:</strong> <?= htmlspecialchars($job['mechanic_name']) ?></p>
      <p><strong>Specialization:</strong> <?= htmlspecialchars($job['specialization']) ?></p>
      <p><strong>Advisor:</strong> <?= htmlspecialchars($job['advisor_name']) ?></p>
      <p><strong>Labour Cost:</strong> LKR <?= number_format($job['labour_cost'], 2) ?></p>
    </div>
    <div class="detail-card">
      <h3>Timeline</h3>
      <p><strong>Created:</strong> <?= date('d M Y H:i', strtotime($job['created_at'])) ?></p>
      <p><strong>Started:</strong> <?= $job['start_at'] ? date('d M Y H:i', strtotime($job['start_at'])) : '—' ?></p>
      <p><strong>Completed:</strong> <?= $job['end_at'] ? date('d M Y H:i', strtotime($job['end_at'])) : '—' ?></p>
      <p><strong>Status:</strong> <span class="badge badge-<?= $job['status'] ?>"><?= ucfirst(str_replace('_',' ',$job['status'])) ?></span></p>
    </div>
  </div>

  <div class="detail-card" style="margin-top:10px">
    <h3>Problem Description</h3>
    <p><?= nl2br(htmlspecialchars($job['problem_desc'])) ?></p>
    <?php if ($job['notes']): ?>
      <h3>Notes</h3>
      <p><?= nl2br(htmlspecialchars($job['notes'])) ?></p>
    <?php endif; ?>
  </div>

  <!-- Status action buttons -->
  <?php if (current_role() !== 'customer'): ?>
    <div class="action-bar" style="margin-top:16px;display:flex;gap:8px;flex-wrap:wrap;">
      <?php if ($job['status'] === 'pending'): ?>
        <a href="update_status.php?id=<?= $id ?>&status=in_progress"
           class="btn btn-primary"
           onclick="return confirm('Start this job?')">▶ Start Job</a>
        <a href="update_status.php?id=<?= $id ?>&status=cancelled"
           class="btn btn-danger"
           onclick="return confirm('Cancel this job?')">✕ Cancel</a>
      <?php elseif ($job['status'] === 'in_progress'): ?>
        <a href="add_part.php?job_id=<?= $id ?>" class="btn btn-secondary">
          + Add Parts</a>
        <a href="update_status.php?id=<?= $id ?>&status=completed"
           class="btn btn-primary"
           onclick="return confirm('Mark as completed?')">✓ Complete</a>
        <a href="update_status.php?id=<?= $id ?>&status=cancelled"
           class="btn btn-danger"
           onclick="return confirm('Cancel this job?')">✕ Cancel</a>
      <?php elseif ($job['status'] === 'completed'): ?>
        <a href="generate_invoice.php?job_id=<?= $id ?>" class="btn btn-primary">
          📋 Generate Invoice</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>
</body>
</html>