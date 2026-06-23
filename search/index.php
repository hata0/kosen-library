<?php
$search_keyword = '';
// パラメータ ?keyword= の値を取得
if (isset($_GET['keyword']) && trim($_GET['keyword']) !== '') {
    $search_keyword = trim($_GET['keyword']);
}

// --- 【MVP用のダミーデータ】 ---
// 本来は $search_keyword を使ってSQL（LIKE検索など）を発行しデータベースから取得します
$books = [
    [
        'id' => '1',
        'title' => '坊っちゃん',
        'author' => '夏目漱石',
        'publisher' => '新潮社',
        'year' => '1906年',
        'ndc' => '913.6',
        'image_url' => 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?auto=format&fit=crop&w=200&q=80'
    ],
    [
        'id' => '2',
        'title' => '人間失格',
        'author' => '太宰治',
        'publisher' => '角川書店',
        'year' => '1948年',
        'ndc' => '913.6',
        'image_url' => 'https://images.unsplash.com/photo-1512820790803-83ca734da794?auto=format&fit=crop&w=200&q=80'
    ],
    [
        'id' => '3',
        'title' => '吾輩は猫である（エラーテスト用）',
        'author' => '夏目漱石',
        'publisher' => '岩波書店',
        'year' => '1905年',
        'ndc' => '913.6',
        // 読込失敗テスト用：あえて存在しないURLにしています（自動的にNo Imageになります）
        'image_url' => 'https://example.com/invalid-image-for-test.jpg'
    ]
];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>検索結果 - 図書室アプリ</title>
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
        body { font-family: 'Helvetica Neue', Arial, 'Hiragino Kaku Gothic ProN', 'Hiragino Sans', Meiryo, sans-serif; background-color: var(--md-sys-color-background); color: var(--md-sys-color-on-surface); min-height: 100vh; display: flex; flex-direction: column; }
        
        /* --- ヘッダー --- */
        .app-header { background-color: var(--md-sys-color-surface); border-bottom: 1px solid var(--md-sys-color-outline); position: sticky; top: 0; z-index: 10; width: 100%; }
        .header-inner { max-width: var(--max-content-width); margin: 0 auto; padding: 16px 20px 8px 20px; }
        .app-title { font-size: 20px; font-weight: 700; color: var(--md-sys-color-on-surface); margin-bottom: 12px; }
        .app-nav { display: flex; gap: 24px; }
        .nav-item { text-decoration: none; color: var(--md-sys-color-on-surface-variant); font-size: 15px; font-weight: 500; padding: 6px 0; }
        
        /* --- メインコンテンツ --- */
        .main-content { flex: 1; width: 100%; max-width: var(--max-content-width); margin: 0 auto; padding: 24px 20px; display: flex; flex-direction: column; }
        
        .search-container { width: 100%; }
        .search-input { width: 100%; padding: 14px 24px; font-size: 16px; color: var(--md-sys-color-on-surface); background-color: #f1f3f4; border: 1px solid transparent; border-radius: 9999px; outline: none; transition: 0.2s; }
        .search-input:focus { background-color: var(--md-sys-color-surface); border-color: var(--md-sys-color-primary); box-shadow: 0 1px 6px rgba(32, 33, 36, 0.1); }
        
        .placeholder-text { color: var(--md-sys-color-on-surface-variant); font-size: 14px; text-align: center; line-height: 1.6; padding: 40px 20px; }
        .result-header { margin-top: 4px; margin-bottom: 16px; font-size: 16px; color: var(--md-sys-color-on-surface); }
        .back-link { display: inline-block; margin-bottom: 16px; color: var(--md-sys-color-primary); text-decoration: none; font-weight: 500; font-size: 14px; }

        /* --- ★ 本のカードUI一覧スタイル（MD3 Outlined Card仕様） --- */
        .book-card-list {
            display: flex;
            flex-direction: column;
            gap: 14px;
            width: 100%;
        }
        .book-card {
            display: flex;
            flex-direction: row; /* スマホ・PC共通で綺麗な横型配置 */
            text-decoration: none;
            color: inherit;
            border: 1px solid var(--md-sys-color-outline);
            border-radius: 14px;
            background-color: var(--md-sys-color-surface);
            overflow: hidden;
            transition: border-color 0.2s, background-color 0.2s, box-shadow 0.2s;
        }
        .book-card:hover {
            border-color: transparent;
            background-color: var(--md-sys-color-surface-variant);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        /* カード内の画像ラッパー */
        .book-card-image-wrapper {
            width: 80px; /* 一覧画面に適したコンパクトサイズ */
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

        /* 画像エラー時のプレースホルダー */
        .no-image-placeholder {
            display: flex;
            width: 100%;
            height: 100%;
            align-items: center;
            justify-content: center;
            color: var(--md-sys-color-on-surface-variant);
            font-size: 11px;
            font-weight: 500;
            text-align: center;
            line-height: 1.2;
        }

        /* カード内のテキスト詳細情報 */
        .book-card-details {
            flex: 1;
            padding: 12px 14px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-width: 0; /* 長いタイトルのはみ出し防止 */
        }
        .book-card-title {
            font-size: 15px;
            font-weight: 700;
            color: var(--md-sys-color-on-surface);
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis; /* タイトルが長すぎる場合は自動で「...」にする */
        }
        .book-card-meta {
            font-size: 13px;
            color: var(--md-sys-color-on-surface-variant);
            line-height: 1.4;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .book-card-ndc {
            margin-top: 4px;
            display: inline-block;
            font-size: 11px;
            font-weight: 700;
            color: var(--md-sys-color-primary);
            background-color: rgba(26, 115, 232, 0.08);
            padding: 2px 8px;
            border-radius: 4px;
            width: fit-content;
        }

        /* --- PC・タブレット向けのレスポンシブ調整 --- */
        @media (min-width: 768px) {
            .header-inner { padding: 24px 24px 12px 24px; display: flex; justify-content: space-between; align-items: center; }
            .app-title { margin-bottom: 0; font-size: 24px; }
            .nav-item { font-size: 16px; }
            .main-content { padding: 40px 24px; }
            .search-input { padding: 16px 28px; font-size: 17px; }
            .result-header { font-size: 18px; margin-bottom: 24px; }
            .back-link { font-size: 15px; margin-bottom: 20px; }

            /* 大画面ではカードを少し広く、見やすく */
            .book-card-list { gap: 16px; }
            .book-card-image-wrapper { width: 90px; }
            .book-card-details { padding: 16px 20px; }
            .book-card-title { font-size: 18px; margin-bottom: 6px; }
            .book-card-meta { font-size: 14px; }
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
        <a href="../index.php" class="back-link">← ホームに戻る</a>

        <div class="search-container" style="margin-bottom: 24px;">
            <form action="index.php" method="GET">
                <input type="text" name="keyword" class="search-input" placeholder="Search..." autocomplete="off" value="<?php echo htmlspecialchars($search_keyword, ENT_QUOTES, 'UTF-8'); ?>">
            </form>
        </div>

        <div class="result-area" style="flex: 1; display: flex; flex-direction: column;">
            <?php if ($search_keyword !== ''): ?>
                <div class="result-header">
                    「<strong><?php echo htmlspecialchars($search_keyword, ENT_QUOTES, 'UTF-8'); ?></strong>」の検索結果
                </div>
                
                <div class="book-card-list">
                    <?php foreach ($books as $book): ?>
                        <a href="../books?id=<?php echo htmlspecialchars($book['id'], ENT_QUOTES, 'UTF-8'); ?>" class="book-card">
                            
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
                                <h2 class="book-card-title"><?php echo htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
                                <div class="book-card-meta">
                                    <?php echo htmlspecialchars($book['author'], ENT_QUOTES, 'UTF-8'); ?> / 
                                    <?php echo htmlspecialchars($book['publisher'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars($book['year'], ENT_QUOTES, 'UTF-8'); ?>)
                                </div>
                                <div class="book-card-ndc">
                                    NDC: <?php echo htmlspecialchars($book['ndc'], ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                            </div>

                        </a>
                    <?php endforeach; ?>
                </div>

            <?php else: ?>
                <p class="placeholder-text">検索キーワードが入力されていません。</p>
            <?php endif; ?>
        </div>
    </main>

</body>
</html>