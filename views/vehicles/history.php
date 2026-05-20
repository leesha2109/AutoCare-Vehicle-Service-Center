<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';
require_login();

$vehicle_id = (int)($_GET['vehicle_id'] ?? 0);

// Fetch vehicle + owner
$stmt = $pdo->prepare("
    SELECT v.*, c.name AS customer_name,
           c.email AS customer_email
    FROM vehicles v
    JOIN customers c ON c.id = v.customer_id
    WHERE v.id = ?
");
$stmt->execute([$vehicle_id]);
$vehicle = $stmt->fetch();
if (!$vehicle) { http_response_code(404); die('Vehicle not found.'); }

// Customers can only view their own vehicles
if (current_role() === 'customer') {
    $own = $pdo->prepare("
        SELECT c.id FROM customers c
        JOIN users u ON u.email = c.email
        WHERE u.id = ?
    ");
    $own->execute([$_SESSION['user_id']]);
    $my = $own->fetch();
    if (!$my || $my['id'] !== (int)$vehicle['customer_id']) {
        http_response_code(403);
        die('Access denied.');
    }
}

// Full service history — JOIN across 4 tables
$history = $pdo->prepare("
    SELECT j.id,
           j.problem_desc,
           j.status,
           j.labour_cost,
           j.start_at,
           j.end_at,
           j.created_at,
           j.notes,
           m.name  AS mechanic_name,
           u.name  AS advisor_name,
           i.total_amount,
           i.payment_status,
           COALESCE(
             (SELECT SUM(jp.qty_used * jp.price_at_time)
              FROM job_parts jp
              WHERE jp.job_card_id = j.id), 0
           ) AS parts_cost,
           GROUP_CONCAT(
             p.name ORDER BY p.name SEPARATOR ', '
           ) AS parts_used
    FROM job_cards j
    JOIN mechanics m ON m.id = j.mechanic_id
    JOIN users     u ON u.id = j.advisor_id
    LEFT JOIN invoices  i  ON i.job_card_id = j.id
    LEFT JOIN job_parts jp2 ON jp2.job_card_id = j.id
    LEFT JOIN parts     p   ON p.id = jp2.part_id
    WHERE j.vehicle_id = ?
    GROUP BY j.id
    ORDER BY j.created_at DESC
");
$history->execute([$vehicle_id]);
$records = $history->fetchAll();

// Summary stats
$total_visits  = count($records);
$total_spent   = array_sum(array_column($records, 'total_amount'));
$completed     = count(array_filter($records, fn($r) => $r['status'] === 'completed'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Service History — <?= htmlspecialchars($vehicle['plate_no']) ?></title>
  <link rel="stylesheet" href="/autocare/public/css/style.css">
</head>
<body>
<div class="container">

  <div class="page-header">
    <h2>Service History</h2>
    <a href="javascript:history.back()" class="btn btn-secondary">← Back</a>
  </div>

  <!-- Vehicle summary -->
  <div class="detail-grid" style="margin-bottom:1rem">
    <div class="detail-card">
      <h3>Vehicle</h3>
      <p><strong>Plate:</strong>   <?= htmlspecialchars($vehicle['plate_no']) ?></p>
      <p><strong>Vehicle:</strong> <?= htmlspecialchars($vehicle['make'] . ' ' . $vehicle['model'] . ' (' . $vehicle['year'] . ')') ?></p>
      <p><strong>Owner:</strong>   <?= htmlspecialchars($vehicle['customer_name']) ?></p>
      <p><strong>Mileage:</strong> <?= number_format($vehicle['mileage']) ?> km</p>
    </div>
    <div class="detail-card">
      <h3>Summary</h3>
      <p><strong>Total Visits:</strong>    <?= $total_visits ?></p>
      <p><strong>Completed Jobs:</strong>  <?= $completed ?></p>
      <p><strong>Total Spent:</strong>     LKR <?= number_format($total_spent, 2) ?></p>
    </div>
  </div>

  <!-- History timeline -->
  <?php if (empty($records)): ?>
    <div class="alert alert-info">No service history found for this vehicle.</div>
  <?php else: ?>
    <?php foreach ($records as $r): ?>
      <div class="history-card">
        <div class="history-header">
          <div>
            <span class="badge badge-<?= $r['status'] ?>">
              <?= ucfirst(str_replace('_', ' ', $r['status'])) ?>
            </span>
            <strong style="margin-left:8px">
              Job #<?= $r['id'] ?>
            </strong>
          </div>
          <span class="history-date">
            <?= date('d M Y', strtotime($r['created_at'])) ?>
          </span>
        </div>

        <div class="history-body">
          <p><strong>Problem:</strong>
            <?= nl2br(htmlspecialchars($r['problem_desc'])) ?>
          </p>

          <?php if ($r['parts_used']): ?>
            <p><strong>Parts Replaced:</strong>
              <?= htmlspecialchars($r['parts_used']) ?>
            </p>
          <?php endif; ?>

          <div class="history-meta">
            <span>Mechanic: <?= htmlspecialchars($r['mechanic_name']) ?></span>
            <span>Advisor: <?= htmlspecialchars($r['advisor_name']) ?></span>
            <?php if ($r['end_at']): ?>
              <span>Completed: <?= date('d M Y', strtotime($r['end_at'])) ?></span>
            <?php endif; ?>
            <?php if ($r['total_amount']): ?>
              <span><strong>Total: LKR <?= number_format($r['total_amount'], 2) ?></strong></span>
            <?php endif; ?>
          </div>

          <?php if ($r['status'] === 'completed'): ?>
            <a href="/autocare/views/jobcards/invoice.php?job_id=<?= $r['id'] ?>"
               class="btn btn-secondary" style="margin-top:8px;font-size:12px">
              View Invoice
            </a>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

</div>
</body>
</html>