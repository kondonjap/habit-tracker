<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/db.php'; // データベース接続設定ファイル

use LINE\LINEBot;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;

// LINE Bot設定
$channelAccessToken = 'GUc0oWfaWfAhEDThgFI10VfsH/Cm319YTHm0rt2oQoDx4WEJGAlh4wnE0c1M2YsY4X8O748pHK1VGyOfoMzFRWvsIV/WF8fS6oVtWNSpLVjWd3+LU3OfgUhD3vmqcgQm9n6e/bYazYj0qkT6YGhkVAdB04t89/1O/w1cDnyilFU=';
$channelSecret = '45507695ab27b0c71178e50a4809c92e';

// LINE Botクライアントの初期化
$httpClient = new CurlHTTPClient($channelAccessToken);
$bot = new LINEBot($httpClient, ['channelSecret' => $channelSecret]);

// Webhookリクエストの処理
$content = file_get_contents('php://input');
$events = json_decode($content, true);

// Webhookデータをログに出力
error_log('Webhook Request: ' . $content);

if (!empty($events['events'])) {
    foreach ($events['events'] as $event) {
        // ユーザーからのテキストメッセージを処理
        if ($event['type'] === 'message' && $event['message']['type'] === 'text') {
            $userId = $event['source']['userId'];
            $replyToken = $event['replyToken'];
            $userMessage = $event['message']['text'];
            if (strpos($userMessage, '登録:') === 0) {
                error_log("Extracted Habit Name: " . $userMessage);
                // マルチバイト対応の文字列切り出し
                $habitName = trim(mb_substr($userMessage, 3, null, 'UTF-8'));
                error_log("Extracted Habit Name: " . $habitName);
                $habitName = mb_convert_encoding($habitName, 'UTF-8'); // UTF-8エンコーディングを確認;

                try {
                    // データベースに習慣を登録
                    $query = "INSERT INTO habits (user_id, habit_name) VALUES (:user_id, :habit_name)";
                    $stmt = $pdo->prepare($query);
                    $stmt->execute([
                        ':user_id' => $userId,
                        ':habit_name' => $habitName
                    ]);
                    error_log("Extracted Habit Name: " . $habitName);
                    $replyMessage = "習慣「{$habitName}」を登録しました！";
                } catch (PDOException $e) {
                    $replyMessage = "習慣の登録に失敗しました。";
                }
            } else {
                $replyMessage = "習慣を登録するには「登録:習慣名」と送信してください。";
            }

            // メッセージの返信
            $textMessageBuilder = new TextMessageBuilder($replyMessage);
            $response = $bot->replyMessage($replyToken, $textMessageBuilder);

            // エラーチェック
            if (!$response->isSucceeded()) {
                error_log('Reply Error: ' . $response->getHTTPStatus() . ' ' . $response->getRawBody());
            }
        }
    }
}
?>
