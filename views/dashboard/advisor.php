<?php
// views/dashboard/advisor.php
require_once '../../config/db.php';
require_once '../../includes/auth.php';
require_role('advisor'); // Only advisors can reach this page
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Advisor Dashboard — AutoCare</title>
  <link rel="stylesheet" href="/autocare/public/css/style.css">
</head>
<body>
  <div class="dashboard">
    <header>
      <h2>Advisor Dashboard</h2>
      <span>Welcome, <?= htmlspecialchars($_SESSION['name']) ?></span>
      <a href="/autocare/includes/logout.php">Logout</a>
    </header>
    <main>
      <p>Advisor dashboard coming soon. Role: <strong>advisor</strong></p>
    </main>
  </div>
</body>
</html>