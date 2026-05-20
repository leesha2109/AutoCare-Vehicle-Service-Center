<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';
require_role('admin');

$id = (int)($_GET['id'] ?? 0);
if ($id) {
    $stmt = $pdo->prepare("UPDATE parts SET is_active = 0 WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['flash'] = ['type'=>'success','message'=>'Part removed from inventory.'];
}
header('Location: index.php');
exit;
?>