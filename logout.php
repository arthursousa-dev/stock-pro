<?php
require_once __DIR__ . '/bootstrap_sessao.php';
$_SESSION = [];
session_destroy();
header('Location: login.php');
exit;
?>