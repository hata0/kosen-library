<?php
// session_start(); // ← これを削除するか、下の書き方に変更します

// もしセッションが開始されていなければ開始する
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


$error_message = "";

// 🌟 フォームからデータがPOST（送信）されてきた場合のみ、DB接続と認証処理を実行
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 入力値の取得（ユーザーIDは小文字に統一し、前後の不要なスペースを削除）
    $input_user_id = isset($_POST['user_id']) ? strtolower(trim($_POST['user_id'])) : '';
    $input_password = isset($_POST['password']) ? $_POST['password'] : '';

    if (!empty($input_user_id) && !empty($input_password)) {
        try {
            // 2. データベースへの接続設定（root / パスワードなし）
            $dsn = 'mysql:host=localhost;dbname=library_app;charset=utf8mb4';
            $db_user = 'root';
            $db_password = '';

            $pdo = new PDO($dsn, $db_user, $db_password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,          // エラー時に例外を投げる
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,     // 連想配列で結果を取得
            ]);

            // 3. 入力された学籍番号（student_id）に一致するユーザーを検索
            // ※ is_deleted = 0（有効なユーザーのみ）という条件も合わせてチェックします
            $sql = "SELECT * FROM users WHERE student_id = :student_id AND is_deleted = 0 LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':student_id', $input_user_id, PDO::PARAM_STR);
            $stmt->execute();
            
            $user = $stmt->fetch();

            // 4. ユーザーが存在し、パスワードが一致するか検証
            // ※ 現時点のデモデータに合わせ、プレーンテキストのまま比較しています
            if ($user && $input_password === $user['password']) {
                
                // 【ログイン成功】セッションにユーザー情報を記録
                // ★修正箇所：DBのリレーションで使う「連番のid(1, 2...)」をuser_idとして保存する
                $_SESSION['user_id'] = $user['id']; 
                
                // マイページなどでの画面表示用に、学籍番号も別の名前で保持しておくと便利です
                $_SESSION['student_id'] = $user['student_id']; 
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['logged_in'] = true;

                // ホーム画面（index.php）に自動で移動
                header("Location: ../index.php");
                exit();

            } else {
                // 【ログイン失敗】IDまたはパスワードが違う場合
                $error_message = "ユーザーIDまたはパスワードが正しくありません";
            }

        } catch (PDOException $e) {
            // データベースエラー（接続できない、テーブルがない等）が発生した場合
            $error_message = "システムエラーが発生しました。接続状況を確認してください。";
            // 開発用のログ出力（本番では非表示にしますが、デバッグ用に便利です）
            // error_log($e->getMessage()); 
        }
    } else {
        $error_message = "すべての項目を入力してください";
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>図書室アプリ - ログイン</title>
    <style>
        /* --- デザインシステム（元のコードを100%完全維持） --- */
        :root {
            --md-sys-color-primary: #1a73e8;
            --md-sys-color-background: #ffffff;
            --md-sys-color-surface: #ffffff;
            --md-sys-color-on-surface: #1f1f1f;
            --md-sys-color-on-surface-variant: #5f6368;
            --md-sys-color-outline: #e0e0e0;
            --md-sys-color-error: #ba1a1a;
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

        <form class="login-form" action="" method="POST">
            <div class="input-group">
                <label class="input-label" for="user_id">ユーザーID</label>
                <input class="form-input" type="text" id="user_id" name="user_id" autocomplete="off" autocapitalize="none" required placeholder="">
            </div>

            <div class="input-group">
                <label class="input-label" for="password">パスワード</label>
                <input class="form-input" type="password" id="password" name="password" required placeholder="">
            </div>

            <button class="submit-button" type="submit">ログイン</button>
        </form>
    </div>

</body>
</html>