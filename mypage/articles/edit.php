<?php
require "../../header_session.php";
?>
<?php
// データベース接続設定
$db_host = 'localhost';
$db_name = 'library_app'; // sql.txtで定義されたデータベース名
$db_user = 'root';        // ご自身の環境に合わせて変更してください
$db_pass = '';            // ご自身の環境に合わせて変更してください

$error_message = '';
$article_data = null;

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

    // URLパラメータから編集対象の記事IDを取得
    $edit_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($edit_id <= 0) {
        $error_message = '無効なアクセスです。';
    } else {
        // ---------------------------------------------------------
        // 1. 記事データの取得（表示用）
        // ---------------------------------------------------------
        // 他人の記事を編集できないように user_id も条件に含める
        $sql = "
            SELECT 
                a.id, a.title, a.content, b.title AS book_title 
            FROM 
                articles a
            JOIN 
                books b ON a.book_id = b.id
            WHERE 
                a.id = :id 
                AND a.user_id = :user_id 
                AND a.is_deleted = 0
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id' => $edit_id,
            ':user_id' => $current_user_id
        ]);
        
        $article_data = $stmt->fetch();

        if (!$article_data) {
            $error_message = '指定された記事が見つからないか、編集する権限がありません。';
        }
    }

    // ---------------------------------------------------------
    // 2. 更新処理（POSTでデータが送信されてきた場合）
    // ---------------------------------------------------------
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $article_data) {
        $input_title = isset($_POST['title']) ? trim($_POST['title']) : '';
        $input_content = isset($_POST['content']) ? trim($_POST['content']) : '';

        if ($input_title === '') {
            $error_message = 'タイトルを入力してください。';
        } elseif ($input_content === '') {
            $error_message = '本文を入力してください。';
        } else {
            // DBを更新 (UPDATE)
            // updated_at はMySQL側で自動更新される設定(ON UPDATE CURRENT_TIMESTAMP)がない場合は、
            // ここで明示的に SET updated_at = NOW() を追加します。
            $update_sql = "
                UPDATE articles 
                SET title = :title, content = :content 
                WHERE id = :id AND user_id = :user_id
            ";
            $update_stmt = $pdo->prepare($update_sql);
            $update_stmt->execute([
                ':title'   => $input_title,
                ':content' => $input_content,
                ':id'      => $edit_id,
                ':user_id' => $current_user_id
            ]);

            // 更新完了後、投稿管理画面へリダイレクトして成功メッセージを表示（PRGパターン）
            $_SESSION['success_message'] = '記事を更新しました！';
            header('Location: index.php');
            exit;
        }
    }

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
    <title>図書室アプリ - 記事の編集</title>
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

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Helvetica Neue', Arial, 'Hiragino Kaku Gothic ProN', 'Hiragino Sans', Meiryo, sans-serif;
            background-color: var(--md-sys-color-background); color: var(--md-sys-color-on-surface);
            min-height: 100vh; display: flex; flex-direction: column;
        }

        .main-content {
            flex: 1; width: 100%; max-width: var(--max-content-width);
            margin: 0 auto; padding: 24px 20px; display: flex; flex-direction: column; gap: 20px;
        }
        .page-header { display: flex; align-items: center; gap: 12px; }
        .back-link { text-decoration: none; color: var(--md-sys-color-on-surface-variant); font-size: 14px; transition: color 0.2s; }
        .back-link:hover { color: var(--md-sys-color-primary); text-decoration: underline; }
        .page-title { font-size: 22px; font-weight: 700; margin-bottom: 8px; }

        .alert-error { padding: 16px; background-color: #fdeded; color: #d32f2f; border-radius: 8px; font-size: 14px; font-weight: 500; margin-bottom: 8px; }

        .card-container {
            background-color: var(--md-sys-color-surface); border: 1px solid var(--md-sys-color-outline);
            border-radius: 16px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }

        .form-group { margin-bottom: 24px; }
        .form-label { display: block; font-size: 14px; font-weight: 700; color: var(--md-sys-color-on-surface-variant); margin-bottom: 8px; }
        
        .book-target-wrapper { padding: 12px 16px; background-color: var(--md-sys-color-surface-variant); border-radius: 8px; }
        .book-target-title { font-size: 16px; font-weight: 700; color: var(--md-sys-color-on-surface); }
        .readonly-text { font-size: 12px; color: var(--md-sys-color-on-surface-variant); margin-top: 4px; display: block;}

        .input-field {
            width: 100%; padding: 14px 16px; font-size: 15px; color: var(--md-sys-color-on-surface);
            background-color: #ffffff; border: 1px solid var(--md-sys-color-outline); border-radius: 8px;
            outline: none; transition: 0.2s; font-family: inherit;
        }
        .input-field:focus { border-color: var(--md-sys-color-primary); box-shadow: 0 0 0 3px rgba(26, 115, 232, 0.15); }
        .input-textarea { min-height: 240px; line-height: 1.6; resize: vertical; }

        .form-actions { display: flex; gap: 12px; align-items: center; margin-top: 12px; }
        .btn {
            display: inline-flex; align-items: center; justify-content: center;
            padding: 10px 24px; font-size: 14px; font-weight: 600; border-radius: 9999px;
            text-decoration: none; cursor: pointer; transition: 0.2s; border: 1px solid transparent;
        }
        .btn-primary { background-color: var(--md-sys-color-primary); color: #ffffff; }
        .btn-primary:hover { background-color: var(--md-sys-color-primary-hover); }
        .link-cancel { font-size: 14px; color: var(--md-sys-color-on-surface-variant); text-decoration: none; }
        .link-cancel:hover { text-decoration: underline; }

        @media (min-width: 768px) {
            .main-content { padding: 40px 24px; gap: 24px; }
            .card-container { padding: 32px; }
        }
    </style>
    <link rel="stylesheet" href="../../header.css">
</head>
<body>
    <?php
    require "../../header.php";
    ?>

    <main class="main-content">
        <div class="page-header">
            <a href="index.php" class="back-link">← 投稿管理へ戻る</a>
        </div>
        
        <h1 class="page-title">記事の編集</h1>

        <?php if ($error_message): ?>
            <div class="alert-error"><?= h($error_message) ?></div>
        <?php endif; ?>

        <?php if ($article_data): ?>
            <div class="card-container">
                <form action="edit.php?id=<?= h($edit_id) ?>" method="POST">
                    
                    <div class="form-group">
                        <span class="form-label">対象の書籍</span>
                        <div class="book-target-wrapper">
                            <div class="book-target-title"><?= h($article_data['book_title']) ?></div>
                            <span class="readonly-text">※ 対象の書籍は変更できません</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="title" class="form-label">タイトル</label>
                        <input 
                            type="text" id="title" name="title" class="input-field" 
                            placeholder="記事のタイトルを入力してください" required
                            value="<?= h(isset($_POST['title']) ? $_POST['title'] : $article_data['title']) ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="content" class="form-label">本文</label>
                        <textarea 
                            id="content" name="content" class="input-field input-textarea" 
                            placeholder="この本の面白かったところ、おすすめのポイントなどを自由に書いてください..." required
                        ><?= h(isset($_POST['content']) ? $_POST['content'] : $article_data['content']) ?></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">更新する</button>
                        <a href="index.php" class="link-cancel">キャンセル</a>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <div class="form-actions" style="margin-top: 24px;">
                <a href="index.php" class="btn btn-primary">一覧へ戻る</a>
            </div>
        <?php endif; ?>
    </main>

</body>
</html>