<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/db.php'; // データベース接続設定ファイル
require_once __DIR__ . '/config.php'; // 定数ファイル
require_once __DIR__ . '/functions/habit_functions.php'; // 習慣関連関数

use LINE\LINEBot;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use LINE\LINEBot\QuickReplyBuilder\ButtonBuilder\QuickReplyButtonBuilder;
use LINE\LINEBot\QuickReplyBuilder\QuickReplyMessageBuilder;
use LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder;





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
                    // Quick Replyのボタンを作成
                    $quickReplyButtons = [];
                    foreach ($habits as $habit) {error_log("Postback Data: " . print_r($habit, true));

                        $quickReplyButtons[] = new QuickReplyButtonBuilder(
                            new PostbackTemplateActionBuilder($habit['habit_name'], "action=done&id=" . $habit['id'])
                        );
                    }

                    // Quick Replyをメッセージに添付
                    $quickReply = new QuickReplyMessageBuilder($quickReplyButtons);
                    $textMessage = new TextMessageBuilder("以下の習慣を選んでください：", $quickReply);
                    $bot->replyMessage($replyToken, $textMessage);
                } else {
                    $replyMessage = "登録された習慣がありません。";
                    $textMessageBuilder = new TextMessageBuilder($replyMessage);
                    $bot->replyMessage($replyToken, $textMessageBuilder);
                }
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
        } elseif ($event['type'] === 'postback') {
            // Postbackイベントの処理
            parse_str($event['postback']['data'], $data);
            if ($data['action'] === 'done') {
                $habitId = $data['id']; // 修正箇所
                error_log("Raw Postback Data: " . $event['postback']['data']);
                error_log("Postback Data: " . print_r($data, true));
        
                // データベースに「完了」ステータスを登録
                $stmt = $pdo->prepare("UPDATE habits SET done_at = NOW() WHERE id = :id");
                $stmt->execute([':id' => $habitId]);
                $replyMessage = "習慣が完了として登録されました！";
        
                // replyTokenを使ってメッセージを返信
                $replyToken = $event['replyToken'];
                $textMessageBuilder = new TextMessageBuilder($replyMessage);
        
                $response = $bot->replyMessage($replyToken, $textMessageBuilder);
        
                // ログに結果を記録
                if (!$response->isSucceeded()) {
                    error_log('LINE API Error: ' . $response->getRawBody());
                } else {
                    error_log('LINE API Success: ' . $response->getRawBody());
                }
            }
        }
    }
}
?>
