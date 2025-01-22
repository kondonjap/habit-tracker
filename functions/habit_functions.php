<?php
// functions/habit_functions.php

function getHabitListResponse($pdo, $userId) {
    try {
        $query = "SELECT habit_name FROM habits WHERE user_id = :user_id";
        $stmt = $pdo->prepare($query);
        $stmt->execute([':user_id' => $userId]);
        $habits = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($habits)) {
            $response = "登録された習慣一覧:\n";
            foreach ($habits as $habit) {
                $response .= "- " . $habit['habit_name'] . "\n";
            }
            return $response;
        } else {
            return "登録された習慣がありません。";
        }
    } catch (PDOException $e) {
        error_log('Database Error in getHabitList: ' . $e->getMessage());
        return "習慣一覧の取得に失敗しました。";
    }
}

function registerHabit($pdo, $userId, $habitName) {
    try {
        // 既存チェック
        $query = "SELECT COUNT(*) FROM habits WHERE user_id = :user_id AND habit_name = :habit_name";
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            ':user_id' => $userId,
            ':habit_name' => $habitName
        ]);

        if ($stmt->fetchColumn() > 0) {
            return "習慣「{$habitName}」は既に登録されています！";
        }

        // 登録処理
        $query = "INSERT INTO habits (user_id, habit_name) VALUES (:user_id, :habit_name)";
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            ':user_id' => $userId,
            ':habit_name' => $habitName
        ]);
        return "習慣「{$habitName}」を登録しました！";
    } catch (PDOException $e) {
        error_log("SQL Error: {$query} - Params: user_id={$userId}, habit_name={$habitName}");
        error_log('Database Error in registerHabit: ' . $e->getMessage());
        return "習慣の登録に失敗しました。";
    }
}

function createHabitFlexMessage($habits) {
    $bubbles = [];
    foreach ($habits as $habit) {
        $bubbles[] = [
            'type' => 'bubble',
            'body' => [
                'type' => 'box',
                'layout' => 'vertical',
                'contents' => [
                    [
                        'type' => 'text',
                        'text' => $habit['habit_name'],
                        'weight' => 'bold',
                        'size' => 'lg',
                        'wrap' => true
                    ]
                ]
            ]
        ];
    }

    return [
        'type' => 'flex',
        'altText' => '習慣一覧',
        'contents' => [
            'type' => 'carousel',
            'contents' => $bubbles
        ]
    ];
}
?>
