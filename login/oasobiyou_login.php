<?php
// 1. セッションの開始
session_start();

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
            width: max-content;
            margin: 12px auto 0 auto;
            padding: 10px 24px;
            font-size: 14px;
            font-weight: 700;
            color: #ffffff;
            background-color: var(--md-sys-color-primary);
            border: none;
            border-radius: 9999px;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(26, 115, 232, 0.2);
            
            position: relative;
            z-index: 5;
            transition: background-color 0.2s, box-shadow 0.2s, left 0.1s ease-out, top 0.1s ease-out;
        }

        /* 🌟 追加：最終確認モーダルのスタイル */
        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex; justify-content: center; align-items: center;
            z-index: 100; opacity: 0; pointer-events: none;
            transition: opacity 0.3s ease;
        }
        .modal-overlay.active { opacity: 1; pointer-events: auto; }

        .modal-content {
            background-color: #ffffff; padding: 24px; border-radius: 16px;
            width: 90%; max-width: 320px; text-align: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            transform: scale(0.9); transition: transform 0.3s ease;
        }
        .modal-overlay.active .modal-content { transform: scale(1); }

        .modal-title { font-size: 16px; font-weight: 700; margin-bottom: 20px; }
        .modal-buttons { display: flex; gap: 12px; justify-content: center; }
        
        .fake-btn {
            flex: 1; padding: 12px; font-size: 14px; font-weight: 700;
            border-radius: 9999px; cursor: pointer; border: none;
            background-color: var(--md-sys-color-surface-variant);
            color: var(--md-sys-color-on-surface);
            transition: background-color 0.2s;
        }
        .fake-btn:active { background-color: #e0e0e0; }
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

        <!-- 🌟 IDを付与してJavaScriptから制御しやすく変更 -->
        <form id="loginForm" class="login-form" action="" method="POST">
            <div class="input-group">
                <label class="input-label" for="user_id">ユーザーID</label>
                <input class="form-input" type="text" id="user_id" name="user_id" autocomplete="off" autocapitalize="none" required placeholder="">
            </div>

            <div class="input-group">
                <label class="input-label" for="password">パスワード</label>
                <input class="form-input" type="password" id="password" name="password" required placeholder="">
            </div>

            <!-- 🌟 type="button" にして勝手に送信されるのをガード -->
            <button class="submit-button" type="button" id="triggerBtn">ログイン</button>
        </form>
    </div>

    <!-- 🌟 最終確認用の2択モーダル（見た目は完全に同じ「いいえ」） -->
    <div class="modal-overlay" id="confirmModal">
        <div class="modal-content">
            <div class="modal-title">本当にログインしますか？</div>
            <div class="modal-buttons">
                <button type="button" class="fake-btn" id="btnLeft">どっちでしょ</button>
                <button type="button" class="fake-btn" id="btnRight">どっちでしょ</button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const escapeBtn = document.getElementById('triggerBtn');
            const loginForm = document.getElementById('loginForm');
            const modal = document.getElementById('confirmModal');
            const btnLeft = document.getElementById('btnLeft');
            const btnRight = document.getElementById('btnRight');

            // 1. 【おさらい】マウスから逃げるシステム
            escapeBtn.addEventListener('mouseover', () => {
                const moveRangeX = 150; 
                const moveRangeY = 100; 
                const randomX = (Math.random() - 0.5) * 2 * moveRangeX;
                const randomY = (Math.random() - 0.5) * 2 * moveRangeY;
                escapeBtn.style.left = `${randomX}px`;
                escapeBtn.style.top = `${randomY}px`;
            });

            // 2. 苦労してボタンを押せたら、最終確認モーダルを表示
            escapeBtn.addEventListener('click', () => {
                // 入力欄の簡易チェック（空ならブラウザ標準の警告を出す）
                if (!loginForm.checkValidity()) {
                    loginForm.reportValidity();
                    return;
                }
                
                // 🌟 毎回左右どちらが「本物（ログイン）」になるかをランダムに決定
                const isLeftTrue = Math.random() < 0.5;
                
                // いったん両方のイベントをクリアして再設定
                btnLeft.onclick = null;
                btnRight.onclick = null;

                if (isLeftTrue) {
                    btnLeft.onclick = () => loginForm.submit(); // 左が本物
                    btnRight.onclick = () => closeModal();       // 右はただ閉じるだけ
                } else {
                    btnLeft.onclick = () => closeModal();       // 左はただ閉じるだけ
                    btnRight.onclick = () => loginForm.submit(); // 右が本物
                }

                // モーダルを表示
                modal.classList.add('active');
            });

            function closeModal() {
                modal.classList.remove('active');
                // 逃げ回ったボタンの位置をリセットしてあげる（優しさ）
                escapeBtn.style.left = '0px';
                escapeBtn.style.top = '0px';
            }
        });
    </script>

</body>
</html>