<?php
// 1. セッションの開始（ログイン状態をチェックするために必須）
session_start();

// 2. データベースへの接続設定（root / パスワードなし）
$dsn = 'mysql:host=localhost;dbname=library_app;charset=utf8mb4';
$db_user = 'root';
$db_password = '';

try {
    $pdo = new PDO($dsn, $db_user, $db_password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // 3. 新着の図書を最新順に3件取得（is_deleted = 0 の有効なデータのみ）
    // ※ id の大きい順（DESC）で並べることで新着順にしています
    $books_stmt = $pdo->query("SELECT * FROM books WHERE is_deleted = 0 ORDER BY id DESC LIMIT 3");
    $new_books = $books_stmt->fetchAll();

    // 4. 新しい紹介記事を最新順に2件取得（is_deleted = 0 の有効なデータのみ）
    // ※ created_at の新しい順（DESC）にしています
    $articles_stmt = $pdo->query("SELECT * FROM articles WHERE is_deleted = 0 ORDER BY created_at DESC LIMIT 2");
    $new_articles = $articles_stmt->fetchAll();

} catch (PDOException $e) {
    // 万が一接続できない場合はエラーを表示
    exit('データベース接続エラー: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}

// 5. ログイン状態によって右上のナビゲーションの文字とリンク先を切り替える
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    $nav_text = "マイページ";
    $nav_link = "mypage/index.php";
} else {
    $nav_text = "ログイン";
    $nav_link = "login/index.php";
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>図書室アプリ - ホーム</title>
    <style>
        /* --- デザインシステム（元のコードを100%完全維持） --- */
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
            padding: 16px 20px 8px 20px;
        }

        .app-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--md-sys-color-on-surface);
            margin-bottom: 12px;
        }

        .app-nav {
            display: flex;
            gap: 24px;
        }

        .nav-item {
            text-decoration: none;
            color: var(--md-sys-color-on-surface-variant);
            font-size: 15px;
            font-weight: 500;
            padding: 6px 0;
            position: relative;
            transition: color 0.2s;
        }

        .nav-item.active {
            color: var(--md-sys-color-on-surface);
            font-weight: 700;
        }

        .nav-item.active::after {
            content: '';
            position: absolute;
            bottom: -9px;
            left: 0;
            width: 100%;
            height: 3px;
            background-color: var(--md-sys-color-primary);
            border-radius: 3px 3px 0 0;
        }

        /* --- メインコンテンツエリア --- */
        .main-content {
            flex: 1;
            width: 100%;
            max-width: var(--max-content-width);
            margin: 0 auto;
            padding: 24px 0 40px 0;
            display: flex;
            flex-direction: column;
            gap: 32px;
        }

        /* 検索バー */
        .search-container {
            padding: 0 20px;
        }

        .search-input {
            width: 100%;
            padding: 14px 24px;
            font-size: 16px;
            color: var(--md-sys-color-on-surface);
            background-color: var(--md-sys-color-surface-variant);
            border: 1px solid transparent;
            border-radius: 9999px;
            outline: none;
            transition: border-color 0.2s, background-color 0.2s, box-shadow 0.2s;
        }

        .search-input::placeholder {
            color: #9aa0a6;
        }

        .search-input:focus {
            background-color: var(--md-sys-color-surface);
            border-color: var(--md-sys-color-primary);
            box-shadow: 0 1px 6px rgba(32, 33, 36, 0.1);
        }

        /* --- セクション共通の見出し部分 --- */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            padding: 0 20px;
        }

        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--md-sys-color-on-surface);
        }

        .more-link {
            font-size: 13px;
            font-weight: 700;
            color: var(--md-sys-color-primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 2px;
            padding: 4px 8px;
            border-radius: 4px;
            transition: opacity 0.2s;
        }

        .more-link:active {
            opacity: 0.7;
        }

        /* --- 新着本セクション：横スクロール --- */
        .books-grid {
            display: flex;
            overflow-x: auto;
            flex-wrap: nowrap;
            gap: 12px;
            padding: 4px 20px 16px 20px;
            scroll-snap-type: x mandatory;
            -webkit-overflow-scrolling: touch;
        }

        .books-grid::-webkit-scrollbar {
            display: none;
        }

        .book-card {
            width: 140px;
            flex-shrink: 0;
            scroll-snap-align: start;
            background-color: var(--md-sys-color-surface);
            border: 1px solid var(--md-sys-color-outline);
            border-radius: 12px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            text-decoration: none;
            color: inherit;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .book-card:active {
            transform: scale(0.96);
        }

        .book-cover {
            width: 100%;
            aspect-ratio: 3 / 4;
            background-color: var(--md-sys-color-surface-variant);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--md-sys-color-on-surface-variant);
            font-size: 12px;
            font-weight: bold;
            border-bottom: 1px solid var(--md-sys-color-outline);
        }
        
        .book-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .book-info {
            padding: 10px;
            display: flex;
            flex-direction: column;
            gap: 4px;
            flex: 1;
        }

        .book-title {
            font-size: 13px;
            font-weight: 700;
            line-height: 1.4;
            color: var(--md-sys-color-on-surface);
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            height: 36.4px;
        }

        .book-author {
            font-size: 11px;
            color: var(--md-sys-color-on-surface-variant);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* --- 新しい紹介記事セクション：スマホ向け縦並びリスト --- */
        .articles-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
            padding: 0 20px;
        }

        .article-card {
            background-color: var(--md-sys-color-surface);
            border: 1px solid var(--md-sys-color-outline);
            border-radius: 12px;
            overflow: hidden;
            display: flex;
            text-decoration: none;
            color: inherit;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .article-card:active {
            transform: scale(0.98);
        }

        .article-thumbnail {
            width: 90px;
            height: 90px;
            flex-shrink: 0;
            background-color: var(--md-sys-color-surface-variant);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--md-sys-color-on-surface-variant);
            font-size: 11px;
            font-weight: bold;
            border-right: 1px solid var(--md-sys-color-outline);
        }

        .article-info {
            padding: 12px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 4px;
            flex: 1;
            min-width: 0;
        }

        .article-date {
            font-size: 11px;
            color: var(--md-sys-color-on-surface-variant);
        }

        .article-title {
            font-size: 14px;
            font-weight: 700;
            line-height: 1.4;
            color: var(--md-sys-color-on-surface);
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* ==========================================================================
           タブレット・PC向けのレスポンシブ調整 (ブレイクポイント: 768px以上)
           ========================================================================== */
        @media (min-width: 768px) {
            .header-inner {
                padding: 24px 24px 12px 24px;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .app-title {
                margin-bottom: 0;
                font-size: 24px;
            }

            .nav-item {
                font-size: 16px;
            }

            .nav-item.active::after {
                bottom: -13px;
            }

            .main-content {
                padding: 40px 24px 60px 24px;
                gap: 40px;
            }

            .search-container, .section-header, .articles-list {
                padding: 0;
                margin: 0;
            }

            .search-input {
                padding: 16px 28px;
                font-size: 17px;
            }

            .books-grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 20px;
                padding: 0;
                overflow-x: visible;
            }

            .book-card {
                width: 100%;
            }

            .book-card:hover {
                transform: translateY(-4px);
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            }

            .book-title {
                font-size: 15px;
                height: auto;
            }

            .articles-list {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 20px;
            }

            .article-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
            }
        }
    </style>
</head>
<body>

    <header class="app-header">
        <div class="header-inner">
            <div class="app-title">図書室アプリ</div>
            <nav class="app-nav">
                <a href="#" class="nav-item active">ホーム</a>
                <a href="<?php echo htmlspecialchars($nav_link, ENT_QUOTES, 'UTF-8'); ?>" class="nav-item"><?php echo htmlspecialchars($nav_text, ENT_QUOTES, 'UTF-8'); ?></a>
            </nav>
        </div>
    </header>

    <main class="main-content">
        <div class="search-container">
            <form action="search/index.php" method="GET">
                <input type="text" name="keyword" class="search-input" placeholder="本を検索する..." autocomplete="off">
            </form>
        </div>

        <section class="books-section">
            <div class="section-header">
                <h2 class="section-title">新着の図書</h2>
                <a href="search/index.php" class="more-link">新着本一覧へ ➔</a>
            </div>
            
            <div class="books-grid">
                <?php foreach ($new_books as $book): ?>
                    <a href="book_detail.php?id=<?php echo $book['id']; ?>" class="book-card">
                        <div class="book-cover">
                            <?php if (!empty($book['image_url']) && file_exists($book['image_url'])): ?>
                                <img src="<?php echo htmlspecialchars($book['image_url'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8'); ?>">
                            <?php else: ?>
                                NO IMAGE
                            <?php endif; ?>
                        </div>
                        <div class="book-info">
                            <div class="book-title"><?php echo htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="book-author"><?php echo htmlspecialchars($book['author'], ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                    </a>
                <?php endforeach; ?>
                
                <?php if (empty($new_books)): ?>
                    <p style="padding: 0 20px; color: var(--md-sys-color-on-surface-variant);">現在新着の図書はありません。</p>
                <?php endif; ?>
            </div>
        </section>

        <section class="articles-section">
            <div class="section-header">
                <h2 class="section-title">新しい紹介記事</h2>
                <a href="article_list.php" class="more-link">記事一覧へ ➔</a>
            </div>

            <div class="articles-list">
                <?php foreach ($new_articles as $article): ?>
                    <a href="article_detail.php?id=<?php echo $article['id']; ?>" class="article-card">
                        <div class="article-thumbnail">📄 ARTICLE</div>
                        <div class="article-info">
                            <span class="article-date"><?php echo date('Y.m.d', strtotime($article['created_at'])); ?></span>
                            <div class="article-title"><?php echo htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                    </a>
                <?php endforeach; ?>

                <?php if (empty($new_articles)): ?>
                    <p style="color: var(--md-sys-color-on-surface-variant);">現在紹介記事はありません。</p>
                <?php endif; ?>
            </div>
        </section>
    </main>

</body>
</html>