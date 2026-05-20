<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';
require_role('admin');

// ── Stat cards ──────────────────────────────────────────
$stats = [];

// Total jobs today
$s = $pdo->query("
    SELECT COUNT(*) FROM job_cards
    WHERE DATE(created_at) = CURDATE()
");
$stats['jobs_today'] = $s->fetchColumn();

// Total revenue this month (paid invoices)
$s = $pdo->query("
    SELECT COALESCE(SUM(total_amount), 0)
    FROM invoices
    WHERE payment_status = 'paid'
    AND MONTH(paid_at) = MONTH(CURDATE())
    AND YEAR(paid_at)  = YEAR(CURDATE())
");
$stats['revenue_month'] = (float)$s->fetchColumn();

// Pending jobs
$s = $pdo->query("
    SELECT COUNT(*) FROM job_cards WHERE status = 'pending'
");
$stats['pending_jobs'] = $s->fetchColumn();

// In progress jobs
$s = $pdo->query("
    SELECT COUNT(*) FROM job_cards WHERE status = 'in_progress'
");
$stats['inprogress_jobs'] = $s->fetchColumn();

// Low stock parts
$s = $pdo->query("
    SELECT COUNT(*) FROM parts
    WHERE qty <= low_stock_threshold AND is_active = 1
");
$stats['low_stock'] = $s->fetchColumn();

// Total customers
$s = $pdo->query("
    SELECT COUNT(*) FROM customers WHERE is_active = 1
");
$stats['total_customers'] = $s->fetchColumn();

// ── Chart: jobs per day this week ────────────────────────
$chart = $pdo->query("
    SELECT DATE(created_at) as day, COUNT(*) as total
    FROM job_cards
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(created_at)
    ORDER BY day ASC
");
$chart_rows = $chart->fetchAll();

// Fill all 7 days even if no jobs that day
$chart_labels = [];
$chart_data   = [];
$chart_map    = [];
foreach ($chart_rows as $row) {
    $chart_map[$row['day']] = $row['total'];
}
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $chart_labels[] = date('D d', strtotime($d));
    $chart_data[]   = $chart_map[$d] ?? 0;
}

// ── Chart: revenue last 6 months ────────────────────────
$rev = $pdo->query("
    SELECT DATE_FORMAT(paid_at, '%b %Y') as month,
           SUM(total_amount) as total
    FROM invoices
    WHERE payment_status = 'paid'
    AND paid_at >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
    GROUP BY DATE_FORMAT(paid_at, '%Y-%m')
    ORDER BY MIN(paid_at) ASC
");
$rev_rows    = $rev->fetchAll();
$rev_labels  = array_column($rev_rows, 'month');
$rev_data    = array_map('floatval', array_column($rev_rows, 'total'));

// ── Recent job cards ─────────────────────────────────────
$recent = $pdo->query("
    SELECT j.id, j.status, j.created_at,
           v.plate_no, v.make,
           c.name AS customer_name,
           m.name AS mechanic_name
    FROM job_cards j
    JOIN vehicles  v ON v.id = j.vehicle_id
    JOIN customers c ON c.id = v.customer_id
    JOIN mechanics m ON m.id = j.mechanic_id
    ORDER BY j.created_at DESC
    LIMIT 8
")->fetchAll();

// ── Low stock parts alert ────────────────────────────────
$low_parts = $pdo->query("
    SELECT * FROM parts
    WHERE qty <= low_stock_threshold AND is_active = 1
    ORDER BY qty ASC
")->fetchAll();

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard — AutoCare</title>
  <link rel="stylesheet" href="/autocare/public/css/style.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="container">

  <!-- Header -->
  <div class="page-header">
    <h2>Dashboard</h2>
    <div style="display:flex;gap:8px;align-items:center">
      <span style="font-size:13px;color:var(--text-secondary)">
        Welcome, <?= htmlspecialchars($_SESSION['name']) ?>
      </span>
      <a href="/autocare/includes/logout.php" class="btn btn-secondary">Logout</a>
    </div>
  </div>

  <!-- Nav links -->
  <nav class="dash-nav">
    <a href="/autocare/views/customers/index.php">Customers</a>
    <a href="/autocare/views/mechanics/index.php">Mechanics</a>
    <a href="/autocare/views/parts/index.php">Parts</a>
    <a href="/autocare/views/jobcards/index.php">Job Cards</a>
    <a href="/autocare/views/users/create.php">Add Staff</a>
  </nav>

  <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?>">
      <?= htmlspecialchars($flash['message']) ?>
    </div>
  <?php endif; ?>

  <!-- Stat cards -->
  <div class="stat-grid">
    <div class="stat-card stat-blue">
      <p class="stat-label">Jobs Today</p>
      <p class="stat-value"><?= $stats['jobs_today'] ?></p>
    </div>
    <div class="stat-card stat-green">
      <p class="stat-label">Revenue This Month</p>
      <p class="stat-value">LKR <?= number_format($stats['revenue_month'], 0) ?></p>
    </div>
    <div class="stat-card stat-amber">
      <p class="stat-label">Pending Jobs</p>
      <p class="stat-value"><?= $stats['pending_jobs'] ?></p>
    </div>
    <div class="stat-card stat-teal">
      <p class="stat-label">In Progress</p>
      <p class="stat-value"><?= $stats['inprogress_jobs'] ?></p>
    </div>
    <div class="stat-card <?= $stats['low_stock'] > 0 ? 'stat-red' : 'stat-gray' ?>">
      <p class="stat-label">Low Stock Parts</p>
      <p class="stat-value"><?= $stats['low_stock'] ?></p>
    </div>
    <div class="stat-card stat-gray">
      <p class="stat-label">Total Customers</p>
      <p class="stat-value"><?= $stats['total_customers'] ?></p>
    </div>
  </div>

  <!-- Charts row -->
  <div class="chart-grid">
    <div class="chart-card">
      <h3>Jobs This Week</h3>
      <canvas id="jobsChart" height="120"></canvas>
    </div>
    <div class="chart-card">
      <h3>Revenue (Last 6 Months)</h3>
      <canvas id="revenueChart" height="120"></canvas>
    </div>
  </div>

  <!-- Recent jobs + low stock side by side -->
  <div class="two-col">

    <div>
      <h3 class="section-title">Recent Job Cards</h3>
      <table class="data-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Vehicle</th>
            <th>Customer</th>
            <th>Status</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recent as $j): ?>
            <tr>
              <td>
                <a href="/autocare/views/jobcards/view.php?id=<?= $j['id'] ?>">
                  #<?= $j['id'] ?>
                </a>
              </td>
              <td><?= htmlspecialchars($j['plate_no'] . ' ' . $j['make']) ?></td>
              <td><?= htmlspecialchars($j['customer_name']) ?></td>
              <td>
                <span class="badge badge-<?= $j['status'] ?>">
                  <?= ucfirst(str_replace('_', ' ', $j['status'])) ?>
                </span>
              </td>
              <td><?= date('d M', strtotime($j['created_at'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php if (!empty($low_parts)): ?>
    <div>
      <h3 class="section-title" style="color:#c0392b">
        ⚠ Low Stock Alert
      </h3>
      <table class="data-table">
        <thead>
          <tr>
            <th>Part</th>
            <th>In Stock</th>
            <th>Threshold</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($low_parts as $p): ?>
            <tr class="row-danger">
              <td><?= htmlspecialchars($p['name']) ?></td>
              <td><strong><?= $p['qty'] ?></strong></td>
              <td><?= $p['low_stock_threshold'] ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <a href="/autocare/views/parts/index.php" class="btn btn-secondary"
         style="margin-top:8px;display:inline-block">
        Manage Inventory →
      </a>
    </div>
    <?php endif; ?>

  </div>
</div>

<script>
// Jobs this week chart
new Chart(document.getElementById('jobsChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($chart_labels) ?>,
    datasets: [{
      label: 'Jobs',
      data:  <?= json_encode($chart_data) ?>,
      backgroundColor: 'rgba(56, 139, 219, 0.7)',
      borderRadius: 4,
    }]
  },
  options: {
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
  }
});

// Revenue chart
new Chart(document.getElementById('revenueChart'), {
  type: 'line',
  data: {
    labels: <?= json_encode($rev_labels) ?>,
    datasets: [{
      label: 'Revenue (LKR)',
      data:  <?= json_encode($rev_data) ?>,
      borderColor: 'rgba(39, 174, 96, 0.9)',
      backgroundColor: 'rgba(39, 174, 96, 0.1)',
      borderWidth: 2,
      fill: true,
      tension: 0.3,
    }]
  },
  options: {
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true } }
  }
});
</script>
</body>
</html>