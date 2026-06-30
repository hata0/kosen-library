<?php
// セッションの開始
session_start();

// DB接続の代わりに、本のダミーデータを用意
$dummy_books = [
    1 => 'AI入門',
    2 => '面白かった小説',
    3 => '実践ネットワーク工学'
];

// URLパラメータから対象の本のIDを取得
$book_id = isset($_GET['book_id']) ? (int)$_GET['book_id'] : 0;
$target_book_title = isset($dummy_books[$book_id]) ? $dummy_books[$book_id] : '選択された本が見つかりません';

$success_message = '';
$error_message = '';

// POSTリクエスト（投稿ボタンが押されたとき）の処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';

    if ($book_id === 0 || !isset($dummy_books[$book_id])) {
        $error_message = '投稿対象の本が正しく選択されていません。';
    } elseif ($title === '') {
        $error_message = 'タイトルを入力してください。';
    } elseif ($content === '') {
        $error_message = '紹介文を入力してください。';
    } else {
        // 実際のシステムでは、ここで introductions テーブルに INSERT します
        // ※注意：テーブルに title カラムを追加する必要があります
        // INSERT INTO introductions (user_id, book_id, title, content) VALUES (...)
        
        $success_message = '紹介文を投稿しました！';
    }
}

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
    <title>図書室アプリ - 紹介文の投稿</title>
    <style>
        /* --- デザインシステム（Material Design 3 ベース） --- */
        :root {
            --md-sys-color-primary: #1a73e8;
            --md-sys-color-primary-hover: #1557b0;
            --md-sys-color-background: #ffffff;
            --md-sys-color-surface: #ffffff;
            --md-sys-color-surface-variant: #f8f9fa;
            --md-sys-color-on-surface: #1f1f1f;
            --md-sys-color-on-surface-variant: #5f6368;
            --md-sys-color-outline: #e0e0e0;
            --max-content-width: 760px;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; -webkit-tap-highlight-color: transparent; }
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
            margin: 0 auto; padding: 24px 20px; display: flex; flex-direction: column; gap: 20px;
        }
        .page-header { display: flex; align-items: center; gap: 12px; }
        .back-link { text-decoration: none; color: var(--md-sys-color-on-surface-variant); font-size: 14px; transition: color 0.2s; }
        .back-link:hover { color: var(--md-sys-color-primary); text-decoration: underline; }
        .page-title { font-size: 22px; font-weight: 700; margin-bottom: 8px; }

        /* アラート */
        .alert { padding: 16px; border-radius: 8px; font-size: 14px; font-weight: 500; margin-bottom: 8px; }
        .alert-success { background-color: #e6f4ea; color: #137333; }
        .alert-error { background-color: #fdeded; color: #d32f2f; }

        /* 投稿フォームのカード */
        .post-card {
            background-color: var(--md-sys-color-surface);
            border: 1px solid var(--md-sys-color-outline);
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }

        .form-group { margin-bottom: 24px; }
        .form-label {
            display: block; font-size: 14px; font-weight: 700;
            color: var(--md-sys-color-on-surface-variant); margin-bottom: 8px;
        }
        
        .book-target {
            font-size: 16px; font-weight: 700; color: var(--md-sys-color-on-surface);
            padding: 12px 16px; background-color: var(--md-sys-color-surface-variant);
            border-radius: 8px; display: inline-block; width: 100%;
        }

        /* 共通入力フィールドスタイル */
        .input-field {
            width: 100%; padding: 14px 16px; font-size: 15px;
            color: var(--md-sys-color-on-surface); background-color: #ffffff;
            border: 1px solid var(--md-sys-color-outline); border-radius: 8px;
            outline: none; transition: border-color 0.2s, box-shadow 0.2s;
            font-family: inherit;
        }
        .input-field:focus {
            border-color: var(--md-sys-color-primary);
            box-shadow: 0 0 0 3px rgba(26, 115, 232, 0.15);
        }
        .input-field::placeholder { color: #9aa0a6; }

        /* テキストエリア特有のスタイル */
        .input-textarea {
            min-height: 240px; line-height: 1.6; resize: vertical;
        }

        /* ボタン */
        .form-actions { display: flex; gap: 12px; align-items: center; margin-top: 12px; }
        .btn {
            display: inline-flex; align-items: center; justify-content: center;
            padding: 10px 24px; font-size: 14px; font-weight: 600; border-radius: 9999px;
            text-decoration: none; cursor: pointer; transition: background-color 0.2s; border: 1px solid transparent;
        }
        .btn-primary { background-color: var(--md-sys-color-primary); color: #ffffff; }
        .btn-primary:hover { background-color: var(--md-sys-color-primary-hover); }

        @media (min-width: 768px) {
            .header-inner { padding: 24px 24px 12px 24px; display: flex; justify-content: space-between; align-items: center; }
            .app-title { margin-bottom: 0; font-size: 24px; }
            .nav-item.active::after { bottom: -13px; }
            .main-content { padding: 40px 24px; gap: 24px; }
            .post-card { padding: 32px; }
        }
    </style>
</head>
<body>
    <header class="app-header">
        <div class="header-inner">
            <div class="app-title">図書室アプリ</div>
            <nav class="app-nav">
                <a href="index.php" class="nav-item">ホーム</a>
                <a href="mypage/" class="nav-item active">マイページ</a>
            </nav>
        </div>
    </header>

    <main class="main-content">
        <div class="page-header">
            <a href="javascript:history.back()" class="back-link">← 前のページへ戻る</a>
        </div>
        
        <h1 class="page-title">紹介記事投稿</h1>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?= h($success_message) ?></div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-error"><?= h($error_message) ?></div>
        <?php endif; ?>

        <div class="post-card">
            <form action="post_intro.php?book_id=<?= h($book_id) ?>" method="POST">
                
                <div class="form-group">
                    <span class="form-label">対象の書籍</span>
                    <div class="book-target">
                        <?= h($target_book_title) ?>
                    </div>
                </div>

                <div class="form-group">
                    <label for="title" class="form-label">タイトル</label>
                    <input 
                        type="text" 
                        id="title" 
                        name="title" 
                        class="input-field" 
                        placeholder="記事のタイトルを入力してください（例：初心者でも分かりやすいAI入門書！）"
                        required
                        value="<?= $success_message ? '' : h(isset($_POST['title']) ? $_POST['title'] : '') ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="content" class="form-label">本文</label>
                    <textarea 
                        id="content" 
                        name="content" 
                        class="input-field input-textarea" 
                        placeholder="この本の面白かったところ、おすすめのポイントなどを自由に書いてください..."
                        required
                    ><?= $success_message ? '' : h(isset($_POST['content']) ? $_POST['content'] : '') ?></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" <?= ($book_id === 0 || !isset($dummy_books[$book_id])) ? 'disabled style="opacity:0.5; cursor:not-allowed;"' : '' ?>>投稿する</button>
                </div>
            </form>
        </div>
    </main>
</body>
</html>