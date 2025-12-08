<?php
// Auth check sudah di handle oleh config.php
// Tidak perlu duplicate function isAdmin()

if (!isAdmin()) {
    header('Location: /admin/login.php');
    exit;
}
?>
