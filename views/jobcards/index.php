<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';
require_login();

// Save filter in cookie so it persists
if (isset($_GET['status'])) {
    setcookie('jc_status_filter', $_GET['status'], time() + (7 * 24 * 3600), '/');
    $filter = $_GET['status'];
} else {
    $filter = $_COOKIE['jc_status_filter'] ?? 'all';
}

// Build query
$params = [];
$where  = 'WHERE 1=1';

if ($filter !== 'all') {
    $where   .= ' AND j.status = ?';
    $params[] = $filter;
}

// Customers only see their own vehicles' job cards
if (current_role() === 'customer') {
    $stmt2 = $pdo->prepare("
        SELECT c.id FROM customers c
        JOIN users u ON u.email = c.email
        WHERE u.id = ?
    ");
    $stmt2->execute([$_SESSION['user_id']]);
    $customer_row = $stmt2->fetch();

    if ($customer_row) {
        $where   .= ' AND c.id = ?';
        $params[] = $customer_row['id'];
    } else {
        // Logged in as customer but no matching customer record
        $where .= ' AND 1=0'; // returns nothing
    }
}

$sql = "
    SELECT j.*,
           v.plate_no, v.make, v.model,
           c.name   AS customer_name,
           m.name   AS mechanic_name,
           u.name   AS advisor_name
    FROM job_cards j
    JOIN vehicles  v ON v.id = j.vehicle_id
    JOIN customers c ON c.id = v.customer_id
    JOIN mechanics m ON m.id = j.mechanic_id
    JOIN users     u ON u.id = j.advisor_id
    $where
    ORDER BY j.created_at DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$jobs = $stmt->fetchAll();

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$statuses = ['all','pending','in_progress','completed','cancelled'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Job Cards — AutoCare</title>
  <link rel="stylesheet" href="/autocare/public/css/style.css">
</head>
<body>
<div class="container">
  <div class="page-header">
    <h2>Job Cards</h2>
    <?php if (current_role() !== 'customer'): ?>
      <a href="create.php" class="btn btn-primary">+ New Job Card</a>
    <?php endif; ?>
  </div>

  <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?>">
      <?= htmlspecialchars($flash['message']) ?>
    </div>
  <?php endif; ?>

  <!-- Status filter tabs -->
  <div class="filter-tabs">
    <?php foreach ($statuses as $s): ?>
      <a href="?status=<?= $s ?>"
         class="tab <?= $filter === $s ? 'active':'' ?>">
        <?= ucfirst(str_replace('_',' ',$s)) ?>
      </a>
    <?php endforeach; ?>
  </div>

  <table class="data-table">
    <thead>
      <tr>
        <th>#</th>
        <th>Vehicle</th>
        <th>Customer</th>
        <th>Mechanic</th>
        <th>Problem</th>
        <th>Status</th>
        <th>Created</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($jobs)): ?>
        <tr><td colspan="8" class="empty">No job cards found.</td></tr>
      <?php else: ?>
        <?php foreach ($jobs as $i => $j): ?>
          <tr>
            <td><?= $i + 1 ?></td>
            <td><?= htmlspecialchars($j['plate_no'] . ' ' . $j['make']) ?></td>
            <td><?= htmlspecialchars($j['customer_name']) ?></td>
            <td><?= htmlspecialchars($j['mechanic_name']) ?></td>
            <td><?= htmlspecialchars(substr($j['problem_desc'], 0, 40)) ?>...</td>
            <td><span class="badge badge-<?= $j['status'] ?>"><?= ucfirst(str_replace('_',' ',$j['status'])) ?></span></td>
            <td><?= date('d M Y', strtotime($j['created_at'])) ?></td>
            <td class="actions">
              <a href="view.php?id=<?= $j['id'] ?>">View</a>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>
</body>
</html>