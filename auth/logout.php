<?php
require_once __DIR__ . '/../includes/sesi.php';
mulai_sesi();
session_unset();
session_destroy();
header('Location: ../index.php');
exit;
