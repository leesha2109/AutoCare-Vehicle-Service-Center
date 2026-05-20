<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';
require_role('admin'); // Only admin can delete

$id = (int)($_GET['id'] ?? 0);
if ($id) {
    $stmt = $pdo->prepare("UPDATE customers SET is_active = 0 WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['flash'] = ['type'=>'success','message'=>'Customer deactivated.'];
}
header('Location: index.php');
exit;
?>