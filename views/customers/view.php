<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ? AND is_active = 1");
$stmt->execute([$id]);
$customer = $stmt->fetch();
if (!$customer) {
    http_response_code(404);
    die('Customer not found.');
}

// Customers can only view their own profile
if (current_role() === 'customer') {
    $own = $pdo->prepare("SELECT c.id FROM customers c JOIN users u ON u.email = c.email WHERE u.id = ?");
    $own->execute([$_SESSION['user_id']]);
    $customer_row = $own->fetch();
    if (!$customer_row || $customer_row['id'] !== (int)$customer['id']) {
        http_response_code(403);
        die('Access denied.');
    }
}

$vehicles_stmt = $pdo->prepare("SELECT * FROM vehicles WHERE customer_id = ? AND is_active = 1 ORDER BY created_at DESC");
$vehicles_stmt->execute([$customer['id']]);
$vehicles = $vehicles_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Customer Details — <?= htmlspecialchars($customer['name']) ?> — AutoCare</title>
  <link rel="stylesheet" href="/autocare/public/css/style.css">
</head>
<body>
<div class="container">
  <div class="page-header">
    <h2>Customer Details</h2>
    <a href="index.php" class="btn btn-secondary">← Back</a>
  </div>

  <div class="detail-grid">
    <div class="detail-card">
      <h3>Customer Information</h3>
      <p><strong>Name:</strong> <?= htmlspecialchars($customer['name']) ?></p>
      <p><strong>NIC:</strong> <?= htmlspecialchars($customer['nic']) ?></p>
      <p><strong>Phone:</strong> <?= htmlspecialchars($customer['phone']) ?></p>
      <p><strong>Email:</strong> <?= htmlspecialchars($customer['email'] ?? '—') ?></p>
      <p><strong>Address:</strong> <?= nl2br(htmlspecialchars($customer['address'] ?? '—')) ?></p>
    </div>

    <div class="detail-card">
      <h3>Summary</h3>
      <p><strong>Registered:</strong> <?= date('d M Y', strtotime($customer['created_at'])) ?></p>
      <p><strong>Status:</strong> <?= $customer['is_active'] ? 'Active' : 'Inactive' ?></p>
      <p><strong>Vehicles:</strong> <?= count($vehicles) ?></p>
    </div>
  </div>

  <div class="detail-card" style="margin-top: 16px;">
    <h3>Vehicles</h3>
    <?php if (empty($vehicles)): ?>
      <p class="empty">No active vehicles found for this customer.</p>
    <?php else: ?>
      <table class="data-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Plate</th>
            <th>Make & Model</th>
            <th>Year</th>
            <th>Mileage</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($vehicles as $index => $vehicle): ?>
            <tr>
              <td><?= $index + 1 ?></td>
              <td><?= htmlspecialchars($vehicle['plate_no']) ?></td>
              <td><?= htmlspecialchars($vehicle['make'] . ' ' . $vehicle['model']) ?></td>
              <td><?= htmlspecialchars($vehicle['year']) ?></td>
              <td><?= htmlspecialchars(number_format($vehicle['mileage'])) ?> km</td>
              <td class="actions">
                <a href="/autocare/views/vehicles/history.php?vehicle_id=<?= $vehicle['id'] ?>">History</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
