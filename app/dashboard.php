<?php
session_start();
// Since dashboard is in app/, and config is in app/config/, we just go into config/
require_once 'config/db.php';

// If not logged in, send to login
if (!isset($_SESSION['id'])) {
    // Go into auth/ folder
    header("Location: auth/");
    exit;
}

// If logged in, check role and redirect to specific folder
switch ($_SESSION['role']) {

    case 'farmer':
        header("Location: farmer/index.php");
        break;

    case 'buyer':
        header("Location: buyer/index.php");
        break;

    case 'admin':
        header("Location: admin/index.php");
        break;

    default:
        // If user has no role, destroy session and send back to login
        session_destroy();
        header("Location: auth/");
}
?>