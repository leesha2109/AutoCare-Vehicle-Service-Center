<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';
require_login();

$id         = (int)($_GET['id'] ?? 0);
$new_status = $_GET['status'] ?? '';
$allowed    = ['pending','in_progress','completed','cancelled'];

if (!$id || !in_array($new_status, $allowed)) {
    header('Location: index.php');
    exit;
}

// Fetch current job
$stmt = $pdo->prepare("SELECT * FROM job_cards WHERE id = ?");
$stmt->execute([$id]);
$job = $stmt->fetch();
if (!$job) { http_response_code(404); die('Not found.'); }

$pdo->beginTransaction();
try {
    $now = date('Y-m-d H:i:s');

    // Set timestamps based on transition
    if ($new_status === 'in_progress') {
        $upd = $pdo->prepare("UPDATE job_cards SET status=?, start_at=? WHERE id=?");
        $upd->execute([$new_status, $now, $id]);
    } elseif ($new_status === 'completed') {
        $upd = $pdo->prepare("UPDATE job_cards SET status=?, end_at=? WHERE id=?");
        $upd->execute([$new_status, $now, $id]);
        // Free up mechanic
        $free = $pdo->prepare("UPDATE mechanics SET status='available' WHERE id=?");
        $free->execute([$job['mechanic_id']]);
    } elseif ($new_status === 'cancelled') {
        $upd = $pdo->prepare("UPDATE job_cards SET status=? WHERE id=?");
        $upd->execute([$new_status, $id]);
        // Free up mechanic
        $free = $pdo->prepare("UPDATE mechanics SET status='available' WHERE id=?");
        $free->execute([$job['mechanic_id']]);
    }

    $pdo->commit();
    $_SESSION['flash'] = ['type'=>'success','message'=>'Job status updated.'];
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['flash'] = ['type'=>'error','message'=>'Update failed.'];
}

header("Location: view.php?id=$id");
exit;
?>