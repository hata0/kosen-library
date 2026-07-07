<?php
// セッションの開始
session_start();

// データベース接続設定
$db_host = 'localhost';
$db_name = 'library_app'; // sql.txtで定義されたデータベース名
$db_user = 'root';        // ご自身の環境に合わせて変更してください
$db_pass = '';            // ご自身の環境に合わせて変更してください

// URLパラメータから記事のIDを取得
$article_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$article_data = null;
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

    // 記事IDが指定されている場合のみデータ取得を実行
    if ($article_id > 0) {
        // articles, users, books の3テーブルを結合して必要な情報をすべて取得
        // 論理削除（is_deleted = 1）されているものは除外する
        $sql = "
            SELECT 
                a.id AS article_id,
                a.title AS article_title,
                a.content,
                a.created_at,
                u.name AS user_name,
                b.id AS book_id,
                b.title AS book_title,
                b.author,
                b.publisher,
                b.image_url
            FROM 
                articles a
            JOIN 
                users u ON a.user_id = u.id
            JOIN 
                books b ON a.book_id = b.id
            WHERE 
                a.id = :id 
                AND a.is_deleted = 0 
                AND u.is_deleted = 0 
                AND b.is_deleted = 0
            LIMIT 1
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $article_id]);
        $article_data = $stmt->fetch();
    }

} catch (PDOException $e) {
    $error_message = 'データベース接続エラー: ' . $e->getMessage();
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
    <title><?= $article_data ? h($article_data['article_title']) : '記事が見つかりません' ?> - 図書室アプリ</title>
    <style>
        /* --- 共通デザインシステム（Material Design 3 ベース） --- */
        :root {
            --md-sys-color-primary: #1a73e8;
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
            display: flex;
            flex-direction: column;
        }

        /* --- ヘッダー --- */
        .app-header {
            background-color: var(--md-sys-color-surface);
            border-bottom: 1px solid var(--md-sys-color-outline);
            position: sticky; top: 0; z-index: 10; width: 100%;
        }
        .header-inner { max-width: var(--max-content-width); margin: 0 auto; padding: 16px 20px 8px 20px; }
        .app-title { font-size: 20px; font-weight: 700; margin-bottom: 12px; }
        .app-nav { display: flex; gap: 24px; }
        .nav-item { text-decoration: none; color: var(--md-sys-color-on-surface-variant); font-size: 15px; font-weight: 500; padding: 6px 0; }

        /* --- メインコンテンツ --- */
        .main-content {
            flex: 1; width: 100%; max-width: var(--max-content-width);
            margin: 0 auto; padding: 24px 20px; display: flex; flex-direction: column; gap: 24px;
        }

        .back-link {
            display: inline-block; color: var(--md-sys-color-primary);
            text-decoration: none; font-weight: 500; font-size: 14px;
        }

        /* --- 記事詳細エリア --- */
        .article-container {
            border-bottom: 1px solid var(--md-sys-color-outline);
            padding-bottom: 32px;
        }
        .article-header { margin-bottom: 24px; }
        .article-title {
            font-size: 22px; font-weight: 700; line-height: 1.4;
            margin-bottom: 16px; color: var(--md-sys-color-on-surface);
        }
        
        .article-meta {
            display: flex; align-items: center; flex-wrap: wrap; gap: 16px;
        }
        .article-author {
            display: inline-flex; align-items: center; gap: 6px;
            font-size: 13px; font-weight: 600; color: var(--md-sys-color-on-surface);
            background-color: var(--md-sys-color-surface-variant);
            padding: 6px 12px; border-radius: 9999px; border: 1px solid var(--md-sys-color-outline);
        }
        .author-icon { font-size: 14px; color: var(--md-sys-color-on-surface-variant); }
        .article-date { font-size: 13px; color: var(--md-sys-color-on-surface-variant); }
        
        .article-body {
            font-size: 16px; line-height: 1.8; color: var(--md-sys-color-on-surface);
            white-space: pre-wrap; letter-spacing: 0.3px;
        }

        /* --- 紹介された本のカード --- */
        .related-book-section { margin-top: 8px; }
        .section-title {
            font-size: 15px; font-weight: 700; color: var(--md-sys-color-on-surface-variant);
            margin-bottom: 12px; letter-spacing: 0.5px;
        }
        .book-card {
            display: flex; flex-direction: row; text-decoration: none; color: inherit;
            border: 1px solid var(--md-sys-color-outline); border-radius: 14px;
            background-color: var(--md-sys-color-surface); overflow: hidden; transition: 0.2s;
        }
        .book-card:hover {
            border-color: transparent; background-color: var(--md-sys-color-surface-variant);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        .book-card-image-wrapper {
            width: 72px; aspect-ratio: 2 / 3; position: relative; flex-shrink: 0;
            background-color: var(--md-sys-color-surface-variant); border-right: 1px solid var(--md-sys-color-outline);
        }
        .book-card-image { width: 100%; height: 100%; object-fit: cover; }
        .no-image-placeholder {
            display: flex; width: 100%; height: 100%; align-items: center;
            justify-content: center; color: var(--md-sys-color-on-surface-variant);
            font-size: 11px; font-weight: 500;
        }
        .book-card-details {
            flex: 1; padding: 12px 14px; display: flex; flex-direction: column;
            justify-content: center; min-width: 0;
        }
        .book-card-title {
            font-size: 14px; font-weight: 700; color: var(--md-sys-color-on-surface);
            margin-bottom: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .book-card-meta {
            font-size: 12px; color: var(--md-sys-color-on-surface-variant);
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }

        /* アラートエラー */
        .alert-error {
            padding: 16px; background-color: #fdeded; color: #d32f2f;
            border-radius: 8px; font-size: 14px; font-weight: 500; margin-bottom: 24px;
        }

        /* --- タブレット・PC向けレスポンシブ調整 --- */
        @media (min-width: 768px) {
            .header-inner { padding: 24px 24px 12px 24px; display: flex; justify-content: space-between; align-items: center; }
            .app-title { margin-bottom: 0; font-size: 24px; }
            .main-content { padding: 40px 24px; gap: 32px; }
            
            .article-title { font-size: 28px; }
            .article-body { font-size: 17px; line-height: 1.9; }

            .book-card-image-wrapper { width: 84px; }
            .book-card-title { font-size: 16px; }
            .book-card-meta { font-size: 13px; }
        }
    </style>
</head>
<body>

    <header class="app-header">
        <div class="header-inner">
            <div class="app-title">図書室アプリ</div>
            <nav class="app-nav">
                <a href="../index.php" class="nav-item">ホーム</a>
                <a href="../mypage/" class="nav-item">マイページ</a>
            </nav>
        </div>
    </header>

    <main class="main-content">
        <a href="javascript:history.back();" class="back-link">← 前の画面に戻る</a>

        <?php if ($error_message): ?>
            <div class="alert-error"><?= h($error_message) ?></div>
        <?php endif; ?>

        <?php if ($article_data): ?>
            <article class="article-container">
                <header class="article-header">
                    <h1 class="article-title"><?= h($article_data['article_title']) ?></h1>
                    
                    <div class="article-meta">
                        <div class="article-author">
                            <span class="author-icon">👤</span>
                            <?= h($article_data['user_name']) ?>
                        </div>
                        <div class="article-date">
                            投稿日: <?= h(date('Y/m/d H:i', strtotime($article_data['created_at']))) ?>
                        </div>
                    </div>
                </header>
                
                <div class="article-body"><?= h($article_data['content']) ?></div>
            </article>

            <section class="related-book-section">
                <h2 class="section-title">この記事で紹介された本</h2>
                
                <a href="../books/?id=<?= h($article_data['book_id']) ?>" class="book-card">
                    
                    <div class="book-card-image-wrapper">
                        <?php if (!empty($article_data['image_url'])): ?>
                            <img src="../<?= h($article_data['image_url']) ?>" 
                                 alt="<?= h($article_data['book_title']) ?>のカバー画像" 
                                 class="book-card-image"
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <?php endif; ?>
                        <div class="no-image-placeholder" style="display: <?= !empty($article_data['image_url']) ? 'none' : 'flex' ?>;">
                            No Image
                        </div>
                    </div>

                    <div class="book-card-details">
                        <h3 class="book-card-title"><?= h($article_data['book_title']) ?></h3>
                        <div class="book-card-meta">
                            <?= h($article_data['author']) ?> / <?= h($article_data['publisher']) ?>
                        </div>
                    </div>
                </a>
            </section>
        <?php else: ?>
            <div style="text-align: center; padding: 40px; border: 1px dashed #e0e0e0; border-radius: 12px; color: #5f6368; margin-top: 24px;">
                指定された記事が存在しないか、既に削除されています。
            </div>
        <?php endif; ?>
    </main>

</body>
</html>