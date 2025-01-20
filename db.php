<?php
// データベース接続設定
$host = '127.0.0.1'; // ローカルホスト
$db = 'habit_tracker'; // データベース名
$user = 'root'; // MySQLユーザー名（デフォルトはroot）
$pass = ''; // MySQLパスワード（通常は空）

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "データベース接続エラー: " . $e->getMessage();
    exit;
}
?>
