<?php
require "../../header_session.php";
?>
<?php
$db_host = 'localhost';         // ホスト名 (例: localhost, 127.0.0.1)
$db_name = 'library_app';// データベース名
$db_user = 'root';     // ユーザー名
$db_pass = '';     // パスワード

$dsn = "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4";

try {
    // PDOインスタンスの生成
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // エラー発生時に例外を投げる
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // 連想配列としてフェッチ
        PDO::ATTR_EMULATE_PREPARES => false, // 静的プレースホルダを使用
    ]);

    // --- 【データ取得処理】 ---
    // is_deleted = 0 (有効なデータ) のみを取得。必要に応じて ORDER BY や LIMIT を追加してください。
    $sql = "SELECT id, title, author, publisher, year, ndc, image_url 
            FROM books 
            WHERE is_deleted = 0 
            ORDER BY id DESC";
    
    $stmt = $pdo->query($sql);
    $books = $stmt->fetchAll();

} catch (PDOException $e) {
    // データベース接続やクエリ実行に失敗した場合の処理
    // ※本番環境ではエラー内容を画面に直接出力せず、ログに記録することをお勧めします
    exit('データベースエラー: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>蔵書一覧 - 図書室アプリ</title>
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
        
        /* --- メインコンテンツ --- */
        .main-content { flex: 1; width: 100%; max-width: var(--max-content-width); margin: 0 auto; padding: 24px 20px; display: flex; flex-direction: column; }
        
        .page-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 20px; }
        .page-title { font-size: 22px; font-weight: 700; color: var(--md-sys-color-on-surface); }
        .book-count { font-size: 14px; color: var(--md-sys-color-on-surface-variant); }

        /* --- 本のカードUI一覧スタイル --- */
        .book-card-list { display: flex; flex-direction: column; gap: 14px; width: 100%; }
        .book-card {
            display: flex; flex-direction: row; text-decoration: none; color: inherit;
            border: 1px solid var(--md-sys-color-outline); border-radius: 14px;
            background-color: var(--md-sys-color-surface); overflow: hidden; transition: 0.2s;
        }
        .book-card:hover { border-color: transparent; background-color: var(--md-sys-color-surface-variant); box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05); }

        .book-card-image-wrapper { width: 80px; aspect-ratio: 2 / 3; position: relative; flex-shrink: 0; background-color: var(--md-sys-color-surface-variant); border-right: 1px solid var(--md-sys-color-outline); }
        .book-card-image { width: 100%; height: 100%; object-fit: cover; }
        .no-image-placeholder { display: flex; width: 100%; height: 100%; align-items: center; justify-content: center; color: var(--md-sys-color-on-surface-variant); font-size: 11px; font-weight: 500; }

        .book-card-details { flex: 1; padding: 12px 14px; display: flex; flex-direction: column; justify-content: center; min-width: 0; }
        .book-card-title { font-size: 15px; font-weight: 700; color: var(--md-sys-color-on-surface); margin-bottom: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .book-card-meta { font-size: 13px; color: var(--md-sys-color-on-surface-variant); line-height: 1.4; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .book-card-ndc { margin-top: 4px; display: inline-block; font-size: 11px; font-weight: 700; color: var(--md-sys-color-primary); background-color: rgba(26, 115, 232, 0.08); padding: 2px 8px; border-radius: 4px; width: fit-content; }

        /* --- PC・タブレット向けのレスポンシブ調整 --- */
        @media (min-width: 768px) {
            .main-content { padding: 40px 24px; }
            
            .page-title { font-size: 26px; }
            .book-card-list { gap: 16px; }
            .book-card-image-wrapper { width: 90px; }
            .book-card-details { padding: 16px 20px; }
            .book-card-title { font-size: 18px; margin-bottom: 6px; }
            .book-card-meta { font-size: 14px; }
        }
    </style>
    <link rel="stylesheet" href="../../header.css">
    <link rel="stylesheet" href="../../back-link.css">
</head>
<body>

    <?php
    require "../../header.php";
    ?>

    <main class="main-content">
        <?php
        require "../../back_link.php";
        ?>

        <div class="page-header">
            <h1 class="page-title">新着本一覧</h1>
            <div class="book-count">全 <?php echo count($books); ?> 冊</div>
        </div>

        <div class="book-card-list">
            <?php foreach ($books as $book): ?>
                <a href="../index.php?id=<?php echo htmlspecialchars($book['id'], ENT_QUOTES, 'UTF-8'); ?>" class="book-card">
                    
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

    </main>

</body>
</html>
