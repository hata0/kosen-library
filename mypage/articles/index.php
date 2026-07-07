<?php
// セッションの開始
session_start();

// データベース接続設定
$db_host = 'localhost';
$db_name = 'library_app'; // sql.txtで定義されたデータベース名
$db_user = 'root';        // ご自身の環境に合わせて変更してください
$db_pass = '';            // ご自身の環境に合わせて変更してください

$introductions_data = [];
$error_message = '';
$success_message = '';

// 前の画面（投稿画面など）から渡された成功メッセージがあれば受け取って削除
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

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
    // 【認証のシミュレーション】(ID:1 テスト太郎)
    // =========================================================
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['user_id'] = 1; 
    }
    $current_user_id = $_SESSION['user_id'];

    // ---------------------------------------------------------
    // 1. 削除処理（POSTで delete_id が送られてきた場合）
    // ---------------------------------------------------------
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['delete_id'])) {
        $delete_id = (int)$_POST['delete_id'];
        
        // 物理削除(DELETE)ではなく、sql.txtの設計に合わせて論理削除(UPDATE)を行う
        $delete_sql = "UPDATE articles SET is_deleted = 1 WHERE id = :id AND user_id = :user_id";
        $del_stmt = $pdo->prepare($delete_sql);
        $del_stmt->execute([
            ':id' => $delete_id,
            ':user_id' => $current_user_id // 本人の記事しか削除できないように条件追加
        ]);
        
        // 処理後は自分自身にリダイレクトして二重送信を防止
        $_SESSION['success_message'] = '記事を削除しました。';
        header('Location: index.php');
        exit;
    }

    // ---------------------------------------------------------
    // 2. 記事の取得（SELECT）処理
    // ---------------------------------------------------------
    // articlesテーブルから、ログイン中ユーザーが書いた有効な(is_deleted=0)記事を取得
    $sql = "
        SELECT 
            a.id AS intro_id,
            a.book_id,
            a.title AS article_title,
            a.created_at
        FROM 
            articles a
        WHERE 
            a.user_id = :user_id 
            AND a.is_deleted = 0
        ORDER BY 
            a.created_at DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':user_id' => $current_user_id]);
    $introductions_data = $stmt->fetchAll();

} catch (PDOException $e) {
    $error_message = 'データベースエラー: ' . $e->getMessage();
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
    <title>図書室アプリ - 投稿管理</title>
    <style>
        /* --- デザインシステム（Material Design 3 ベース） --- */
        :root {
            --md-sys-color-primary: #1a73e8;
            --md-sys-color-primary-hover: #1557b0;
            --md-sys-color-error: #b3261e;
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
        .app-header { background-color: var(--md-sys-color-surface); border-bottom: 1px solid var(--md-sys-color-outline); position: sticky; top: 0; z-index: 10; width: 100%; }
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
        .alert { padding: 16px; border-radius: 8px; font-size: 14px; font-weight: 500; margin-bottom: 8px; }
        .alert-success { background-color: #e6f4ea; color: #137333; }
        .alert-error { background-color: #fdeded; color: #d32f2f; }

        /* テーブル */
        .table-container {
            width: 100%; overflow-x: auto; background-color: var(--md-sys-color-surface);
            border: 1px solid var(--md-sys-color-outline); border-radius: 12px;
        }
        .post-table { width: 100%; border-collapse: collapse; min-width: 600px; text-align: left; }
        .post-table th, .post-table td { padding: 16px; border-bottom: 1px solid var(--md-sys-color-outline-variant); vertical-align: middle; }
        .post-table th { font-size: 13px; color: var(--md-sys-color-on-surface-variant); font-weight: 500; background-color: #fafafa; }
        .post-table tr:last-child td { border-bottom: none; }
        .article-title { font-size: 15px; font-weight: 700; color: var(--md-sys-color-on-surface); text-decoration: none; }
        .article-title:hover { color: var(--md-sys-color-primary); text-decoration: underline; }
        .date-text { font-size: 14px; color: var(--md-sys-color-on-surface-variant); }

        /* ボタン */
        .action-buttons { display: flex; gap: 8px; }
        .btn {
            display: inline-flex; align-items: center; justify-content: center;
            padding: 8px 16px; font-size: 13px; font-weight: 600; border-radius: 9999px;
            text-decoration: none; cursor: pointer; transition: background-color 0.2s, color 0.2s;
            border: 1px solid transparent;
        }
        .btn-primary { background-color: var(--md-sys-color-primary); color: #ffffff; }
        .btn-primary:hover { background-color: var(--md-sys-color-primary-hover); }
        
        .btn-danger-outline {
            background-color: transparent; color: var(--md-sys-color-error); border-color: var(--md-sys-color-error);
        }
        .btn-danger-outline:hover { background-color: rgba(179, 38, 30, 0.08); }

        /* レスポンシブ */
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
        
        <h1 class="page-title">投稿管理</h1>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?= h($success_message) ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert alert-error"><?= h($error_message) ?></div>
        <?php endif; ?>

        <?php if (empty($introductions_data)): ?>
            <div style="text-align: center; padding: 40px; border: 1px dashed #e0e0e0; border-radius: 12px; color: #5f6368;">
                現在、投稿した紹介文はありません。
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="post-table">
                    <thead>
                        <tr>
                            <th>記事タイトル</th>
                            <th>投稿日</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($introductions_data as $row): ?>
                            <tr>
                                <td>
                                    <a href="../../book_detail.php?id=<?= h($row['book_id']) ?>" class="article-title">
                                        <?= h($row['article_title']) ?>
                                    </a>
                                </td>
                                <td class="date-text">
                                    <?= h(date('Y/m/d H:i', strtotime($row['created_at']))) ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="edit.php?id=<?= h($row['intro_id']) ?>" class="btn btn-primary">編集</a>
                                        
                                        <button type="button" class="btn btn-danger-outline" onclick="confirmDelete(<?= h($row['intro_id']) ?>)">削除</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>

    <form id="delete-form" action="index.php" method="POST" style="display: none;">
        <input type="hidden" name="delete_id" id="delete_id" value="">
    </form>

    <script>
        // 削除ボタンが押されたときの確認ダイアログとPOST送信処理
        function confirmDelete(introId) {
            if (confirm('この記事を本当に削除しますか？\n（※削除後は元に戻せません）')) {
                // 隠しフォームのID入力欄に、消したい記事のIDをセット
                document.getElementById('delete_id').value = introId;
                // フォームを送信
                document.getElementById('delete-form').submit();
            }
        }
    </script>
</body>
</html>