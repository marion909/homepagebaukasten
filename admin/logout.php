<?php
require_once "../core/init.php";

$auth = new Auth();
$auth->logout();

header('Location: login.php');
exit;
