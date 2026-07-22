<?php
require "header_session.php";
?>
<?php
// 1. データベースへの接続設定（root / パスワードなし）
$dsn = 'mysql:host=localhost;dbname=library_app;charset=utf8mb4';
$db_user = 'root';
$db_password = '';

try {
    $pdo = new PDO($dsn, $db_user, $db_password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // 2. 新着の図書を最新順に3件取得（is_deleted = 0 の有効なデータのみ）
    $books_stmt = $pdo->query("SELECT * FROM books WHERE is_deleted = 0 ORDER BY id DESC LIMIT 3");
    $new_books = $books_stmt->fetchAll();

    // 3. 新しい紹介記事を最新順に2件取得（booksテーブルと結合して本の画像URLも取得）
    // ※ articles.book_id と books.id で結合しています。環境に合わせてカラム名は調整してください。
    $articles_stmt = $pdo->query("
        SELECT a.*, b.image_url AS book_image_url 
        FROM articles AS a
        LEFT JOIN books AS b ON a.book_id = b.id
        WHERE a.is_deleted = 0 
        ORDER BY a.created_at DESC 
        LIMIT 2
    ");
    $new_articles = $articles_stmt->fetchAll();

} catch (PDOException $e) {
    // 万が一接続できない場合はエラーを表示
    exit('データベース接続エラー: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
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

        /* --- AI司書バナー --- */
        .ai-banner-container {
            padding: 0 20px;
        }

        .ai-banner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: linear-gradient(135deg, #e8f0fe 0%, #d2e3fc 100%);
            border: 1px solid #cce0ff;
            border-radius: 16px;
            padding: 20px;
            text-decoration: none;
            color: var(--md-sys-color-on-surface);
            box-shadow: 0 2px 4px rgba(26, 115, 232, 0.1);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .ai-banner:active {
            transform: scale(0.98);
            box-shadow: none;
        }

        .ai-banner-content {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .ai-banner-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--md-sys-color-primary);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .ai-banner-desc {
            font-size: 13px;
            color: var(--md-sys-color-on-surface-variant);
        }

        .ai-banner-icon {
            font-size: 20px;
            color: var(--md-sys-color-primary);
            font-weight: bold;
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
            margin: 0 20px;
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
            margin: 0;
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
            padding: 0;
            /* 【追加】中身がはみ出さないようにガード */
            overflow: hidden; 
        }

        .book-cover img {
            width: 100%;
            /* 【変更】auto から 100% に変更して領域いっぱいに固定 */
            height: 100%; 
            /* 横長・縦長の画像でも、アスペクト比を保ったまま綺麗に中央をトリミング */
            object-fit: cover; 
        }

        .book-info {
            padding: 8px;
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
            overflow: hidden;
        }

        .article-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
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
            .main-content {
                padding: 40px 24px 60px 24px;
                gap: 40px;
            }

            .search-container, .ai-banner-container, .section-header, .articles-list {
                padding: 0;
                margin: 0;
            }

            .search-input {
                padding: 16px 28px;
                font-size: 17px;
            }

            .ai-banner {
                padding: 24px 32px;
            }

            .ai-banner:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(26, 115, 232, 0.15);
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
    <link rel="stylesheet" href="header.css">
</head>
<body>

    <?php
    require "header.php";
    ?>

    <main class="main-content">
        <div class="search-container">
            <form action="books/list/index.php" method="GET">
                <input type="text" name="keyword" class="search-input" placeholder="本を検索する..." autocomplete="off">
            </form>
        </div>

        <div class="ai-banner-container">
            <a href="recommend.php" class="ai-banner">
                <div class="ai-banner-content">
                    <div class="ai-banner-title">✨ AI司書に相談する</div>
                    <div class="ai-banner-desc">今の気分に合わせて、おすすめの本を提案します</div>
                </div>
                <div class="ai-banner-icon">➔</div>
            </a>
        </div>

        <section class="books-section">
            <div class="section-header">
                <h2 class="section-title">新着の図書</h2>
                <a href="books/list/index.php" class="more-link">新着本一覧へ ➔</a>
            </div>
            
            <div class="books-grid">
                <?php foreach ($new_books as $book): ?>
                    <a href="books/index.php?id=<?php echo $book['id']; ?>" class="book-card">
                        <div class="book-cover">
                            <?php if (!empty($book['image_url'])): ?>
                                <img src="<?php echo htmlspecialchars($book['image_url'], ENT_QUOTES, 'UTF-8'); ?>" 
                                     alt="<?php echo htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8'); ?>"
                                     onerror="this.onerror=null; this.parentNode.innerHTML='NO IMAGE';">
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
                <a href="articles/list/index.php" class="more-link">記事一覧へ ➔</a>
            </div>

            <div class="articles-list">
                <?php foreach ($new_articles as $article): ?>
                    <a href="articles/index.php?id=<?php echo $article['id']; ?>" class="article-card">
                        <div class="article-thumbnail">
                            <?php if (!empty($article['book_image_url'])): ?>
                                <img src="<?php echo htmlspecialchars($article['book_image_url'], ENT_QUOTES, 'UTF-8'); ?>" 
                                     alt="記事の画像"
                                     onerror="this.onerror=null; this.parentNode.innerHTML='NO IMAGE';">
                            <?php else: ?>
                                NO IMAGE
                            <?php endif; ?>
                        </div>
                        <div class="article-info">
                            <span class="article-date"><?php echo date('Y.m.d', strtotime($article['created_at'])); ?></span>
                            <div class="article-title"><?php echo htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                    </a>
                <?php endforeach; ?>

                <?php if (empty($new_articles)): ?>
                    <p style="padding: 0 20px; color: var(--md-sys-color-on-surface-variant);">現在紹介記事はありません。</p>
                <?php endif; ?>
            </div>
        </section>
    </main>

</body>
</html>