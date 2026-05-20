<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';
require_login();

$id     = (int)($_GET['id']     ?? 0);
$job_id = (int)($_GET['job_id'] ?? 0);

if ($id && $job_id) {
    // Get the job_part record first to restore stock
    $stmt = $pdo->prepare("SELECT * FROM job_parts WHERE id = ?");
    $stmt->execute([$id]);
    $jp = $stmt->fetch();

    if ($jp) {
        $pdo->beginTransaction();
        try {
            // Restore stock
            $rst = $pdo->prepare("UPDATE parts SET qty = qty + ? WHERE id = ?");
            $rst->execute([$jp['qty_used'], $jp['part_id']]);

            // Delete job_part record
            $del = $pdo->prepare("DELETE FROM job_parts WHERE id = ?");
            $del->execute([$id]);

            $pdo->commit();
            $_SESSION['flash'] = ['type'=>'success','message'=>'Part removed and stock restored.'];
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['flash'] = ['type'=>'error','message'=>'Failed to remove part.'];
        }
    }
}
header("Location: add_part.php?job_id=$job_id");
exit;
?>