<?php
// セッションの開始
session_start();

// データベース接続設定
$db_host = 'localhost';
$db_name = 'library_app'; // sql.txtで定義されたデータベース名
$db_user = 'root';        // ご自身の環境に合わせて変更してください
$db_pass = '';            // ご自身の環境に合わせて変更してください

$history_data = [];
$error_message = '';

try {
    // PDOによるデータベース接続
    $dsn = "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);

    // =========================================================
    // 【認証のシミュレーション】
    // 本来はログイン時に $_SESSION['user_id'] がセットされます。
    // sql.txtの初期データに合わせて、ID:1（テスト太郎）でテストします。
    // =========================================================
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['user_id'] = 1; 
    }
    $current_user_id = $_SESSION['user_id'];

    // ---------------------------------------------------------
    // 取得（SELECT）処理：borrow_records と books を結合して履歴を取得
    // ---------------------------------------------------------
    $sql = "
        SELECT 
            br.book_id,
            b.title,
            br.loan_date,
            br.return_date
        FROM 
            borrow_records br
        JOIN 
            books b ON br.book_id = b.id
        WHERE 
            br.user_id = :user_id
        ORDER BY 
            br.loan_date DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':user_id', $current_user_id, PDO::PARAM_INT);
    $stmt->execute();
    
    // 取得したデータを配列に格納
    $history_data = $stmt->fetchAll();

} catch (PDOException $e) {
    // エラー時の処理
    $error_message = 'データベース接続またはデータ取得エラー: ' . $e->getMessage();
}

// XSS対策用関数
function h($string) {
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>図書室アプリ - 貸出履歴</title>
    <style>
        /* --- デザインシステム --- */
        :root {
            --md-sys-color-primary: #1a73e8;
            --md-sys-color-primary-hover: #1557b0;
            --md-sys-color-background: #ffffff;
            --md-sys-color-surface: #ffffff;
            --md-sys-color-on-surface: #1f1f1f;
            --md-sys-color-on-surface-variant: #5f6368;
            --md-sys-color-outline: #e0e0e0;
            --md-sys-color-outline-variant: #f1f3f4;
            --max-content-width: 760px;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Helvetica Neue', Arial, 'Hiragino Kaku Gothic ProN', 'Hiragino Sans', Meiryo, sans-serif;
            background-color: var(--md-sys-color-background);
            color: var(--md-sys-color-on-surface);
            min-height: 100vh;
            display: flex; flex-direction: column;
        }

        /* ヘッダー */
        .app-header {
            background-color: var(--md-sys-color-surface); border-bottom: 1px solid var(--md-sys-color-outline);
            position: sticky; top: 0; z-index: 10; width: 100%;
        }
        .header-inner { max-width: var(--max-content-width); margin: 0 auto; padding: 16px 20px 8px 20px; }
        .app-title { font-size: 20px; font-weight: 700; margin-bottom: 12px; }
        .app-nav { display: flex; gap: 24px; }
        .nav-item { text-decoration: none; color: var(--md-sys-color-on-surface-variant); font-size: 15px; font-weight: 500; padding: 6px 0; position: relative; }
        .nav-item.active { color: var(--md-sys-color-on-surface); font-weight: 700; }
        .nav-item.active::after { content: ''; position: absolute; bottom: -9px; left: 0; width: 100%; height: 3px; background-color: var(--md-sys-color-primary); border-radius: 3px 3px 0 0; }

        /* メインコンテンツ */
        .main-content {
            flex: 1; width: 100%; max-width: var(--max-content-width);
            margin: 0 auto; padding: 24px 20px; display: flex; flex-direction: column; gap: 24px;
        }
        .page-header { display: flex; align-items: center; gap: 12px; margin-bottom: 8px; }
        .back-link { text-decoration: none; color: var(--md-sys-color-on-surface-variant); font-size: 14px; transition: color 0.2s; }
        .back-link:hover { color: var(--md-sys-color-primary); }
        .page-title { font-size: 22px; font-weight: 700; }

        /* アラート */
        .alert-error {
            padding: 16px; background-color: #fdeded; color: #d32f2f;
            border-radius: 8px; font-size: 14px; font-weight: 500;
        }

        /* テーブル */
        .table-container {
            width: 100%; overflow-x: auto; background-color: var(--md-sys-color-surface);
            border: 1px solid var(--md-sys-color-outline); border-radius: 12px;
        }
        .history-table { width: 100%; border-collapse: collapse; min-width: 600px; text-align: left; }
        .history-table th, .history-table td { padding: 16px; border-bottom: 1px solid var(--md-sys-color-outline-variant); vertical-align: middle; }
        .history-table th { font-size: 13px; color: var(--md-sys-color-on-surface-variant); font-weight: 500; background-color: #fafafa; }
        .history-table tr:last-child td { border-bottom: none; }
        .book-title { font-size: 15px; font-weight: 700; color: var(--md-sys-color-on-surface); text-decoration: none; }
        .book-title:hover { color: var(--md-sys-color-primary); text-decoration: underline; }
        .date-text { font-size: 14px; color: var(--md-sys-color-on-surface-variant); }

        /* ボタン */
        .btn {
            display: inline-flex; align-items: center; justify-content: center;
            padding: 8px 16px; font-size: 13px; font-weight: 600; border-radius: 9999px;
            text-decoration: none; transition: background-color 0.2s;
        }
        .btn-primary { background-color: var(--md-sys-color-primary); color: #ffffff; border: 1px solid transparent; }
        .btn-primary:hover { background-color: var(--md-sys-color-primary-hover); }

        @media (min-width: 768px) {
            .header-inner { padding: 24px 24px 12px 24px; display: flex; justify-content: space-between; align-items: center; }
            .app-title { margin-bottom: 0; font-size: 24px; }
            .nav-item.active::after { bottom: -13px; }
            .main-content { padding: 40px 24px; gap: 32px; }
        }
    </style>
</head>
<body>
    <header class="app-header">
        <div class="header-inner">
            <div class="app-title">図書室アプリ</div>
            <nav class="app-nav">
                <a href="../../index.php" class="nav-item">ホーム</a>
                <a href="../" class="nav-item active">マイページ</a>
            </nav>
        </div>
    </header>

    <main class="main-content">
        <div class="page-header">
            <a href="../" class="back-link">← マイページへ戻る</a>
        </div>
        
        <h1 class="page-title">貸出履歴</h1>

        <?php if ($error_message): ?>
            <div class="alert-error"><?= h($error_message) ?></div>
        <?php elseif (empty($history_data)): ?>
            <div style="text-align: center; padding: 40px; border: 1px dashed #e0e0e0; border-radius: 12px; color: #5f6368;">
                現在、貸出履歴はありません。
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>書籍名</th>
                            <th>貸出日</th>
                            <th>返却日(期限)</th>
                            <th>アクション</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history_data as $row): ?>
                            <tr>
                                <td>
                                    <a href="../../book_detail.php?id=<?= h($row['book_id']) ?>" class="book-title">
                                        <?= h($row['title']) ?>
                                    </a>
                                </td>
                                <td class="date-text">
                                    <?= h(date('Y/m/d', strtotime($row['loan_date']))) ?>
                                </td>
                                <td class="date-text">
                                    <?= !empty($row['return_date']) ? h(date('Y/m/d', strtotime($row['return_date']))) : '<span style="color: #d32f2f; font-weight: bold;">未返却</span>' ?>
                                </td>
                                <td>
                                    <a href="../../post_intro.php?book_id=<?= h($row['book_id']) ?>" class="btn btn-primary">
                                        紹介する
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>