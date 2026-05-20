<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';
require_role('customer');

// Find linked customer record
$stmt = $pdo->prepare("
    SELECT c.* FROM customers c
    JOIN users u ON u.email = c.email
    WHERE u.id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$customer = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Portal — AutoCare</title>
  <link rel="stylesheet" href="/autocare/public/css/style.css">
</head>
<body>
<div class="container">
  <div class="page-header">
    <h2>Welcome, <?= htmlspecialchars($_SESSION['name']) ?></h2>
    <a href="/autocare/includes/logout.php" class="btn btn-secondary">Logout</a>
  </div>

  <?php if ($customer): ?>
    <div class="detail-grid">
      <div class="detail-card">
        <h3>My Profile</h3>
        <p><strong>Name:</strong> <?= htmlspecialchars($customer['name']) ?></p>
        <p><strong>NIC:</strong>  <?= htmlspecialchars($customer['nic']) ?></p>
        <p><strong>Phone:</strong><?= htmlspecialchars($customer['phone']) ?></p>
      </div>
    </div>
    <div style="margin-top:1.5rem">
      <a href="/autocare/views/jobcards/index.php" class="btn btn-primary">
        View My Job Cards & Invoices
      </a>
    </div>
  <?php else: ?>
    <div class="alert alert-error">
      No customer record linked to your account.
      Please contact the service center.
    </div>
  <?php endif; ?>
</div>
</body>
</html>