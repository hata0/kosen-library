<?php
require "../header_session.php";
?>
<?php
$search_keyword = '';
$books = []; // 検索結果を格納する配列

// パラメータ ?keyword= の値を取得
if (isset($_GET['keyword']) && trim($_GET['keyword']) !== '') {
    $search_keyword = trim($_GET['keyword']);
    
    // 2. データベースへの接続設定（root / パスワードなし）
    $dsn = 'mysql:host=localhost;dbname=library_app;charset=utf8mb4';
    $db_user = 'root';
    $db_password = '';

    try {
        $pdo = new PDO($dsn, $db_user, $db_password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        // 3. SQL文の作成（タイトル、著者、出版社からあいまい検索）
        // プレースホルダー（:keyword）を使い、SQLインジェクションを完全に防ぎます
        $sql = "SELECT * FROM books 
                WHERE is_deleted = 0 
                AND (title LIKE :keyword OR author LIKE :keyword OR publisher LIKE :keyword)
                ORDER BY id DESC"; // 新しい登録順に並べる
        
        $stmt = $pdo->prepare($sql);
        
        // 部分一致検索のためにキーワードの前後を % で囲む
        $like_keyword = '%' . $search_keyword . '%';
        $stmt->bindValue(':keyword', $like_keyword, PDO::PARAM_STR);
        
        $stmt->execute();
        $books = $stmt->fetchAll();

    } catch (PDOException $e) {
        exit('データベース接続エラー: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
    }
}

// 4. ログイン状態によって右上のナビゲーションの文字とリンク先を切り替える
// ※ search/ フォルダ内にいるため、上の階層（../）を意識したパスにしています
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    $nav_text = "マイページ";
    $nav_link = "../mypage/index.php";
} else {
    $nav_text = "ログイン";
    $nav_link = "../login/index.php"; 
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>検索結果 - 図書室アプリ</title>
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
        
        .search-container { width: 100%; }
        .search-input { width: 100%; padding: 14px 24px; font-size: 16px; color: var(--md-sys-color-on-surface); background-color: #f1f3f4; border: 1px solid transparent; border-radius: 9999px; outline: none; transition: 0.2s; }
        .search-input:focus { background-color: var(--md-sys-color-surface); border-color: var(--md-sys-color-primary); box-shadow: 0 1px 6px rgba(32, 33, 36, 0.1); }
        
        .placeholder-text { color: var(--md-sys-color-on-surface-variant); font-size: 14px; text-align: center; line-height: 1.6; padding: 40px 20px; }
        .result-header { margin-top: 4px; margin-bottom: 16px; font-size: 16px; color: var(--md-sys-color-on-surface); }
        .back-link { display: inline-block; margin-bottom: 16px; color: var(--md-sys-color-primary); text-decoration: none; font-weight: 500; font-size: 14px; }

        /* --- ★ 本のカードUI一覧スタイル（MD3 Outlined Card仕様） --- */
        .book-card-list {
            display: flex;
            flex-direction: column;
            gap: 14px;
            width: 100%;
        }
        .book-card {
            display: flex;
            flex-direction: row; 
            text-decoration: none;
            color: inherit;
            border: 1px solid var(--md-sys-color-outline);
            border-radius: 14px;
            background-color: var(--md-sys-color-surface);
            overflow: hidden;
            transition: border-color 0.2s, background-color 0.2s, box-shadow 0.2s;
        }
        .book-card:hover {
            border-color: transparent;
            background-color: var(--md-sys-color-surface-variant);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .book-card-image-wrapper {
            width: 80px; 
            aspect-ratio: 2 / 3;
            position: relative;
            flex-shrink: 0;
            background-color: var(--md-sys-color-surface-variant);
            border-right: 1px solid var(--md-sys-color-outline);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .book-card-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .no-image-placeholder {
            display: flex;
            width: 100%;
            height: 100%;
            align-items: center;
            justify-content: center;
            color: var(--md-sys-color-on-surface-variant);
            font-size: 11px;
            font-weight: 500;
            text-align: center;
            line-height: 1.2;
        }

        .book-card-details {
            flex: 1;
            padding: 12px 14px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-width: 0; 
        }
        .book-card-title {
            font-size: 15px;
            font-weight: 700;
            color: var(--md-sys-color-on-surface);
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis; 
        }
        .book-card-meta {
            font-size: 13px;
            color: var(--md-sys-color-on-surface-variant);
            line-height: 1.4;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .book-card-ndc {
            margin-top: 4px;
            display: inline-block;
            font-size: 11px;
            font-weight: 700;
            color: var(--md-sys-color-primary);
            background-color: rgba(26, 115, 232, 0.08);
            padding: 2px 8px;
            border-radius: 4px;
            width: fit-content;
        }

        @media (min-width: 768px) {
            .main-content { padding: 40px 24px; }
            .search-input { padding: 16px 28px; font-size: 17px; }
            .result-header { font-size: 18px; margin-bottom: 24px; }
            .back-link { font-size: 15px; margin-bottom: 20px; }

            .book-card-list { gap: 16px; }
            .book-card-image-wrapper { width: 90px; }
            .book-card-details { padding: 16px 20px; }
            .book-card-title { font-size: 18px; margin-bottom: 6px; }
            .book-card-meta { font-size: 14px; }
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

        <div class="search-container" style="margin-bottom: 24px;">
            <form action="index.php" method="GET">
                <input type="text" name="keyword" class="search-input" placeholder="本を検索する..." autocomplete="off" value="<?php echo htmlspecialchars($search_keyword, ENT_QUOTES, 'UTF-8'); ?>">
            </form>
        </div>

        <div class="result-area" style="flex: 1; display: flex; flex-direction: column;">
            <?php if ($search_keyword !== ''): ?>
                <div class="result-header">
                    「<strong><?php echo htmlspecialchars($search_keyword, ENT_QUOTES, 'UTF-8'); ?></strong>」の検索結果 (<?php echo count($books); ?>件)
                </div>
                
                <?php if (!empty($books)): ?>
                    <div class="book-card-list">
                        <?php foreach ($books as $book): ?>
                            <a href="../books/index.php?id=<?php echo htmlspecialchars($book['id'], ENT_QUOTES, 'UTF-8'); ?>" class="book-card">
                                
                                <div class="book-card-image-wrapper">
                                    <?php if (!empty($book['image_url']) && file_exists('../' . $book['image_url'])): ?>
                                        <img src="<?php echo htmlspecialchars('../' . $book['image_url'], ENT_QUOTES, 'UTF-8'); ?>" 
                                             alt="<?php echo htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8'); ?>のカバー画像" 
                                             class="book-card-image">
                                    <?php else: ?>
                                        <div class="no-image-placeholder">No Image</div>
                                    <?php endif; ?>
                                </div>

                                <div class="book-card-details">
                                    <h2 class="book-card-title"><?php echo htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
                                    <div class="book-card-meta">
                                        <?php echo htmlspecialchars($book['author'], ENT_QUOTES, 'UTF-8'); ?> / 
                                        <?php echo htmlspecialchars($book['publisher'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars($book['year'], ENT_QUOTES, 'UTF-8'); ?>年)
                                    </div>
                                    <?php if (!empty($book['ndc'])): ?>
                                        <div class="book-card-ndc">
                                            NDC: <?php echo htmlspecialchars($book['ndc'], ENT_QUOTES, 'UTF-8'); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="placeholder-text">一致する図書が見つかりませんでした。</p>
                <?php endif; ?>

            <?php else: ?>
                <p class="placeholder-text">検索キーワードが入力されていません。</p>
            <?php endif; ?>
        </div>
    </main>

</body>
</html>