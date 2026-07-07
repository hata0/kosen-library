<?php
// ==========================================================================
// 1. データベース接続設定 (必要に応じて書き換えてください)
// ==========================================================================
$dsn = 'mysql:host=localhost;dbname=library_app;charset=utf8mb4';
$user = 'root';
$password = ''; 

try {
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    exit('データベース接続失敗: ' . $e->getMessage());
}

// ==========================================================================
// 2. ページネーションと検索用のパラメータ処理
// ==========================================================================
$per_page = 4; // 1ページあたりの表示件数

// 現在のページ数を取得
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) {
    $current_page = 1;
}

// 検索キーワードを取得
$keyword = isset($_GET['article_keyword']) ? trim($_GET['article_keyword']) : '';

// ==========================================================================
// 3. データ件数と紹介記事データの取得 (SQLクエリ)
// ==========================================================================
// 検索条件の構築 (論理削除されていない有効な記事のみ対象)
$where_clauses = ['a.is_deleted = 0'];
$params = [];

if ($keyword !== '') {
    // ★ 修正ポイント：本のタイトル（b.title）を除外し、記事タイトルと紹介本文のみを対象に
    $where_clauses[] = '(a.title LIKE :kw1 OR a.content LIKE :kw2)';
    $params[':kw1'] = "%{$keyword}%";
    $params[':kw2'] = "%{$keyword}%";
}

$where_sql = implode(' AND ', $where_clauses);

// 総件数を取得して総ページ数を計算
$count_sql = "SELECT COUNT(*) FROM articles a JOIN books b ON a.book_id = b.id WHERE {$where_sql}";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_articles = $stmt->fetchColumn();
$total_pages = ceil($total_articles / $per_page);

// 現在のページが最大ページを超えないようにガード
if ($total_pages > 0 && $current_page > $total_pages) {
    $current_page = $total_pages;
}

// 該当するページの紹介記事データを取得
$offset = ($current_page - 1) * $per_page;
$select_sql = "SELECT a.id, a.title AS article_title, a.content AS excerpt, a.created_at, b.title AS book_title, b.image_url 
               FROM articles a 
               JOIN books b ON a.book_id = b.id 
               WHERE {$where_sql} 
               ORDER BY a.created_at DESC 
               LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($select_sql);

