<?php
define('FNJ_ROOT', '../');
require __DIR__ . '/../inc/app.php';
$_SESSION = [];
session_destroy();
header('Location: login.php');
