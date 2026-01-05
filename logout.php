<?php
// logout.php
require_once 'config.php';

$auth = new Auth();
$auth->logout();

header('Location: ' . BASE_URL . '/login.php');
exit;
?>