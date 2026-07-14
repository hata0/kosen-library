<?php
require "../header_session.php";
?>
<?php
// =========================================================
// 【ログアウト処理】
// =========================================================
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    // セッション変数をすべて解除
    $_SESSION = array();
    // セッションを破棄
    session_destroy();
    // ログイン画面へリダイレクト（環境に合わせてパスは調整してください）
    header("Location: ../login/index.php");
    exit();
}

// =========================================================
// 【ログインチェック】
// =========================================================
// セッションに user_id が無い（未ログイン）場合はログイン画面へ強制移動
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/index.php");
    exit();
}
$current_user_id = $_SESSION['user_id'];


// データベース接続設定
$db_host = 'localhost';
$db_name = 'library_app'; // sql.txtで定義されたデータベース名
$db_user = 'root';        // ご自身の環境に合わせて変更してください
$db_pass = '';            // ご自身の環境に合わせて変更してください

$error_message = '';
$display_user_id = '不明';
$display_user_name = '不明';

try {
    // PDOによるデータベース接続
    $dsn = "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);

    // ---------------------------------------------------------
    // 1. 保存（UPDATE）処理：フォームから送信された場合
    // ---------------------------------------------------------
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
        if (isset($_POST['user_name'])) {
            // 空白文字のみの入力を防ぐためトリム
            $input_name = trim($_POST['user_name']);
            if ($input_name !== '') {
                // DBのユーザー名(name)を更新
                $update_sql = "UPDATE users SET name = :name WHERE id = :id AND is_deleted = 0";
                $stmt = $pdo->prepare($update_sql);
                $stmt->execute([
                    ':name' => $input_name,
                    ':id'   => $current_user_id
                ]);
            }
        }
        // フォームの再送信を防ぐために自分自身にリダイレクト
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    // ---------------------------------------------------------
    // 2. 取得（SELECT）処理：現在のユーザー情報をDBから読み込む
    // ---------------------------------------------------------
    $select_sql = "SELECT student_id, name FROM users WHERE id = :id AND is_deleted = 0";
    $stmt = $pdo->prepare($select_sql);
    $stmt->execute([':id' => $current_user_id]);
    $user_data = $stmt->fetch();

    if ($user_data) {
        $display_user_id = $user_data['student_id']; // student_idをユーザーIDとして表示
        $display_user_name = $user_data['name'];     // nameをユーザー名として表示
    } else {
        $error_message = 'ユーザー情報が見つかりません。';
    }

} catch (PDOException $e) {
    $error_message = 'データベース接続エラー: ' . $e->getMessage();
}

// URLのパラメータ（?mode=edit）を見て、編集モードかどうかを判定
$is_edit_mode = isset($_GET['mode']) && $_GET['mode'] === 'edit';

// XSS対策用関数
function h($string) {
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
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
            --md-sys-color-error: #b3261e; /* エラー用の赤色を追加 */
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

        /* メインコンテンツ */
        .main-content {
            flex: 1; width: 100%; max-width: var(--max-content-width);
            margin: 0 auto; padding: 24px 20px; display: flex; flex-direction: column; gap: 24px;
        }
        .page-title { font-size: 22px; font-weight: 700; margin-bottom: 4px; }

        /* アラート */
        .alert-error {
            padding: 16px; background-color: #fdeded; color: #d32f2f;
            border-radius: 8px; font-size: 14px; font-weight: 500;
        }

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

        /* 入力フォームのスタイル */
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

        /* ログアウトボタン専用スタイル */
        .menu-item-logout {
            justify-content: center;
            color: var(--md-sys-color-error);
            font-weight: 700;
            background-color: transparent;
            margin-top: 16px;
        }
        .menu-item-logout:hover {
            background-color: rgba(179, 38, 30, 0.08); /* うっすら赤く光る */
        }

        /* PC向け調整 */
        @media (min-width: 768px) {
            .main-content { padding: 40px 24px; gap: 32px; }
            .info-row { flex-direction: row; align-items: center; gap: 24px; }
            .info-label { width: 100px; }
        }
    </style>
    <link rel="stylesheet" href="../header.css">
    <link rel="stylesheet" href="../back-link.css">
</head>
<body>
    <?php
    require "../header.php";
    ?>

    <main class="main-content">
        <?php
        require "../back_link.php";
        ?>

        <h1 class="page-title">マイページ</h1>

        <?php if ($error_message): ?>
            <div class="alert-error"><?= h($error_message) ?></div>
        <?php endif; ?>

        <div class="profile-card">
            <div class="section-title">ユーザー情報</div>
            
            <?php if ($is_edit_mode && empty($error_message)): ?>
                <form action="" method="POST">
                    <input type="hidden" name="action" value="save">
                    <div class="profile-info">
                        <div class="info-row">
                            <span class="info-label">ユーザーID</span>
                            <span class="info-value" style="color: var(--md-sys-color-on-surface-variant);">
                                <?= h($display_user_id) ?> (変更不可)
                            </span>
                        </div>
                        <div class="info-row">
                            <label for="user_name" class="info-label">ユーザー名</label>
                            <input type="text" id="user_name" name="user_name" class="input-text" value="<?= h($display_user_name) ?>" required autocomplete="off">
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
                        <span class="info-value"><?= h($display_user_id) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">ユーザー名</span>
                        <span class="info-value"><?= h($display_user_name) ?></span>
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

            <a href="?action=logout" class="menu-item menu-item-logout" onclick="return confirm('本当にログアウトしますか？');">
                ログアウト
            </a>
        </div>
    </main>
</body>
</html>