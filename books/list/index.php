<?php
require "../../header_session.php";
?>
<?php
$db_host = 'localhost';         // ホスト名 (例: localhost, 127.0.0.1)
$db_name = 'library_app';       // データベース名
$db_user = 'root';              // ユーザー名
$db_pass = '';                  // パスワード

$dsn = "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4";

try {
    // PDOインスタンスの生成
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // エラー発生時に例外を投げる
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // 連想配列としてフェッチ
        PDO::ATTR_EMULATE_PREPARES => false, // 静的プレースホルダを使用
    ]);

    // ==========================================================================
    // ページネーションと検索用のパラメータ処理
    // ==========================================================================
    $per_page = 10; // 1ページあたりの表示件数

    // 現在のページ数を取得
    $current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($current_page < 1) {
        $current_page = 1;
    }

    // 検索キーワードを取得
    $keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

    // ==========================================================================
    // データ件数と蔵書データの取得 (SQLクエリ)
    // ==========================================================================
    // 検索条件の構築 (論理削除されていない有効なデータのみ)
    $where_clauses = ['is_deleted = 0'];
    $params = [];

    if ($keyword !== '') {
        // 本のタイトル、著者、出版社から検索
        $where_clauses[] = '(title LIKE :kw1 OR author LIKE :kw2 OR publisher LIKE :kw3)';
        $params[':kw1'] = "%{$keyword}%";
        $params[':kw2'] = "%{$keyword}%";
        $params[':kw3'] = "%{$keyword}%";
    }

    $where_sql = implode(' AND ', $where_clauses);

    // 総件数を取得して総ページ数を計算
    $count_sql = "SELECT COUNT(*) FROM books WHERE {$where_sql}";
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total_books = $stmt->fetchColumn();
    $total_pages = ceil($total_books / $per_page);

    // 現在のページが最大ページを超えないようにガード
    if ($total_pages > 0 && $current_page > $total_pages) {
        $current_page = $total_pages;
    }

    // 該当するページのデータを取得
    $offset = ($current_page - 1) * $per_page;
    $select_sql = "SELECT id, title, author, publisher, year, ndc, image_url 
                   FROM books 
                   WHERE {$where_sql} 
                   ORDER BY id DESC 
                   LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($select_sql);

    // パラメータをバインド
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $books = $stmt->fetchAll();

} catch (PDOException $e) {
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
        .main-content { flex: 1; width: 100%; max-width: var(--max-content-width); margin: 0 auto; padding: 24px 20px; display: flex; flex-direction: column; gap: 20px; }
        
        .page-header { display: flex; justify-content: space-between; align-items: flex-end; }
        .page-title { font-size: 22px; font-weight: 700; color: var(--md-sys-color-on-surface); }
        .book-count { font-size: 14px; color: var(--md-sys-color-on-surface-variant); }

        /* --- 検索バー --- */
        .search-container {
        }

        .search-input {
            width: 100%;
            padding: 12px 20px;
            font-size: 15px;
            color: var(--md-sys-color-on-surface);
            background-color: var(--md-sys-color-surface-variant);
            border: 1px solid var(--md-sys-color-outline);
            border-radius: 8px;
            outline: none;
            transition: border-color 0.2s;
        }
        
        .search-input:focus {
            border-color: var(--md-sys-color-primary);
            background-color: var(--md-sys-color-surface);
        }

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

        /* --- ページネーション --- */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            margin-top: 24px;
        }

        .page-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 36px;
            height: 36px;
            padding: 0 6px;
            border: 1px solid var(--md-sys-color-outline);
            border-radius: 8px;
            background-color: var(--md-sys-color-surface);
            color: var(--md-sys-color-on-surface);
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            transition: background-color 0.2s;
        }

        .page-btn.active {
            background-color: var(--md-sys-color-primary);
            color: #ffffff;
            border-color: var(--md-sys-color-primary);
            pointer-events: none;
        }

        .page-btn.disabled {
            opacity: 0.4;
            pointer-events: none;
        }

        /* --- PC・タブレット向けのレスポンシブ調整 --- */
        @media (min-width: 768px) {
            .main-content { padding: 40px 24px 60px 24px; gap: 28px; }
            
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
            <div class="book-count">全 <?php echo $total_books; ?> 冊</div>
        </div>

        <div class="search-container">
            <!-- actionを空にすることで、現在のページにGETリクエストを送信 -->
            <form action="" method="GET">
                <input type="text" name="keyword" class="search-input" placeholder="本を検索する..." autocomplete="off" value="<?php echo htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8'); ?>">
            </form>
        </div>

        <div class="book-card-list">
            <?php if (!empty($books)): ?>
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
            <?php else: ?>
                <p style="text-align: center; color: var(--md-sys-color-on-surface-variant); padding: 40px 0;">検索条件に一致する本が見つかりませんでした。</p>
            <?php endif; ?>
        </div>

        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($current_page > 1): ?>
                    <a href="?page=<?php echo $current_page - 1; ?>&keyword=<?php echo urlencode($keyword); ?>" class="page-btn">❮</a>
                <?php else: ?>
                    <span class="page-btn disabled">❮</span>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if ($i === $current_page): ?>
                        <span class="page-btn active"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?page=<?php echo $i; ?>&keyword=<?php echo urlencode($keyword); ?>" class="page-btn"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($current_page < $total_pages): ?>
                    <a href="?page=<?php echo $current_page + 1; ?>&keyword=<?php echo urlencode($keyword); ?>" class="page-btn">❯</a>
                <?php else: ?>
                    <span class="page-btn disabled">❯</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </main>

</body>
</html>