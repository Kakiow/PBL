<?php

$config = [
    'host' => 'localhost',
    'port' => '5432',
    'database' => 'sistem_kas_grup',
    'username' => 'basis_data',
    'password' => 'Ibennn'
];

$dsn = "pgsql:host={$config['host']};port={$config['port']};dbname={$config['database']}";

try {
    $pdo = new PDO($dsn, $config['username'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Koneksi database gagal. Silakan periksa file config/koneksi.php.');
}