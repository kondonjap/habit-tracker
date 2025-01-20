<?php
require_once __DIR__ . '/db.php'; // データベース接続
require_once __DIR__ . '/vendor/autoload.php'; // ComposerでインストールしたLINE Bot SDK

use LINE\LINEBot;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;

// LINE Bot設定
$channelAccessToken = '2006801028';
$channelSecret = '2f830fdadf1c5abb9d9b72950eb071ed';

$httpClient = new CurlHTTPClient($channelAccessToken);
$bot = new LINEBot($httpClient, ['channelSecret' => $channelSecret]);

// Webhookの受信
$content = file_get_contents('php://input');
$events = json_decode($content, true);

if (!empty($events['events'])) {
    foreach ($events['events'] as $event) {
        if ($event['type'] == 'message' && $event['message']['type'] == 'text') {
            $userId = $event['source']['userId'];
            $userMessage = $event['message']['text'];

            if (strpos($userMessage, '登録:') === 0) {
                // 習慣登録
                $habitName = substr($userMessage, 4);
                $query = "INSERT INTO habits (user_id, habit_name) VALUES (:user_id, :habit_name)";
                $stmt = $pdo->prepare($query);
                $stmt->execute([
                    ':user_id' => $userId,
                    ':habit_name' => $habitName
                ]);
                $replyMessage = "習慣「{$habitName}」を登録しました！";
            } else {
                $replyMessage = "習慣を登録するには『登録:習慣名』と送信してください。";
            }

            // ユーザーに返信
            $replyToken = $event['replyToken'];
            $textMessageBuilder = new TextMessageBuilder($replyMessage);
            $bot->replyMessage($replyToken, $textMessageBuilder);
        }
    }
}
?>
