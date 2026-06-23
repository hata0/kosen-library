<?php
$search_keyword = '';
// パラメータ ?q= の値を取得
if (isset($_GET['keyword']) && trim($_GET['keyword']) !== '') {
    $search_keyword = trim($_GET['keyword']);
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>検索結果 - 図書室アプリ</title>
    <style>
        :root {
            --md-sys-color-primary: #1a73e8;
            --md-sys-color-background: #ffffff;
            --md-sys-color-surface: #ffffff;
            --md-sys-color-on-surface: #1f1f1f;
            --md-sys-color-on-surface-variant: #5f6368;
            --md-sys-color-outline: #e0e0e0;
            --max-content-width: 760px;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; -webkit-tap-highlight-color: transparent; }
        body { font-family: 'Helvetica Neue', Arial, 'Hiragino Kaku Gothic ProN', 'Hiragino Sans', Meiryo, sans-serif; background-color: var(--md-sys-color-background); color: var(--md-sys-color-on-surface); min-height: 100vh; display: flex; flex-direction: column; }
        
        .app-header { background-color: var(--md-sys-color-surface); border-bottom: 1px solid var(--md-sys-color-outline); position: sticky; top: 0; z-index: 10; width: 100%; }
        .header-inner { max-width: var(--max-content-width); margin: 0 auto; padding: 16px 20px 8px 20px; }
        .app-title { font-size: 20px; font-weight: 700; color: var(--md-sys-color-on-surface); margin-bottom: 12px; }
        .app-nav { display: flex; gap: 24px; }
        .nav-item { text-decoration: none; color: var(--md-sys-color-on-surface-variant); font-size: 15px; font-weight: 500; padding: 6px 0; position: relative; }
        .nav-item.active { color: var(--md-sys-color-on-surface); font-weight: 700; }
        
        .main-content { flex: 1; width: 100%; max-width: var(--max-content-width); margin: 0 auto; padding: 24px 20px; display: flex; flex-direction: column; }
        
        .search-container { width: 100%; }
        .search-input { width: 100%; padding: 14px 24px; font-size: 16px; color: var(--md-sys-color-on-surface); background-color: #f1f3f4; border: 1px solid transparent; border-radius: 9999px; outline: none; transition: 0.2s; }
        .search-input:focus { background-color: var(--md-sys-color-surface); border-color: var(--md-sys-color-primary); box-shadow: 0 1px 6px rgba(32, 33, 36, 0.1); }
        .future-content-space { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; border: 2px dashed var(--md-sys-color-outline); border-radius: 16px; padding: 40px 20px; background-color: rgba(248, 249, 250, 0.5); }
        .placeholder-text { color: var(--md-sys-color-on-surface-variant); font-size: 14px; text-align: center; line-height: 1.6; }
        
        /* 検索結果画面専用の追加スタイル */
        .result-header { margin-top: 4px; margin-bottom: 16px; font-size: 16px; color: var(--md-sys-color-on-surface); }
        .back-link { display: inline-block; margin-bottom: 16px; color: var(--md-sys-color-primary); text-decoration: none; font-weight: 500; font-size: 14px; }

        /* PC・タブレット向けのレスポンシブ調整 */
        @media (min-width: 768px) {
            .header-inner { padding: 24px 24px 12px 24px; display: flex; justify-content: space-between; align-items: center; }
            .app-title { margin-bottom: 0; font-size: 24px; }
            .nav-item { font-size: 16px; }
            .main-content { padding: 40px 24px; }
            .search-input { padding: 16px 28px; font-size: 17px; }
            .result-header { font-size: 18px; margin-bottom: 24px; }
            .back-link { font-size: 15px; margin-bottom: 20px; }
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
                
                <div class="future-content-space">
                    <p class="placeholder-text">
                        ここにデータベースから取得した<br>該当する本の一覧（カードUI）がレスポンシブに並びます。
                    </p>
                </div>
            <?php else: ?>
                <p class="placeholder-text">検索キーワードが入力されていません。</p>
            <?php endif; ?>
        </div>
    </main>

</body>
</html>