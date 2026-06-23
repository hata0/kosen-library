<?php
// セッションの開始
session_start();

// DB接続の代わりに、ダミーの紹介文投稿データを用意
// （usersテーブル、booksテーブル、introductionsテーブルをJOINした想定のデータ）
$introductions_data = [
    [
        'intro_id'   => 101,
        'book_id'    => 1,
        'book_title' => 'AI入門を読んでみた',
        'created_at' => '2023-05-15 10:30:00'
    ],
    [
        'intro_id'   => 102,
        'book_id'    => 2,
        'book_title' => '面白かった小説紹介',
        'created_at' => '2023-04-20 14:15:00'
    ]
];

// XSS対策用関数
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
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
            --md-sys-color-error: #b3261e; /* 削除ボタン用の赤色 */
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
        
        /* 削除用のボタン（アウトライン・赤） */
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
                                        <?= h($row['book_title']) ?>
                                    </a>
                                </td>
                                <td class="date-text">
                                    <?= h(date('Y/m/d', strtotime($row['created_at']))) ?>
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

    <script>
        // 削除ボタンが押されたときの確認ダイアログ
        function confirmDelete(introId) {
            if (confirm('この紹介文を本当に削除しますか？\n（※削除後は元に戻せません）')) {
                // 実際のシステムでは、ここで削除用のAPIを叩くか、フォームをsubmitします
                alert('ダミー: 紹介文ID ' + introId + ' の削除処理を実行します。');
                // 例: window.location.href = 'delete.php?id=' + introId;
            }
        }
    </script>
</body>
</html>