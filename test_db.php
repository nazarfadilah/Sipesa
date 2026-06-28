<?php
$hosts = ['127.0.0.1', 'localhost'];
$port = 3306;
$db = 'pwan5124_sipesapelindo';
$user = 'pwan5124_YogaArya';
$pass = '*Malihah08';

foreach ($hosts as $host) {
    try {
        echo "Trying $host...\n";
        $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        echo "Connected to $host successfully!\n";
        $stmt = $pdo->query("SELECT * FROM jenis");
        $rows = $stmt->fetchAll();
        print_r($rows);
        break;
    } catch (Exception $e) {
        echo "Failed for $host: " . $e->getMessage() . "\n";
    }
}
