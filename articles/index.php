<?php
// URLパラメータから記事のIDを取得（未指定の場合は空文字）
$article_id = isset($_GET['id']) ? trim($_GET['id']) : '';

// --- 【MVP用のダミーデータ】 ---
// 本来は $article_id を使ってデータベース（articlesテーブルとbooksテーブルの結合など）から取得します

// 記事データ
$article = [
    'id' => '101',
    'title' => '近代文学の金字塔を今こそ読むべき理由',
    'content' => "正義感が強すぎる主人公の葛藤と、ユーモア溢れるキャラクターたちの掛け合いが最高です。現代人が読んでも全く色褪せない魅力があります。\n\n特に印象的なのは、周囲の大人たちのずる賢さに対して、主人公がどこまでも真っ直ぐに、愚直なまでに自分の正義を貫こうとする姿勢です。一見すると不器用で損ばかりしているように見えますが、読み進めるうちにその純粋さに心を打たれます。\n\n文章も非常にテンポが良く、当時の言葉遣いでありながら現代の小説のようにスラスラと読めてしまうのも驚きです。読書が苦手な人にこそ、ぜひ最初のステップとして手に取ってほしい名作です。",
    'date' => '2026/06/20',
    'book_id' => '1' // 紐づく本のID
];

// 紐づく本のデータ
$book = [
    'id' => '1',
    'title' => '坊っちゃん',
    'author' => '夏目漱石',
    'publisher' => '新潮社',
    'year' => '1906年',
    'ndc' => '913.6',
    'image_url' => 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?auto=format&fit=crop&w=200&q=80'
];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8'); ?> - 図書室アプリ</title>
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
            gap: 24px;
        }

        .back-link {
            display: inline-block;
            color: var(--md-sys-color-primary);
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
        }

        /* --- 記事詳細エリア --- */
        .article-container {
            border-bottom: 1px solid var(--md-sys-color-outline);
            padding-bottom: 32px;
        }
        .article-header {
            margin-bottom: 24px;
        }
        .article-title {
            font-size: 22px;
            font-weight: 700;
            line-height: 1.4;
            margin-bottom: 12px;
            color: var(--md-sys-color-on-surface);
        }
        .article-date {
            font-size: 13px;
            color: var(--md-sys-color-on-surface-variant);
        }
        
        /* 記事本文のタイポグラフィ（読みやすさ重視） */
        .article-body {
            font-size: 16px;
            line-height: 1.8;
            color: var(--md-sys-color-on-surface);
            white-space: pre-wrap; /* 改行コードをそのまま反映 */
            letter-spacing: 0.3px;
        }

        /* --- 紹介された本のカード（検索画面のパーツと統一） --- */
        .related-book-section {
            margin-top: 8px;
        }
        .section-title {
            font-size: 15px;
            font-weight: 700;
            color: var(--md-sys-color-on-surface-variant);
            margin-bottom: 12px;
            letter-spacing: 0.5px;
        }
        .book-card {
            display: flex;
            flex-direction: row;
            text-decoration: none;
            color: inherit;
            border: 1px solid var(--md-sys-color-outline);
            border-radius: 14px;
            background-color: var(--md-sys-color-surface);
            overflow: hidden;
            transition: 0.2s;
        }
        .book-card:hover {
            border-color: transparent;
            background-color: var(--md-sys-color-surface-variant);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        .book-card-image-wrapper {
            width: 72px;
            aspect-ratio: 2 / 3;
            position: relative;
            flex-shrink: 0;
            background-color: var(--md-sys-color-surface-variant);
            border-right: 1px solid var(--md-sys-color-outline);
        }
        .book-card-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .no-image-placeholder {
            display: flex;
            width: 100%;
            height: 100%;
            align-items: center;
            justify-content: center;
            color: var(--md-sys-color-on-surface-variant);
            font-size: 11px;
            font-weight: 500;
        }
        .book-card-details {
            flex: 1;
            padding: 12px 14px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-width: 0;
        }
        .book-card-title {
            font-size: 14px;
            font-weight: 700;
            color: var(--md-sys-color-on-surface);
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .book-card-meta {
            font-size: 12px;
            color: var(--md-sys-color-on-surface-variant);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
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
                <a href="#" class="nav-item">マイページ</a>
            </nav>
        </div>
    </header>

    <main class="main-content">
        <a href="javascript:history.back();" class="back-link">← 前の画面に戻る</a>

        <?php if ($article_id !== ''): ?>
            <article class="article-container">
                <header class="article-header">
                    <h1 class="article-title"><?php echo htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8'); ?></h1>
                    <div class="article-date">投稿日: <?php echo htmlspecialchars($article['date'], ENT_QUOTES, 'UTF-8'); ?></div>
                </header>
                
                <div class="article-body"><?php echo htmlspecialchars($article['content'], ENT_QUOTES, 'UTF-8'); ?></div>
            </article>

            <section class="related-book-section">
                <h2 class="section-title">この記事で紹介された本</h2>
                
                <a href="../books/?id=<?php echo htmlspecialchars($book['id'], ENT_QUOTES, 'UTF-8'); ?>" class="book-book-link book-card">
                    
                    <div class="book-card-image-wrapper">
                        <?php if (!empty($book['image_url'])): ?>
                            <img src="<?php echo htmlspecialchars($book['image_url'], ENT_QUOTES, 'UTF-8'); ?>" 
                                 alt="<?php echo htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8'); ?>のカバー画像" 
                                 class="book-card-image"
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <?php endif; ?>
                        <div class="no-image-placeholder" style="display: <?php echo !empty($book['image_url']) ? 'none' : 'flex'; ?>;">
                            No Image
                        </div>
                    </div>

                    <div class="book-card-details">
                        <h3 class="book-card-title"><?php echo htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                        <div class="book-card-meta">
                            <?php echo htmlspecialchars($book['author'], ENT_QUOTES, 'UTF-8'); ?> / 
                            <?php echo htmlspecialchars($book['publisher'], ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    </div>
                </a>
            </section>
        <?php else: ?>
            <p>記事の情報が見つかりませんでした。</p>
        <?php endif; ?>
    </main>

</body>
</html>
