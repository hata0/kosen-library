<?php
// エラーメッセージがURLパラメータ（?error=1）で渡ってきたら受け取る
$error_message = "";
if (isset($_GET['error']) && $_GET['error'] == 1) {
    $error_message = "ログインに失敗しました";
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>図書室アプリ - ログイン</title>
    <style>
        /* --- デザインシステム（index.php と完全統一） --- */
        :root {
            --md-sys-color-primary: #1a73e8;
            --md-sys-color-background: #ffffff;
            --md-sys-color-surface: #ffffff;
            --md-sys-color-on-surface: #1f1f1f;
            --md-sys-color-on-surface-variant: #5f6368;
            --md-sys-color-outline: #e0e0e0;
            --md-sys-color-error: #ba1a1a; /* エラー用の赤色を追加 */
            --md-sys-color-error-container: #ffdad6;
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
            justify-content: center;
            align-items: center;
            position: relative;
        }

        /* --- ホームに戻る「←」ボタン --- */
        .back-button {
            position: absolute;
            top: 16px;
            left: 16px;
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            color: var(--md-sys-color-on-surface-variant);
            font-size: 24px;
            font-weight: 300;
            border-radius: 50%;
            transition: background-color 0.2s;
        }

        .back-button:active {
            background-color: #f1f3f4;
        }

        /* --- ログインコンテナ --- */
        .login-container {
            width: 100%;
            max-width: 400px;
            padding: 40px 24px;
            display: flex;
            flex-direction: column;
            gap: 32px;
        }

        .login-header {
            text-align: center;
        }

        .app-title {
            font-size: 28px;
            font-weight: 700;
            color: var(--md-sys-color-on-surface);
            margin-bottom: 8px;
        }

        .app-subtitle {
            font-size: 14px;
            color: var(--md-sys-color-on-surface-variant);
        }

        /* --- エラーメッセージのスタイル --- */
        .error-banner {
            background-color: var(--md-sys-color-error-container);
            color: var(--md-sys-color-error);
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            text-align: center;
        }

        /* --- フォームと入力エリア --- */
        .login-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .input-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .input-label {
            font-size: 14px;
            font-weight: 500;
            color: var(--md-sys-color-on-surface-variant);
            padding-left: 4px;
        }

        .form-input {
            width: 100%;
            padding: 16px;
            font-size: 16px;
            color: var(--md-sys-color-on-surface);
            background-color: #ffffff;
            border: 1px solid var(--md-sys-color-outline);
            border-radius: 12px;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-input:focus {
            border-color: var(--md-sys-color-primary);
            box-shadow: 0 0 0 3px rgba(26, 115, 232, 0.15);
        }

        /* --- ボタン --- */
        .submit-button {
            width: 100%;
            padding: 16px 24px;
            font-size: 16px;
            font-weight: 700;
            color: #ffffff;
            background-color: var(--md-sys-color-primary);
            border: none;
            border-radius: 9999px;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(26, 115, 232, 0.2);
            transition: background-color 0.2s, box-shadow 0.2s;
            margin-top: 12px;
        }

        .submit-button:active {
            background-color: #1557b0;
            box-shadow: none;
        }
    </style>
</head>
<body>

    <a href="../index.php" class="back-button" aria-label="ホームに戻る">←</a>

    <div class="login-container">
        <div class="login-header">
            <h1 class="app-title">図書室アプリ</h1>
            <p class="app-subtitle">アカウントにログインしてください</p>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="error-banner"><?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <form class="login-form" action="login_process.php" method="POST">
            <div class="input-group">
                <label class="input-label" for="user_id">ユーザーID</label>
                <input class="form-input" type="text" id="user_id" name="user_id" autocomplete="off" required>
            </div>

            <div class="input-group">
                <label class="input-label" for="password">パスワード</label>
                <input class="form-input" type="password" id="password" name="password" required>
            </div>

            <button class="submit-button" type="submit">ログイン</button>
        </form>
    </div>

</body>
</html>