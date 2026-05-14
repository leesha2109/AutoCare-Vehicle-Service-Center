<?php
// index.php
require_once 'includes/auth.php';
start_session();

if (!is_logged_in()) {
    header('Location: /autocare/views/auth/login.php');
    exit;
}

// Redirect each role to their own dashboard
switch (current_role()) {
    case 'admin':
        header('Location: /autocare/views/dashboard/admin.php');
        break;
    case 'advisor':
        header('Location: /autocare/views/dashboard/advisor.php');
        break;
    case 'customer':
        header('Location: /autocare/views/dashboard/customer.php');
        break;
    default:
        logout(); 
}
exit;
?>