// パラメータをバインド
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$display_articles = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>図書室アプリ - 紹介記事一覧</title>
    <style>
        /* --- デザインシステム（Material Design 3 ベース） --- */
        :root {
            --md-sys-color-primary: #1a73e8;
            --md-sys-color-background: #ffffff;
            --md-sys-color-surface: #ffffff;
            --md-sys-color-surface-variant: #f1f3f4;
            --md-sys-color-on-surface: #1f1f1f;
            --md-sys-color-on-surface-variant: #5f6368;
            --md-sys-color-outline: #e0e0e0;
            --max-content-width: 760px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            -webkit-tap-highlight-color: transparent;
        }

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
            position: sticky;
            top: 0;
            z-index: 10;
            width: 100%;
        }

        .header-inner {
            max-width: var(--max-content-width);
            margin: 0 auto;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .back-button {
            text-decoration: none;
            color: var(--md-sys-color-on-surface-variant);
            font-size: 20px;
            font-weight: 700;
            padding: 4px 8px;
            margin-left: -8px;
            transition: opacity 0.2s;
        }

        .back-button:active {
            opacity: 0.5;
        }

        .app-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--md-sys-color-on-surface);
        }

        /* --- メインコンテンツエリア --- */
        .main-content {
            flex: 1;
            width: 100%;
            max-width: var(--max-content-width);
            margin: 0 auto;
            padding: 20px 0 40px 0;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .page-header {
            padding: 0 20px;
        }

        .page-title {
            font-size: 22px;
            font-weight: 700;
            color: var(--md-sys-color-on-surface);
        }

        /* 検索バー */
        .search-box {
            padding: 0 20px;
        }

        .search-input {
            width: 100%;
            padding: 12px 20px;
            font-size: 15px;
            color: var(--md-sys-color-on-surface);
            background-color: var(--md-sys-color-surface-variant);
            border: 1px solid transparent;
            border-radius: 8px;
            outline: none;
        }

        /* --- 紹介記事リスト --- */
        .articles-container {
            display: flex;
            flex-direction: column;
            gap: 16px;
            padding: 0 20px;
        }

        .article-card {
            background-color: var(--md-sys-color-surface);
            border: 1px solid var(--md-sys-color-outline);
            border-radius: 12px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            text-decoration: none;
            color: inherit;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .article-card:active {
            transform: scale(0.99);
        }

        .article-banner {
            width: 100%;
            height: 160px;
            background-color: var(--md-sys-color-surface-variant);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--md-sys-color-on-surface-variant);
            font-size: 14px;
            font-weight: bold;
            border-bottom: 1px solid var(--md-sys-color-outline);
            overflow: hidden;
        }

        .article-banner img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .article-body {
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .article-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            color: var(--md-sys-color-on-surface-variant);
        }

        .book-tag {
            background-color: var(--md-sys-color-surface-variant);
            color: var(--md-sys-color-on-surface);
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            max-width: 70%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .article-title {
            font-size: 16px;
            font-weight: 700;
            line-height: 1.4;
            color: var(--md-sys-color-on-surface);
        }

        .article-excerpt {
            font-size: 13px;
            line-height: 1.6;
            color: var(--md-sys-color-on-surface-variant);
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* --- ページネーション --- */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            margin-top: 12px;
            padding: 0 20px;
        }

        .page-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 36px;
            height: 36px;
            padding: 0 6px;
            border: 1px solid var(--md-sys-color-outline);
            border-radius: 8px;
            background-color: var(--md-sys-color-surface);
            color: var(--md-sys-color-on-surface);
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            transition: background-color 0.2s;
        }

        .page-btn.active {
            background-color: var(--md-sys-color-primary);
            color: #ffffff;
            border-color: var(--md-sys-color-primary);
            pointer-events: none;
        }

        .page-btn.disabled {
            opacity: 0.4;
            pointer-events: none;
        }

        /* ==========================================================================
           タブレット・PC向けのレスポンシブ調整 (ブレイクポイント: 768px以上)
           ========================================================================== */
        @media (min-width: 768px) {
            .header-inner {
                padding: 24px;
            }

            .main-content {
                padding: 40px 24px 60px 24px;
                gap: 28px;
            }

            .page-header, .search-box, .articles-container, .pagination {
                padding: 0;
                margin: 0;
            }

            .page-title {
                font-size: 26px;
            }

            .articles-container {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 20px;
            }
        }
    </style>
</head>
<body>

    <header class="app-header">
        <div class="header-inner">
            <a href="oregatukutta_HomePage.php" class="back-button">❮</a>
            <div class="app-title">図書室アプリ</div>
        </div>
    </header>

    <main class="main-content">
        
        <div class="page-header">
            <h1 class="page-title">紹介記事一覧</h1>
        </div>

        <div class="search-box">
            <form action="" method="GET">
                <input type="text" name="article_keyword" class="search-input" placeholder="記事のタイトルや内容で検索..." autocomplete="off" value="<?php echo htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8'); ?>">
            </form>
        </div>

        <div class="articles-container">
            <?php if (!empty($display_articles)): ?>
                <?php foreach ($display_articles as $article): ?>
                    <?php 
                        $date_formatted = date('Y.m.d', strtotime($article['created_at']));
                    ?>
                    <a href="detail.php?id=<?php echo $article['id']; ?>" class="article-card">
                        
                        <div class="article-banner">
                            <?php if (!empty($article['image_url'])): ?>
                                <img src="<?php echo htmlspecialchars($article['image_url'], ENT_QUOTES, 'UTF-8'); ?>" alt="本の表紙">
                            <?php else: ?>
                                NO IMAGE
                            <?php endif; ?>
                        </div>

                        <div class="article-body">
                            <div class="article-meta">
                                <span class="book-tag">📖 <?php echo htmlspecialchars($article['book_title'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <span class="article-date"><?php echo $date_formatted; ?></span>
                            </div>
                            <h2 class="article-title"><?php echo htmlspecialchars($article['article_title'], ENT_QUOTES, 'UTF-8'); ?></h2>
                            <p class="article-excerpt"><?php echo htmlspecialchars($article['excerpt'], ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; color: var(--md-sys-color-on-surface-variant); padding: 40px 0;">記事が見つかりませんでした。</p>
            <?php endif; ?>
        </div>

        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($current_page > 1): ?>
                    <a href="?page=<?php echo $current_page - 1; ?>&article_keyword=<?php echo urlencode($keyword); ?>" class="page-btn">❮</a>
                <?php else: ?>
                    <span class="page-btn disabled">❮</span>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if ($i === $current_page): ?>
                        <span class="page-btn active"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?page=<?php echo $i; ?>&article_keyword=<?php echo urlencode($keyword); ?>" class="page-btn"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($current_page < $total_pages): ?>
                    <a href="?page=<?php echo $current_page + 1; ?>&article_keyword=<?php echo urlencode($keyword); ?>" class="page-btn">❯</a>
                <?php else: ?>
                    <span class="page-btn disabled">❯</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </main>

</body>
</html>