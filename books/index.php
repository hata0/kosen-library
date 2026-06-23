<?php
// URLパラメータから本のIDを取得（未指定の場合は空文字）
$book_id = isset($_GET['id']) ? trim($_GET['id']) : '';

// --- 【MVP用のダミーデータ】 ---
// 本来は $book_id を使ってデータベースから取得します
$book = [
    'title' => '坊っちゃん',
    'author' => '夏目漱石',
    'publisher' => '新潮社',
    'year' => '1906年',
    'ndc' => '913.6' // ★日本十進分類法（913.6 = 日本文学・小説）を追加
];

// この本に紐づく紹介記事のダミーリスト
$articles = [
    [
        'id' => '101',
        'title' => '近代文学の金字塔を今こそ読むべき理由',
        'excerpt' => '正義感が強すぎる主人公の葛藤と、ユーモア溢れるキャラクターたちの掛け合いが最高です。現代人が読んでも全く色褪せない魅力があります。',
        'date' => '2026/06/20'
    ],
    [
        'id' => '102',
        'title' => 'スマホ世代に勧めたい、テンポの良い名作',
        'excerpt' => '文章が非常に軽快で、現代のライトノベルのようにサクサク読めることに驚きました。特に教頭との心理戦のシーンはハラハラします。',
        'date' => '2026/06/15'
    ]
];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8'); ?> - 図書室アプリ</title>
    <style>
        /* --- 共通デザインシステム（Material Design 3 ベース） --- */
        :root {
            --md-sys-color-primary: #1a73e8;
            --md-sys-color-primary-container: #e8f0fe;
            --md-sys-color-on-primary-container: #174ea6;
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
        .app-title { font-size: 20px; font-weight: 700; margin-bottom: 12px; }
        .app-nav { display: flex; gap: 24px; }
        .nav-item { text-decoration: none; color: var(--md-sys-color-on-surface-variant); font-size: 15px; font-weight: 500; padding: 6px 0; }

        /* --- メインコンテンツ --- */
        .main-content {
            flex: 1;
            width: 100%;
            max-width: var(--max-content-width);
            margin: 0 auto;
            padding: 24px 20px;
            display: flex;
            flex-direction: column;
            gap: 28px;
        }

        .back-link {
            display: inline-block;
            color: var(--md-sys-color-primary);
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
        }

        /* --- 本の情報セクション --- */
        .book-info-container {
            border-bottom: 1px solid var(--md-sys-color-outline);
            padding-bottom: 24px;
        }
        .book-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 16px;
            line-height: 1.3;
        }
        .book-meta-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .book-meta-item {
            font-size: 15px;
            color: var(--md-sys-color-on-surface-variant);
            display: flex;
        }
        .book-meta-label {
            width: 80px;
            flex-shrink: 0;
            color: #80868b;
        }

        /* --- セクションヘッダー ＆ 投稿ボタン --- */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }
        .section-title {
            font-size: 18px;
            font-weight: 700;
        }
        .btn-post {
            display: inline-flex;
            align-items: center;
            background-color: var(--md-sys-color-primary);
            color: #ffffff;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 9999px;
            font-size: 14px;
            font-weight: 700;
            transition: background-color 0.2s, box-shadow 0.2s;
        }
        .btn-post:hover {
            background-color: #1557b0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.15);
        }

        /* --- 紹介記事カード --- */
        .article-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .article-card {
            display: block;
            text-decoration: none;
            color: inherit;
            border: 1px solid var(--md-sys-color-outline);
            border-radius: 16px;
            padding: 20px;
            background-color: var(--md-sys-color-surface);
            transition: border-color 0.2s, background-color 0.2s, box-shadow 0.2s;
        }
        .article-card:hover {
            border-color: transparent;
            background-color: var(--md-sys-color-surface-variant);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        .article-title {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 8px;
            line-height: 1.4;
            color: var(--md-sys-color-on-surface);
        }
        .article-excerpt {
            font-size: 14px;
            color: var(--md-sys-color-on-surface-variant);
            line-height: 1.5;
            margin-bottom: 12px;
            display: -webkit-box;
            -webkit-box-orient: vertical;
            -webkit-box-line-clamp: 3;
            overflow: hidden;
        }
        .article-date {
            font-size: 12px;
            color: #80868b;
            text-align: right;
        }

        /* --- タブレット・PC向けレスポンシブ調整 --- */
        @media (min-width: 768px) {
            .header-inner { padding: 24px 24px 12px 24px; display: flex; justify-content: space-between; align-items: center; }
            .app-title { margin-bottom: 0; font-size: 24px; }
            .main-content { padding: 40px 24px; gap: 36px; }
            .book-title { font-size: 28px; }
            .section-title { font-size: 20px; }
            .article-title { font-size: 18px; }
        }
    </style>
</head>
<body>

    <header class="app-header">
        <div class="header-inner">
            <div class="app-title">図書室アプリ</div>
            <nav class="app-nav">
                <a href="../index.php" class="nav-item">ホーム</a>
                <a href="#" class="nav-item">マイページ</a>
            </nav>
        </div>
    </header>

    <main class="main-content">
        <a href="javascript:history.back();" class="back-link">← 前の画面に戻る</a>

        <?php if ($book_id !== ''): ?>
            <section class="book-info-container">
                <h1 class="book-title"><?php echo htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8'); ?></h1>
                <ul class="book-meta-list">
                    <li class="book-meta-item">
                        <span class="book-meta-label">著者</span>
                        <span class="book-meta-value"><?php echo htmlspecialchars($book['author'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </li>
                    <li class="book-meta-item">
                        <span class="book-meta-label">出版社</span>
                        <span class="book-meta-value"><?php echo htmlspecialchars($book['publisher'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </li>
                    <li class="book-meta-item">
                        <span class="book-meta-label">出版年</span>
                        <span class="book-meta-value"><?php echo htmlspecialchars($book['year'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </li>
                    <li class="book-meta-item">
                        <span class="book-meta-label">NDC</span>
                        <span class="book-meta-value"><?php echo htmlspecialchars($book['ndc'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </li>
                </ul>
            </section>

            <section class="articles-section">
                <div class="section-header">
                    <h2 class="section-title">紹介記事</h2>
                    <a href="../article/post.php?book_id=<?php echo htmlspecialchars($book_id, ENT_QUOTES, 'UTF-8'); ?>" class="btn-post">記事を投稿する</a>
                </div>

                <div class="article-list">
                    <?php foreach ($articles as $article): ?>
                        <a href="../article/index.php?id=<?php echo htmlspecialchars($article['id'], ENT_QUOTES, 'UTF-8'); ?>" class="article-card">
                            <h3 class="article-title"><?php echo htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <p class="article-excerpt"><?php echo htmlspecialchars($article['excerpt'], ENT_QUOTES, 'UTF-8'); ?></p>
                            <div class="article-date"><?php echo htmlspecialchars($article['date'], ENT_QUOTES, 'UTF-8'); ?></div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php else: ?>
            <p>本の情報が見つかりませんでした。</p>
        <?php endif; ?>
    </main>

</body>
</html>
