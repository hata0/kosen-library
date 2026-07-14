<?php
// ==========================================================================
// 1. データベース接続設定
// ==========================================================================
$dsn = 'mysql:host=localhost;dbname=library_app;charset=utf8mb4';
$user = 'root';
$password = ''; 

try {
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    exit('データベース接続失敗: ' . $e->getMessage());
}

// ==========================================================================
// 2. Gemini APIの設定 (★ご自身のAPIキーに書き換えてください)
// ==========================================================================
// TODO: fix
define('GEMINI_API_KEY', '');

// ==========================================================================
// 3. AI選書ロジック
// ==========================================================================
$recommended_book = null;
$ai_comment = '';
$profile_text = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['profile'])) {
    $profile_text = trim($_POST['profile']);

    if ($profile_text !== '') {
        // ① 図書室にある本（選択肢）の一覧をデータベースから取得
        $books_stmt = $pdo->query("SELECT id, title, author FROM books WHERE is_deleted = 0");
        $all_books = $books_stmt->fetchAll();

        // AIに渡すための本のテキストリストを作成
        $books_list_str = "";
        foreach ($all_books as $b) {
            $books_list_str .= "ID: " . $b['id'] . " | タイトル: " . $b['title'] . " | 著者: " . $b['author'] . "\n";
        }

        // ② AI（Gemini）への指示文（プロンプト）を作成
        $prompt = "あなたは見識が深く親切な学校の図書室の司書AIです。\n\n"
                . "【ユーザーのプロフィール】\n"
                . "「" . $profile_text . "」\n\n"
                . "【図書室の蔵書リスト】\n"
                . $books_list_str . "\n"
                . "上記の【ユーザーのプロフィール】をよく読み、その人の興味・関心、悩み、あるいは文脈から読み取れる好みの傾向を深く分析してください。\n"
                . "そして、【図書室の蔵書リスト】の中から、最もその人に合うと思われる本を必ず「1冊だけ」選んでください。\n\n"
                . "出力は必ず以下のJSON形式でのみ行ってください。余計な挨拶や解説の文字は一切含めないでください。\n"
                . "{\n"
                . "  \"selected_id\": 選んだ本のIDの数字,\n"
                . "  \"comment\": \"なぜその本を勧めるのか、その人のプロフィールに寄り添った50〜100文字程度の温かいおすすめコメント\"\n"
                . "}";

        // ③ Gemini APIを呼び出す（cURLを使用）
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . GEMINI_API_KEY;
        
        $data = [
            'contents' => [
                ['parts' => [['text' => $prompt]]]
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json' // JSONで返却するように強制
            ]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE); // ←このように直します
        curl_close($ch);

        if ($http_code === 200) {
            $result = json_decode($response, true);
            // AIが返したJSONテキストをパース
            $ai_json_str = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
            $ai_data = json_decode($ai_json_str, true);

            if (isset($ai_data['selected_id'])) {
                // ④ AIが選んだIDを使ってデータベースから本の詳細情報を取得
                $stmt = $pdo->prepare("SELECT id, title, author, publisher, image_url FROM books WHERE id = :id AND is_deleted = 0");
                $stmt->execute([':id' => $ai_data['selected_id']]);
                $recommended_book = $stmt->fetch();
                $ai_comment = $ai_data['comment'] ?? '';
            }
        }

        // ⚠️ APIエラーや該当本が万が一取得できなかった場合のセーフティ（ランダム選出）
        if (!$recommended_book) {
            $stmt = $pdo->query("SELECT id, title, author, publisher, image_url FROM books WHERE is_deleted = 0 ORDER BY RAND() LIMIT 1");
            $recommended_book = $stmt->fetch();
            $ai_comment = "こちらは今週の図書室イチオシの1冊です。ぜひ読んでみてください！";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>図書室アプリ - AIおすすめ選書</title>
    <style>
        :root {
            --md-sys-color-primary: #1a73e8;
            --md-sys-color-background: #ffffff;
            --md-sys-color-surface: #ffffff;
            --md-sys-color-surface-variant: #f1f3f4;
            --md-sys-color-on-surface: #1f1f1f;
            --md-sys-color-on-surface-variant: #5f6368;
            --md-sys-color-outline: #e0e0e0;
            --max-content-width: 600px;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Helvetica Neue', Arial, 'Hiragino Kaku Gothic ProN', 'Hiragino Sans', Meiryo, sans-serif;
            background-color: var(--md-sys-color-background);
            color: var(--md-sys-color-on-surface);
            min-height: 100vh;
        }

        .app-header {
            background-color: var(--md-sys-color-surface);
            border-bottom: 1px solid var(--md-sys-color-outline);
            position: sticky; top: 0; z-index: 10; width: 100%;
        }

        .header-inner {
            max-width: var(--max-content-width); margin: 0 auto; padding: 16px 20px;
            display: flex; align-items: center; gap: 16px;
        }

        .back-button {
            text-decoration: none; color: var(--md-sys-color-on-surface-variant);
            font-size: 20px; font-weight: 700; padding: 4px 8px; margin-left: -8px;
        }

        .app-title { font-size: 18px; font-weight: 700; }

        .main-content {
            max-width: var(--max-content-width); margin: 0 auto; padding: 30px 20px;
            display: flex; flex-direction: column; gap: 32px;
        }

        .page-title { font-size: 22px; font-weight: 700; margin-bottom: 8px; }
        .page-description { font-size: 14px; color: var(--md-sys-color-on-surface-variant); line-height: 1.5; }

        .profile-form { display: flex; flex-direction: column; gap: 16px; }
        .textarea-label { font-size: 14px; font-weight: 700; color: var(--md-sys-color-on-surface); }
        
        .profile-textarea {
            width: 100%; height: 120px; padding: 16px; font-size: 15px;
            border: 1px solid var(--md-sys-color-outline); border-radius: 12px;
            background-color: var(--md-sys-color-surface-variant);
            outline: none; resize: none; font-family: inherit; line-height: 1.5;
        }
        .profile-textarea:focus {
            background-color: var(--md-sys-color-surface);
            border-color: var(--md-sys-color-primary);
        }

        .submit-btn {
            background-color: var(--md-sys-color-primary); color: #ffffff;
            border: none; border-radius: 9999px; padding: 14px;
            font-size: 16px; font-weight: 700; cursor: pointer;
        }

        /* 結果表示カード */
        .result-section {
            background-color: #f6f9fe; border: 2px solid #e8f0fe;
            border-radius: 16px; padding: 24px;
            display: flex; flex-direction: column; gap: 16px;
            animation: fadeIn 0.4s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .result-header { font-size: 16px; font-weight: 700; color: #1a73e8; }
        
        /* 🌟 AIからの司書コメントの見た目 */
        .ai-bubble {
            background-color: #ffffff; border: 1px dashed #1a73e8;
            border-radius: 8px; padding: 14px; font-size: 14px;
            line-height: 1.6; color: #1f1f1f; font-style: italic;
        }

        .book-match-card {
            display: flex; gap: 20px; background: #ffffff;
            border: 1px solid var(--md-sys-color-outline);
            border-radius: 12px; padding: 16px; text-decoration: none; color: inherit;
        }

        .book-cover {
            width: 90px; height: 120px; background-color: var(--md-sys-color-surface-variant);
            flex-shrink: 0; display: flex; align-items: center; justify-content: center;
            font-size: 11px; font-weight: bold; border-radius: 6px; overflow: hidden;
            border: 1px solid var(--md-sys-color-outline);
        }
        .book-cover img { width: 100%; height: 100%; object-fit: cover; }

        .book-info { display: flex; flex-direction: column; justify-content: center; gap: 6px; min-width: 0; }
        .book-title { font-size: 16px; font-weight: 700; color: var(--md-sys-color-on-surface); }
        .book-author { font-size: 13px; color: var(--md-sys-color-on-surface-variant); }
        .book-publisher { font-size: 12px; color: #9aa0a6; }
    </style>
</head>
<body>

    <header class="app-header">
        <div class="header-inner">
            <a href="index.php" class="back-button">❮</a>
            <div class="app-title">図書室アプリ</div>
        </div>
    </header>

    <main class="main-content">
        
        <div>
            <h1 class="page-title">AI司書のおすすめ選書</h1>
            <p class="page-description">本物のAIがあなたのプロフィールを読み込み、文脈や感情に合わせて図書室の蔵書から最高の一冊を提案します。</p>
        </div>

        <form action="" method="POST" class="profile-form">
            <label class="textarea-label" for="profile">プロフィール・今の気分や興味</label>
            <textarea 
                name="profile" 
                id="profile" 
                class="profile-textarea" 
                placeholder="例: 最近ちょっと人間関係に疲れていて、静かに没頭できるような感動する小説が読みたい気分です。"
                required><?php echo htmlspecialchars($profile_text, ENT_QUOTES, 'UTF-8'); ?></textarea>
            <button type="submit" class="submit-btn">AI司書に相談する</button>
        </form>

        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && $recommended_book): ?>
            <div class="result-section">
                <div class="result-header">✨ AI司書が選んだあなたへの1冊</div>
                
                <div class="ai-bubble">
                    💬 「<?php echo htmlspecialchars($ai_comment, ENT_QUOTES, 'UTF-8'); ?>」
                </div>
                
                <a href="book_detail.php?id=<?php echo $recommended_book['id']; ?>" class="book-match-card">
                    <div class="book-cover">
                        <?php if (!empty($recommended_book['image_url'])): ?>
                            <img src="<?php echo htmlspecialchars($recommended_book['image_url'], ENT_QUOTES, 'UTF-8'); ?>" alt="本の表紙">
                        <?php else: ?>
                            NO COVER
                        <?php endif; ?>
                    </div>
                    <div class="book-info">
                        <div class="book-title"><?php echo htmlspecialchars($recommended_book['title'], ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="book-author">著者: <?php echo htmlspecialchars($recommended_book['author'], ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="book-publisher"><?php echo htmlspecialchars($recommended_book['publisher'], ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                </a>
            </div>
        <?php endif; ?>

    </main>

</body>
</html>