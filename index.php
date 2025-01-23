<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/db.php'; // データベース接続設定ファイル
require_once __DIR__ . '/config.php'; // 定数ファイル
require_once __DIR__ . '/functions/habit_functions.php'; // 習慣関連関数

use LINE\LINEBot;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;

// LINE Bot設定
$channelAccessToken = LINE_CHANNEL_ACCESS_TOKEN;
$channelSecret = LINE_CHANNEL_SECRET;

// LINE Botクライアントの初期化
$httpClient = new CurlHTTPClient($channelAccessToken);
$bot = new LINEBot($httpClient, ['channelSecret' => $channelSecret]);

// Webhookリクエストの処理
$content = file_get_contents('php://input');
$events = json_decode($content, true);

if (!empty($events['events'])) {
    foreach ($events['events'] as $event) {
        if ($event['type'] === 'message' && $event['message']['type'] === 'text') {
            $userId = $event['source']['userId'];
            $replyToken = $event['replyToken'];
            $userMessage = $event['message']['text'];

            if ($userMessage === '一覧表示') {
                // データベースから習慣一覧を取得
                $habits = getHabitList($pdo, $userId);

                if (!empty($habits)) {
                    // 習慣一覧をテキストで作成
                    $replyMessage = "登録された習慣一覧:\n";
                    foreach ($habits as $habit) {
                        $replyMessage .= "- " . $habit['habit_name'] . "\n";
                    }
                } else {
                    $replyMessage = "登録された習慣がありません。";
                }

                // メッセージの返信
                $textMessageBuilder = new TextMessageBuilder($replyMessage);
                $bot->replyMessage($replyToken, $textMessageBuilder);
            } elseif (strpos($userMessage, '登録:') === 0) {
                // 習慣登録処理
                $habitName = trim(mb_substr($userMessage, 3, null, 'UTF-8'));
                $replyMessage = registerHabit($pdo, $userId, $habitName);
                $textMessageBuilder = new TextMessageBuilder($replyMessage);
                $bot->replyMessage($replyToken, $textMessageBuilder);
            } else {
                // その他のメッセージへの返信
                $replyMessage = "習慣を登録するには「登録:習慣名」と送信してください。";
                $textMessageBuilder = new TextMessageBuilder($replyMessage);
                $bot->replyMessage($replyToken, $textMessageBuilder);
            }
        }
    }
}
?>
