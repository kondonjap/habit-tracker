<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/db.php'; // データベース接続設定ファイル

use LINE\LINEBot;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;

// LINE Bot設定
$channelAccessToken = 'YOUR_CHANNEL_ACCESS_TOKEN';
$channelSecret = 'YOUR_CHANNEL_SECRET';

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
                // ユーザーが「登録:習慣名」と送信した場合
                $habitName = substr($userMessage, 4);

                try {
                    // データベースに習慣を登録
                    $query = "INSERT INTO habits (user_id, habit_name) VALUES (:user_id, :habit_name)";
                    $stmt = $pdo->prepare($query);
                    $stmt->execute([
                        ':user_id' => $userId,
                        ':habit_name' => $habitName
                    ]);

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
