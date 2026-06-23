<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>図書室アプリ - ホーム</title>
    <style>
        /* --- デザインシステム（Material Design 3 ベース） --- */
        :root {
            --md-sys-color-primary: #1a73e8;
            --md-sys-color-background: #ffffff;
            --md-sys-color-surface: #ffffff;
            --md-sys-color-on-surface: #1f1f1f;
            --md-sys-color-on-surface-variant: #5f6368;
            --md-sys-color-outline: #e0e0e0;
            --max-content-width: 760px; /* PC・タブレットでのコンテンツ最大幅 */
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

        /* --- ヘッダー（画面幅100%に広がるレスポンシブ設計） --- */
        .app-header {
            background-color: var(--md-sys-color-surface);
            border-bottom: 1px solid var(--md-sys-color-outline);
            position: sticky;
            top: 0;
            z-index: 10;
            width: 100%;
        }

        /* ヘッダー内部を中央寄せし、左右の余白を管理 */
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

        /* ナビゲーション（横並び） */
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

        /* アクティブ項目の下線表示 */
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

        /* --- メインコンテンツエリア（中央配置） --- */
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

        /* 検索バー（カプセル型） */
        .search-container {
            width: 100%;
        }

        .search-input {
            width: 100%;
            padding: 14px 24px;
            font-size: 16px;
            color: var(--md-sys-color-on-surface);
            background-color: #f1f3f4; /* どんな背景にも馴染むライトグレー */
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

        /* 将来のコンテンツ追加用フリースペース */
        .future-content-space {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border: 2px dashed var(--md-sys-color-outline);
            border-radius: 16px;
            padding: 60px 20px;
            background-color: rgba(248, 249, 250, 0.5);
        }

        .placeholder-text {
            color: var(--md-sys-color-on-surface-variant);
            font-size: 14px;
            text-align: center;
            line-height: 1.6;
        }

        /* ==========================================================================
           タブレット・PC向けのレスポンシブ調整 (ブレイクポイント: 768px以上)
           ========================================================================== */
        @media (min-width: 768px) {
            .header-inner {
                padding: 24px 24px 12px 24px;
                display: flex;
                justify-content: space-between; /* タイトルとナビを両端に分ける */
                align-items: center;
            }

            .app-title {
                margin-bottom: 0; /* PCでは縦積みを解消 */
                font-size: 24px;
            }

            .nav-item {
                font-size: 16px;
            }

            .nav-item.active::after {
                bottom: -13px; /* 横並び時の下線位置を綺麗にフィットさせる */
            }

            .main-content {
                padding: 40px 24px;
                gap: 32px;
            }

            .search-input {
                padding: 16px 28px; /* 大画面向けに少しゆったりとしたサイズに */
                font-size: 17px;
            }

            .placeholder-text {
                font-size: 15px;
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
                <a href="#" class="nav-item">マイページ</a>
            </nav>
        </div>
    </header>

    <main class="main-content">
        <div class="search-container">
            <form action="search/index.php" method="GET">
                <input type="text" name="keyword" class="search-input" placeholder="Search..." autocomplete="off">
            </form>
        </div>

        <div class="future-content-space">
            <p class="placeholder-text">
                ここに将来、お知らせや<br>
                任意の追加コンテンツを組み込むことができます。
            </p>
        </div>
    </main>

</body>
</html>
