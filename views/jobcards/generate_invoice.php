<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';
require_login();

$job_id = (int)($_GET['job_id'] ?? 0);

// Only generate for completed jobs
$stmt = $pdo->prepare("SELECT * FROM job_cards WHERE id = ? AND status = 'completed'");
$stmt->execute([$job_id]);
$job = $stmt->fetch();
if (!$job) { die('Invoice can only be generated for completed jobs.'); }

// Check if invoice already exists
$chk = $pdo->prepare("SELECT id FROM invoices WHERE job_card_id = ?");
$chk->execute([$job_id]);
if ($chk->fetch()) {
    header("Location: invoice.php?job_id=$job_id");
    exit;
}

// Calculate parts total
$pts = $pdo->prepare("
    SELECT SUM(qty_used * price_at_time) as total
    FROM job_parts WHERE job_card_id = ?
");
$pts->execute([$job_id]);
$parts_cost  = (float)($pts->fetchColumn() ?? 0);
$labour_cost = (float)$job['labour_cost'];
$total       = $parts_cost + $labour_cost;

// Insert invoice
$ins = $pdo->prepare("
    INSERT INTO invoices (job_card_id, parts_cost, labour_cost, total_amount)
    VALUES (?, ?, ?, ?)
");
$ins->execute([$job_id, $parts_cost, $labour_cost, $total]);

$_SESSION['flash'] = ['type'=>'success','message'=>'Invoice generated successfully.'];
header("Location: invoice.php?job_id=$job_id");
exit;
?>