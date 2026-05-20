<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';
require_login(); // Both admin and advisor can see customers

// Search
$search = trim($_GET['search'] ?? '');

// Pagination
$per_page    = 10;
$page        = max(1, (int)($_GET['page'] ?? 1));
$offset      = ($page - 1) * $per_page;

// Count total for pagination
if ($search) {
    $count_stmt = $pdo->prepare("
        SELECT COUNT(*) FROM customers
        WHERE is_active = 1
        AND (name LIKE ? OR nic LIKE ?)
    ");
    $count_stmt->execute(["%$search%", "%$search%"]);
} else {
    $count_stmt = $pdo->query("SELECT COUNT(*) FROM customers WHERE is_active = 1");
}
$total       = $count_stmt->fetchColumn();
$total_pages = ceil($total / $per_page);

// Fetch customers
if ($search) {
    $stmt = $pdo->prepare("
        SELECT * FROM customers
        WHERE is_active = 1
        AND (name LIKE ? OR nic LIKE ?)
        ORDER BY created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute(["%$search%", "%$search%", $per_page, $offset]);
} else {
    $stmt = $pdo->prepare("
        SELECT * FROM customers
        WHERE is_active = 1
        ORDER BY created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$per_page, $offset]);
}
$customers = $stmt->fetchAll();

// Flash message
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Customers — AutoCare</title>
  <link rel="stylesheet" href="/autocare/public/css/style.css">
</head>
<body>
<div class="container">
  <div class="page-header">
    <h2>Customers</h2>
    <a href="create.php" class="btn btn-primary">+ Add Customer</a>
  </div>

  <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?>">
      <?= htmlspecialchars($flash['message']) ?>
    </div>
  <?php endif; ?>

  <!-- Search form -->
  <form method="GET" class="search-form">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <input type="text" name="search"
           placeholder="Search by name or NIC..."
           value="<?= htmlspecialchars($search) ?>">
    <button type="submit" class="btn">Search</button>
    <?php if ($search): ?>
      <a href="index.php" class="btn btn-secondary">Clear</a>
    <?php endif; ?>
  </form>

  <!-- Customer table -->
  <table class="data-table">
    <thead>
      <tr>
        <th>#</th>
        <th>Name</th>
        <th>NIC</th>
        <th>Phone</th>
        <th>Email</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($customers)): ?>
        <tr><td colspan="6" class="empty">No customers found.</td></tr>
      <?php else: ?>
        <?php foreach ($customers as $i => $c): ?>
          <tr>
            <td><?= $offset + $i + 1 ?></td>
            <td><?= htmlspecialchars($c['name']) ?></td>
            <td><?= htmlspecialchars($c['nic']) ?></td>
            <td><?= htmlspecialchars($c['phone']) ?></td>
            <td><?= htmlspecialchars($c['email'] ?? '—') ?></td>
            <td class="actions">
              <a href="view.php?id=<?= $c['id'] ?>">View</a>
              <a href="edit.php?id=<?= $c['id'] ?>">Edit</a>
              <?php if (current_role() === 'admin'): ?>
                <a href="delete.php?id=<?= $c['id'] ?>"
                   class="danger"
                   onclick="return confirm('Deactivate this customer?')">Delete</a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>

  <!-- Pagination -->
  <?php if ($total_pages > 1): ?>
    <div class="pagination">
      <?php for ($p = 1; $p <= $total_pages; $p++): ?>
        <a href="?page=<?= $p ?>&search=<?= urlencode($search) ?>"
           class="<?= $p === $page ? 'active' : '' ?>">
          <?= $p ?>
        </a>
      <?php endfor; ?>
    </div>
  <?php endif; ?>
</div>
</body>
</html>