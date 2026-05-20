<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';
require_role('admin');

$id   = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM mechanics WHERE id = ? AND is_active = 1");
$stmt->execute([$id]);
$mechanic = $stmt->fetch();

if ($mechanic) {
    $new_status = $mechanic['status'] === 'off' ? 'available' : 'off';
    $upd = $pdo->prepare("UPDATE mechanics SET status = ? WHERE id = ?");
    $upd->execute([$new_status, $id]);
    $_SESSION['flash'] = ['type'=>'success','message'=>'Mechanic status updated.'];
}
header('Location: index.php');
exit;
?>