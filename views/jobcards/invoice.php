<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';
require_login();

$job_id = (int)($_GET['job_id'] ?? 0);

// Fetch invoice with full job details
$stmt = $pdo->prepare("
    SELECT i.*,
           j.problem_desc, j.notes, j.start_at, j.end_at, j.created_at as job_date,
           v.plate_no, v.make, v.model, v.year,
           c.name   AS customer_name, c.phone AS customer_phone,
               c.email  AS customer_email, c.address AS customer_address,
           m.name   AS mechanic_name,
           u.name   AS advisor_name
    FROM invoices i
    JOIN job_cards j ON j.id = i.job_card_id
    JOIN vehicles  v ON v.id = j.vehicle_id
    JOIN customers c ON c.id = v.customer_id
    JOIN mechanics m ON m.id = j.mechanic_id
    JOIN users     u ON u.id = j.advisor_id
    WHERE i.job_card_id = ?
");
$stmt->execute([$job_id]);
$inv = $stmt->fetch();
if (!$inv) { die('Invoice not found. Please generate it first.'); }

// Fetch parts used
$parts = $pdo->prepare("
    SELECT jp.*, p.name as part_name, p.part_no
    FROM job_parts jp
    JOIN parts p ON p.id = jp.part_id
    WHERE jp.job_card_id = ?
");
$parts->execute([$job_id]);
$parts_list = $parts->fetchAll();

// Mark as paid handler
if (isset($_GET['mark_paid']) && $_GET['mark_paid'] === '1') {
    $upd = $pdo->prepare("
        UPDATE invoices SET payment_status='paid', paid_at=NOW()
        WHERE job_card_id=?
    ");
    $upd->execute([$job_id]);
    header("Location: invoice.php?job_id=$job_id");
    exit;
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Invoice #<?= $inv['id'] ?> — AutoCare</title>
  <link rel="stylesheet" href="/autocare/public/css/style.css">
  <style>
    /* Print styles */
    @media print {
      .no-print { display: none !important; }
      body { background: white; }
      .invoice-box { box-shadow: none; border: 1px solid #ccc; }
    }
  </style>
</head>
<body>

<!-- Toolbar (hidden on print) -->
<div class="container no-print">
  <div class="page-header" style="margin-bottom:1rem">
    <h2>Invoice #<?= $inv['id'] ?></h2>
    <div style="display:flex;gap:8px">
      <a href="view.php?id=<?= $job_id ?>" class="btn btn-secondary">← Back</a>
      <?php if ($inv['payment_status'] === 'unpaid' && current_role() !== 'customer'): ?>
        <a href="?job_id=<?= $job_id ?>&mark_paid=1"
           class="btn btn-primary"
           onclick="return confirm('Mark invoice as paid?')">✓ Mark as Paid</a>
      <?php endif; ?>
      <button onclick="window.print()" class="btn btn-secondary">🖨 Print</button>
    </div>
  </div>
  <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?>"><?= htmlspecialchars($flash['message']) ?></div>
  <?php endif; ?>
</div>

<!-- Invoice document -->
<div class="invoice-box">

  <div class="inv-header">
    <div>
      <h1 class="inv-brand">AutoCare</h1>
      <p>Vehicle Service Center</p>
      <p>Colombo, Sri Lanka</p>
      <p>autocare@gmail.com</p>
    </div>
    <div class="inv-meta">
      <h2>INVOICE</h2>
      <p><strong>Invoice #:</strong> <?= str_pad($inv['id'], 5, '0', STR_PAD_LEFT) ?></p>
      <p><strong>Date:</strong> <?= date('d M Y', strtotime($inv['created_at'])) ?></p>
      <p>
        <strong>Status:</strong>
        <span class="badge badge-<?= $inv['payment_status'] === 'paid' ? 'completed' : 'pending' ?>">
          <?= strtoupper($inv['payment_status']) ?>
        </span>
      </p>
      <?php if ($inv['paid_at']): ?>
        <p><strong>Paid on:</strong> <?= date('d M Y', strtotime($inv['paid_at'])) ?></p>
      <?php endif; ?>
    </div>
  </div>

  <div class="inv-parties">
    <div>
      <h4>Bill To</h4>
      <p><strong><?= htmlspecialchars($inv['customer_name']) ?></strong></p>
      <p><?= htmlspecialchars($inv['customer_phone']) ?></p>
      <?php if ($inv['customer_email']): ?>
        <p><?= htmlspecialchars($inv['customer_email']) ?></p>
      <?php endif; ?>
      <?php if ($inv['customer_address']): ?>
        <p><?= htmlspecialchars($inv['customer_address']) ?></p>
      <?php endif; ?>
    </div>
    <div>
      <h4>Vehicle Details</h4>
      <p><strong><?= htmlspecialchars($inv['plate_no']) ?></strong></p>
      <p><?= htmlspecialchars($inv['make'] . ' ' . $inv['model'] . ' (' . $inv['year'] . ')') ?></p>
      <p>Mechanic: <?= htmlspecialchars($inv['mechanic_name']) ?></p>
      <p>Advisor: <?= htmlspecialchars($inv['advisor_name']) ?></p>
    </div>
  </div>

  <p class="inv-problem">
    <strong>Work Performed:</strong> <?= nl2br(htmlspecialchars($inv['problem_desc'])) ?>
  </p>

  <!-- Parts table -->
  <table class="inv-table">
    <thead>
      <tr>
        <th>#</th>
        <th>Description</th>
        <th>Part No</th>
        <th>Qty</th>
        <th>Unit Price</th>
        <th>Total</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($parts_list)): ?>
        <tr><td colspan="6" class="empty">No parts used.</td></tr>
      <?php else: ?>
        <?php foreach ($parts_list as $i => $p): ?>
          <tr>
            <td><?= $i + 1 ?></td>
            <td><?= htmlspecialchars($p['part_name']) ?></td>
            <td><?= htmlspecialchars($p['part_no']) ?></td>
            <td><?= $p['qty_used'] ?></td>
            <td>LKR <?= number_format($p['price_at_time'], 2) ?></td>
            <td>LKR <?= number_format($p['qty_used'] * $p['price_at_time'], 2) ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>

  <!-- Totals -->
  <div class="inv-totals">
    <div class="inv-total-row">
      <span>Parts Total</span>
      <span>LKR <?= number_format($inv['parts_cost'], 2) ?></span>
    </div>
    <div class="inv-total-row">
      <span>Labour Cost</span>
      <span>LKR <?= number_format($inv['labour_cost'], 2) ?></span>
    </div>
    <div class="inv-total-row inv-grand-total">
      <span>TOTAL AMOUNT</span>
      <span>LKR <?= number_format($inv['total_amount'], 2) ?></span>
    </div>
  </div>

  <p class="inv-footer">Thank you for choosing AutoCare. Drive safe!</p>
</div>

</body>
</html>