<?php

$config = [
    'host' => 'aws-0-ap-south-1.pooler.supabase.com',
    'port' => '5432',
    'database' => 'postgres',
    'username' => 'postgres.wnkfkdemomeefciksgbj',
    'password' => 'SIMKAS123##'
];

$dsn = "pgsql:host={$config['host']};port={$config['port']};dbname={$config['database']};sslmode=require";

try {
    $pdo = new PDO($dsn, $config['username'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Koneksi database gagal. Silakan periksa file config/koneksi.php.');
}