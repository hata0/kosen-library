<?php
// セッションの開始
session_start();

// ログイン状態によって右上のナビゲーションを切り替える
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    $nav_text = "マイページ";
    $nav_link = "/kosen-library/mypage/index.php";
} else {
    $nav_text = "ログイン";
    $nav_link = "/kosen-library/login/index.php";
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ページが見つかりません - 図書室アプリ</title>
    <style>
        /* --- デザインシステム --- */
        :root {
            --md-sys-color-primary: #1a73e8;
            --md-sys-color-primary-hover: #1557b0;
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
        .nav-item { text-decoration: none; color: var(--md-sys-color-on-surface-variant); font-size: 15px; font-weight: 500; padding: 6px 0; transition: color 0.2s; }
        .nav-item:hover { color: var(--md-sys-color-primary); }

        /* --- メインコンテンツ（エラー表示部分） --- */
        .main-content {
            flex: 1; width: 100%; max-width: var(--max-content-width);
            margin: 0 auto; padding: 40px 20px;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            text-align: center;
        }

        .error-code {
            font-size: 80px;
            font-weight: 900;
            color: var(--md-sys-color-primary);
            line-height: 1;
            margin-bottom: 16px;
            letter-spacing: -2px;
        }

        .error-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--md-sys-color-on-surface);
            margin-bottom: 24px;
        }

        /* ★ 追加：GIF画像用のスタイル */
        .gif-container {
            margin-bottom: 24px;
        }
        .error-gif {
            width: 100%;
            max-width: 280px; /* GIFの最大サイズ（お好みで調整してください） */
            height: auto;
            border-radius: 16px; /* 角を少し丸める */
        }

        .error-desc {
            font-size: 15px;
            color: var(--md-sys-color-on-surface-variant);
            line-height: 1.6;
            margin-bottom: 40px;
        }

        .btn-home {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 14px 32px;
            font-size: 15px;
            font-weight: 700;
            color: #ffffff;
            background-color: var(--md-sys-color-primary);
            border-radius: 9999px;
            text-decoration: none;
            transition: background-color 0.2s, box-shadow 0.2s;
            box-shadow: 0 2px 4px rgba(26, 115, 232, 0.2);
        }

        .btn-home:hover {
            background-color: var(--md-sys-color-primary-hover);
            box-shadow: 0 4px 8px rgba(26, 115, 232, 0.3);
        }

        .btn-home:active {
            transform: scale(0.98);
            box-shadow: none;
        }

        @media (min-width: 768px) {
            .header-inner { padding: 24px 24px 12px 24px; display: flex; justify-content: space-between; align-items: center; }
            .app-title { margin-bottom: 0; font-size: 24px; }
            .error-code { font-size: 120px; }
            .error-title { font-size: 28px; }
            .error-gif { max-width: 320px; } /* PC画面では少し大きくする */
        }
    </style>
</head>
<body>

    <header class="app-header">
        <div class="header-inner">
            <div class="app-title">図書室アプリ</div>
            <nav class="app-nav">
                <a href="/kosen-library/index.php" class="nav-item">ホーム</a>
                <a href="<?= htmlspecialchars($nav_link, ENT_QUOTES, 'UTF-8') ?>" class="nav-item"><?= htmlspecialchars($nav_text, ENT_QUOTES, 'UTF-8') ?></a>
            </nav>
        </div>
    </header>

    <main class="main-content">
        <div class="error-code">404</div>
        <h1 class="error-title">ページが見つかりません</h1>

        <!-- ★ 追加：GIF画像の配置エリア -->
        <div class="gif-container">
            <!-- srcの "your-animation.gif" の部分を、ご自身で用意したGIFのパスに書き換えてください -->
            <!-- ネット上のフリーGIF等のURLを直接指定する（https://...）ことも可能です -->
            <img src="/kosen-library/video_collection/atsushi.gif" alt="404 Error Animation" class="error-gif">
        </div>

        <p class="error-desc">
            お探しのページは削除されたか、URLが変更された可能性があります。<br>
            正しいURLを入力したか再度ご確認ください。
        </p>
        <a href="/kosen-library/index.php" class="btn-home">ホーム画面に戻る</a>
    </main>

</body>
</html>