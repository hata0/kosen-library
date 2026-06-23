<?php
// セッションの開始（変更した名前を一時的に保持するため）
session_start();

// 初期値の設定（セッションに未登録の場合のみ初期値をセット）
if (!isset($_SESSION['user_id_code'])) {
    $_SESSION['user_id_code'] = 'k22022ti';
}
if (!isset($_SESSION['user_name'])) {
    $_SESSION['user_name'] = '名無し';
}

// 保存ボタンが押されたときの処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
    if (isset($_POST['user_name'])) {
        // 空白文字のみの入力を防ぐため、トリムして空でなければ更新
        $input_name = trim($_POST['user_name']);
        if ($input_name !== '') {
            $_SESSION['user_name'] = $input_name;
        }
    }
    // フォームの再送信（更新ボタン連打による不具合）を防ぐために自分自身にリダイレクト
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// URLのパラメータ（?mode=edit）を見て、編集モードかどうかを判定
$is_edit_mode = isset($_GET['mode']) && $_GET['mode'] === 'edit';

// XSS対策用関数
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>図書室アプリ - マイページ</title>
    <style>
        /* --- デザインシステム（Material Design 3 ベース） --- */
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

        /* ヘッダー */
        .app-header {
            background-color: var(--md-sys-color-surface);
            border-bottom: 1px solid var(--md-sys-color-outline);
            position: sticky; top: 0; z-index: 10; width: 100%;
        }
        .header-inner {
            max-width: var(--max-content-width); margin: 0 auto; padding: 16px 20px 8px 20px;
        }
        .app-title { font-size: 20px; font-weight: 700; margin-bottom: 12px; }
        .app-nav { display: flex; gap: 24px; }
        .nav-item {
            text-decoration: none; color: var(--md-sys-color-on-surface-variant);
            font-size: 15px; font-weight: 500; padding: 6px 0; position: relative;
        }
        .nav-item.active { color: var(--md-sys-color-on-surface); font-weight: 700; }
        .nav-item.active::after {
            content: ''; position: absolute; bottom: -9px; left: 0; width: 100%;
            height: 3px; background-color: var(--md-sys-color-primary); border-radius: 3px 3px 0 0;
        }

        /* メインコンテンツ */
        .main-content {
            flex: 1; width: 100%; max-width: var(--max-content-width);
            margin: 0 auto; padding: 24px 20px; display: flex; flex-direction: column; gap: 24px;
        }
        .page-title { font-size: 22px; font-weight: 700; margin-bottom: 4px; }

        /* プロフィールカード */
        .profile-card {
            background-color: var(--md-sys-color-surface-variant); border-radius: 16px;
            padding: 20px; border: 1px solid var(--md-sys-color-outline);
        }
        .section-title {
            font-size: 14px; font-weight: 700; color: var(--md-sys-color-primary);
            margin-bottom: 16px; letter-spacing: 0.5px;
        }
        .profile-info { display: flex; flex-direction: column; gap: 16px; }
        .info-row { display: flex; flex-direction: column; gap: 6px; }
        .info-label { font-size: 13px; color: var(--md-sys-color-on-surface-variant); font-weight: 500; }
        .info-value { font-size: 16px; font-weight: 700; }

        /* 入力フォームのスタイル（マテリアルデザインベース） */
        .input-text {
            width: 100%; max-width: 320px; padding: 10px 14px; font-size: 15px;
            color: var(--md-sys-color-on-surface); background-color: #ffffff;
            border: 1px solid var(--md-sys-color-outline); border-radius: 8px; outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .input-text:focus {
            border-color: var(--md-sys-color-primary);
            box-shadow: 0 0 0 3px rgba(26, 115, 232, 0.15);
        }

        /* ボタン関係 */
        .profile-actions { display: flex; gap: 12px; margin-top: 8px; align-items: center; }
        .btn {
            display: inline-flex; align-items: center; justify-content: center;
            padding: 8px 18px; font-size: 13px; font-weight: 600; border-radius: 9999px;
            text-decoration: none; cursor: pointer; transition: background-color 0.2s; border: 1px solid transparent;
        }
        .btn-primary { background-color: var(--md-sys-color-primary); color: #ffffff; }
        .btn-primary:hover { background-color: var(--md-sys-color-primary-hover); }
        .btn-outline { background-color: transparent; color: var(--md-sys-color-primary); border-color: var(--md-sys-color-outline); background-color: #ffffff; }
        .btn-outline:hover { background-color: var(--md-sys-color-surface-variant); }
        .link-cancel { font-size: 13px; color: var(--md-sys-color-on-surface-variant); text-decoration: none; }
        .link-cancel:hover { text-decoration: underline; }

        /* メニューリンク */
        .menu-list { display: flex; flex-direction: column; gap: 12px; }
        .menu-item {
            display: flex; justify-content: space-between; align-items: center;
            padding: 18px 20px; background-color: var(--md-sys-color-surface);
            border: 1px solid var(--md-sys-color-outline); border-radius: 12px;
            text-decoration: none; color: var(--md-sys-color-on-surface); transition: background-color 0.2s;
        }
        .menu-item:hover { background-color: rgba(241, 243, 244, 0.5); }
        .menu-title { font-size: 16px; font-weight: 700; }
        .menu-desc { font-size: 13px; color: var(--md-sys-color-on-surface-variant); margin-top: 2px; }
        .menu-arrow { color: var(--md-sys-color-on-surface-variant); font-size: 18px; font-weight: bold; }

        /* PC向け調整 */
        @media (min-width: 768px) {
            .header-inner { padding: 24px 24px 12px 24px; display: flex; justify-content: space-between; align-items: center; }
            .app-title { margin-bottom: 0; font-size: 24px; }
            .nav-item.active::after { bottom: -13px; }
            .main-content { padding: 40px 24px; gap: 32px; }
            .info-row { flex-direction: row; align-items: center; gap: 24px; }
            .info-label { width: 100px; }
        }
    </style>
</head>
<body>
    <header class="app-header">
        <div class="header-inner">
            <div class="app-title">図書室アプリ</div>
            <nav class="app-nav">
                <a href="../index.php" class="nav-item">ホーム</a>
                <a href="#" class="nav-item active">マイページ</a>
            </nav>
        </div>
    </header>

    <main class="main-content">
        <h1 class="page-title">マイページ</h1>

        <div class="profile-card">
            <div class="section-title">ユーザー情報</div>
            
            <?php if ($is_edit_mode): ?>
                <form action="" method="POST">
                    <input type="hidden" name="action" value="save">
                    <div class="profile-info">
                        <div class="info-row">
                            <span class="info-label">ユーザーID</span>
                            <span class="info-value" style="color: var(--md-sys-color-on-surface-variant);">
                                <?= h($_SESSION['user_id_code']) ?> (変更不可)
                            </span>
                        </div>
                        <div class="info-row">
                            <label for="user_name" class="info-label">ユーザー名</label>
                            <input type="text" id="user_name" name="user_name" class="input-text" value="<?= h($_SESSION['user_name']) ?>" required autocomplete="off">
                        </div>
                        <div class="profile-actions">
                            <button type="submit" class="btn btn-primary">保存する</button>
                            <a href="?mode=view" class="link-cancel">キャンセル</a>
                        </div>
                    </div>
                </form>
            <?php else: ?>
                <div class="profile-info">
                    <div class="info-row">
                        <span class="info-label">ユーザーID</span>
                        <span class="info-value"><?= h($_SESSION['user_id_code']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">ユーザー名</span>
                        <span class="info-value"><?= h($_SESSION['user_name']) ?></span>
                    </div>
                    <div class="profile-actions">
                        <a href="?mode=edit" class="btn btn-outline">編集する</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="menu-list">
            <a href="history/" class="menu-item">
                <div>
                    <div class="menu-title">貸出履歴</div>
                    <div class="menu-desc">過去に借りた本の履歴や返却状況を確認できます</div>
                </div>
                <div class="menu-arrow">&gt;</div>
            </a>
            <a href="articles/" class="menu-item">
                <div>
                    <div class="menu-title">紹介文の管理</div>
                    <div class="menu-desc">自分が作成した本の紹介記事の確認・修正・削除ができます</div>
                </div>
                <div class="menu-arrow">&gt;</div>
            </a>
        </div>
    </main>
</body>
</html>