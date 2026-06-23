<?php
// ==========================================================================
// 1. 本来はデータベースから取得する「紹介記事」のテストデータ（件数が多い想定）
// ==========================================================================
$all_articles = [
    // --- 1ページ目用のデータ ---
    [
        'id' => 1,
        'date' => '2026.06.23',
        'book_title' => '心躍るWebデザインの基本と実践テクニック',
        'article_title' => '【司書おすすめ】梅雨の季節にじっくり読みたい小説5選',
        'excerpt' => '雨の音が心地よく響く静かな図書室から、この時期にぴったりな5冊をご紹介します。どんよりした気分を吹き飛ばす爽快なミステリーから...'
    ],
    [
        'id' => 2,
        'date' => '2026.06.15',
        'book_title' => '未来を創るプログラミング思考：論理的解決力を身につける',
        'article_title' => '試験勉強に役立つ！集中力を高めるための参考書の選び方',
        'excerpt' => '「買ったはいいけれど途中で挫折してしまった…」そんな経験はありませんか？自分の現在のレベルに合った本の見極め方を解説します。'
    ],
    
    // --- 2ページ目用のデータ ---
    [
        'id' => 3,
        'date' => '2026.06.02',
        'book_title' => 'たのしい図書室の過ごし方',
        'article_title' => '新緑の季節、本を片手に外へ出かけよう！読書スポット案内',
        'excerpt' => 'いつもの図書室を飛び出して、たまには青空の下で読書を楽しんでみませんか？学校周辺の穴場ベンチや、緑に囲まれたスペースを紹介します。'
    ],
    [
        'id' => 4,
        'date' => '2026.05.20',
        'book_title' => '読書が楽しくなる魔法',
        'article_title' => '読書が苦手なキミへ。10分で一気に引き込まれる短編集',
        'excerpt' => '長い小説を読む体力がなくても大丈夫！朝の読書時間や通学の電車の中でサクッと読めて、なおかつ強烈な余韻を残すおすすめの作品です。'
    ],
    
    // --- 3ページ目用のデータ ---
    [
        'id' => 5,
        'date' => '2026.05.01',
        'book_title' => '歴史を知る・学ぶ本',
        'article_title' => 'タイムトラベル気分！物語として読める面白い歴史書',
        'excerpt' => '暗記ばかりの歴史が苦手という人必見。まるで小説を読んでいるかのように当時のドラマが頭に入ってくる本を集めました。'
    ]
];

// ==========================================================================
// 2. ページネーション（件数制御）のPHPロジック
// ==========================================================================
$per_page = 2; // 1ページあたりに表示する記事数
$total_articles = count($all_articles); // 全記事数
$total_pages = ceil($total_articles / $per_page); // 総ページ数（今回は全5件÷2 = 3ページ）

// 現在のページ数をURLパラメータ（?page=X）から取得。なければ1ページ目にする
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// ページ数が範囲外（1未満、または最大ページ超）だった場合のガード処理
if ($current_page < 1) {
    $current_page = 1;
} elseif ($current_page > $total_pages) {
    $current_page = $total_pages;
}

// 配列から現在のページに表示する分だけを切り出す（MySQLのLIMIT句のような処理）
$offset = ($current_page - 1) * $per_page;
$display_articles = array_slice($all_articles, $offset, $per_page);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>図書室アプリ - 紹介記事一覧 (Page <?php echo $current_page; ?>)</title>
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
            height: 120px;
            background-color: var(--md-sys-color-surface-variant);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--md-sys-color-on-surface-variant);
            font-size: 14px;
            font-weight: bold;
            border-bottom: 1px solid var(--md-sys-color-outline);
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

        /* --- ページネーション（リンク型） --- */
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
            pointer-events: none; /* 現在のページはクリック不可に */
        }

        .page-btn.disabled {
            opacity: 0.4;
            pointer-events: none; /* 無効な矢印はクリック不可に */
        }

        .page-btn:active {
            background-color: var(--md-sys-color-surface-variant);
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

            .article-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            }
        }
    </style>
</head>
<body>

    <header class="app-header">
        <div class="header-inner">
            <a href="../index.php" class="back-button">❮</a>
            <div class="app-title">図書室アプリ</div>
        </div>
    </header>

    <main class="main-content">
        
        <div class="page-header">
            <h1 class="page-title">紹介記事一覧</h1>
        </div>

        <div class="search-box">
            <form action="" method="GET">
                <input type="text" name="article_keyword" class="search-input" placeholder="記事タイトルや本の名で検索..." autocomplete="off" value="<?php echo isset($_GET['article_keyword']) ? htmlspecialchars($_GET['article_keyword'], ENT_QUOTES, 'UTF-8') : ''; ?>">
            </form>
        </div>

        <div class="articles-container">
            <?php if (!empty($display_articles)): ?>
                <?php foreach ($display_articles as $article): ?>
                    <a href="detail.php?id=<?php echo $article['id']; ?>" class="article-card">
                        <div class="article-banner">IMAGE (ID: <?php echo $article['id']; ?>)</div>
                        <div class="article-body">
                            <div class="article-meta">
                                <span class="book-tag">📖 <?php echo htmlspecialchars($article['book_title'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <span class="article-date"><?php echo htmlspecialchars($article['date'], ENT_QUOTES, 'UTF-8'); ?></span>
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

        <div class="pagination">
            <?php if ($current_page > 1): ?>
                <a href="?page=<?php echo $current_page - 1; ?>" class="page-btn">❮</a>
            <?php else: ?>
                <span class="page-btn disabled">❮</span>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <?php if ($i === $current_page): ?>
                    <span class="page-btn active"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?page=<?php echo $i; ?>" class="page-btn"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($current_page < $total_pages): ?>
                <a href="?page=<?php echo $current_page + 1; ?>" class="page-btn">❯</a>
            <?php else: ?>
                <span class="page-btn disabled">❯</span>
            <?php endif; ?>
        </div>

    </main>

</body>
</html>