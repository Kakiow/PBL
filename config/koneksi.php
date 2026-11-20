<?php

$config = [
    'host'     => getenv('DB_HOST') ?: '127.0.0.1',
    'port'     => getenv('DB_PORT') ?: '5432',
    'database' => getenv('DB_NAME') ?: 'simkas',
    'username' => getenv('DB_USER') ?: 'simkas',
    'password' => getenv('DB_PASS') ?: 'SimkasLokal2026!',
    'sslmode'  => getenv('DB_SSLMODE') ?: 'disable',
];

$dsn = "pgsql:host={$config['host']};port={$config['port']};dbname={$config['database']};sslmode={$config['sslmode']}";

try {
    $pdo = new PDO($dsn, $config['username'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Koneksi database gagal. Silakan periksa file config/koneksi.php.');
}