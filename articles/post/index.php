<?php
// セッションの開始
session_start();

// データベース接続設定
$db_host = 'localhost';
$db_name = 'library_app';
$db_user = 'root';
$db_pass = '';

try {
    // PDOによるデータベース接続
    $dsn = "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);

} catch (PDOException $e) {
    die('データベースエラー: ' . $e->getMessage());
}

// =========================================================
// 【非同期通信（AJAX）用の処理】: 本のサジェスト候補をJSONで返す
// =========================================================
if (isset($_GET['action']) && $_GET['action'] === 'suggest') {
    header('Content-Type: application/json; charset=utf-8');
    $keyword = isset($_GET['q']) ? trim($_GET['q']) : '';
    
    if ($keyword === '') {
        echo json_encode([]);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT id, title, author, publisher FROM books WHERE title LIKE :keyword AND is_deleted = 0 LIMIT 10");
        $stmt->execute([':keyword' => '%' . $keyword . '%']);
        $results = $stmt->fetchAll();
        echo json_encode($results);
    } catch (PDOException $e) {
        echo json_encode([]);
    }
    exit;
}

// =========================================================
// 【通常の画面表示・投稿処理】
// =========================================================

if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; 
}
$current_user_id = $_SESSION['user_id'];

$error_message = '';

// POSTリクエスト（投稿ボタンが押されたとき）
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $book_id = isset($_POST['book_id']) ? (int)$_POST['book_id'] : 0;
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';

    if ($book_id <= 0) {
        $error_message = 'サジェスト候補から対象の本を正しく選択してください。';
    } elseif ($title === '') {
        $error_message = 'タイトルを入力してください。';
    } elseif ($content === '') {
        $error_message = '紹介文を入力してください。';
    } else {
        try {
            $check_stmt = $pdo->prepare("SELECT id FROM books WHERE id = :id AND is_deleted = 0");
            $check_stmt->execute([':id' => $book_id]);
            
            if (!$check_stmt->fetch()) {
                $error_message = '選択された本は存在しないか、削除されています。';
            } else {
                // articlesテーブルへのデータ挿入
                $insert_sql = "
                    INSERT INTO articles (user_id, book_id, title, content) 
                    VALUES (:user_id, :book_id, :title, :content)
                ";
                $insert_stmt = $pdo->prepare($insert_sql);
                $insert_stmt->execute([
                    ':user_id' => $current_user_id,
                    ':book_id' => $book_id,
                    ':title'   => $title,
                    ':content' => $content
                ]);
                
                // 二重投稿防止 ＆ 投稿管理画面への自動遷移（PRGパターン）
                $_SESSION['success_message'] = '紹介文を投稿しました！';
                header('Location: ../../mypage/articles/');
                exit;
            }
        } catch (PDOException $e) {
            $error_message = '投稿中にエラーが発生しました: ' . $e->getMessage();
        }
    }
}

// =========================================================
// 【初期表示用の書籍データ取得処理】（GETまたはPOSTエラー時の復元）
// =========================================================
$selected_book_id = '';
$selected_book_title = '';

// エラーで戻ってきた場合はPOSTから、新規アクセス時はGETからIDを取得
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_id'])) {
    $selected_book_id = (int)$_POST['book_id'];
} elseif (isset($_GET['book_id'])) {
    $selected_book_id = (int)$_GET['book_id'];
}

// IDが存在する場合は、その本のタイトルを取得する
if ($selected_book_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT title FROM books WHERE id = :id AND is_deleted = 0");
        $stmt->execute([':id' => $selected_book_id]);
        $book = $stmt->fetch();
        if ($book) {
            $selected_book_title = $book['title'];
        } else {
            // 本が見つからない・削除済みの場合はIDをリセット
            $selected_book_id = '';
        }
    } catch (PDOException $e) {
        $selected_book_id = '';
    }
}

function h($string) {
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>図書室アプリ - 紹介文の投稿</title>
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

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Helvetica Neue', Arial, 'Hiragino Kaku Gothic ProN', 'Hiragino Sans', Meiryo, sans-serif;
            background-color: var(--md-sys-color-background); color: var(--md-sys-color-on-surface);
            min-height: 100vh; display: flex; flex-direction: column;
        }

        .app-header { background-color: var(--md-sys-color-surface); border-bottom: 1px solid var(--md-sys-color-outline); position: sticky; top: 0; z-index: 10; width: 100%; }
        .header-inner { max-width: var(--max-content-width); margin: 0 auto; padding: 16px 20px 8px 20px; }
        .app-title { font-size: 20px; font-weight: 700; margin-bottom: 12px; }
        .app-nav { display: flex; gap: 24px; }
        .nav-item { text-decoration: none; color: var(--md-sys-color-on-surface-variant); font-size: 15px; font-weight: 500; padding: 6px 0; position: relative; }
        .nav-item.active { color: var(--md-sys-color-on-surface); font-weight: 700; }
        .nav-item.active::after { content: ''; position: absolute; bottom: -9px; left: 0; width: 100%; height: 3px; background-color: var(--md-sys-color-primary); border-radius: 3px 3px 0 0; }

        .main-content {
            flex: 1; width: 100%; max-width: var(--max-content-width);
            margin: 0 auto; padding: 24px 20px; display: flex; flex-direction: column; gap: 20px;
        }
        .page-header { display: flex; align-items: center; gap: 12px; }
        .back-link { text-decoration: none; color: var(--md-sys-color-on-surface-variant); font-size: 14px; transition: color 0.2s; }
        .back-link:hover { color: var(--md-sys-color-primary); text-decoration: underline; }
        .page-title { font-size: 22px; font-weight: 700; margin-bottom: 8px; }

        .alert { padding: 16px; border-radius: 8px; font-size: 14px; font-weight: 500; margin-bottom: 8px; }
        .alert-error { background-color: #fdeded; color: #d32f2f; }

        .card-container {
            background-color: var(--md-sys-color-surface);
            border: 1px solid var(--md-sys-color-outline);
            border-radius: 16px; padding: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }

        .form-group { margin-bottom: 24px; position: relative; }
        .form-label { display: block; font-size: 14px; font-weight: 700; color: var(--md-sys-color-on-surface-variant); margin-bottom: 8px; }
        
        .input-field {
            width: 100%; padding: 14px 16px; font-size: 15px; color: var(--md-sys-color-on-surface);
            background-color: #ffffff; border: 1px solid var(--md-sys-color-outline); border-radius: 8px;
            outline: none; transition: 0.2s; font-family: inherit;
        }
        .input-field:focus { border-color: var(--md-sys-color-primary); box-shadow: 0 0 0 3px rgba(26, 115, 232, 0.15); }
        .input-textarea { min-height: 240px; line-height: 1.6; resize: vertical; }

        .autocomplete-container { position: relative; }
        .autocomplete-list {
            position: absolute; top: 100%; left: 0; right: 0;
            background: #fff; border: 1px solid var(--md-sys-color-outline);
            border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            margin-top: 4px; padding: 8px 0; z-index: 100;
            max-height: 250px; overflow-y: auto; list-style: none;
            display: none; 
        }
        .autocomplete-item { padding: 10px 16px; cursor: pointer; transition: background-color 0.2s; }
        .autocomplete-item:hover { background-color: var(--md-sys-color-surface-variant); }
        .item-title { font-size: 15px; font-weight: 700; color: var(--md-sys-color-on-surface); margin-bottom: 2px; }
        .item-meta { font-size: 12px; color: var(--md-sys-color-on-surface-variant); }
        .no-results { padding: 10px 16px; font-size: 14px; color: var(--md-sys-color-on-surface-variant); text-align: center; }

        .form-actions { display: flex; gap: 12px; align-items: center; margin-top: 12px; }
        .btn {
            display: inline-flex; align-items: center; justify-content: center;
            padding: 10px 24px; font-size: 14px; font-weight: 600; border-radius: 9999px;
            text-decoration: none; cursor: pointer; transition: 0.2s; border: 1px solid transparent;
        }
        .btn-primary { background-color: var(--md-sys-color-primary); color: #ffffff; }
        .btn-primary:hover { background-color: var(--md-sys-color-primary-hover); }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; }

        @media (min-width: 768px) {
            .header-inner { padding: 24px 24px 12px 24px; display: flex; justify-content: space-between; align-items: center; }
            .app-title { margin-bottom: 0; font-size: 24px; }
            .nav-item.active::after { bottom: -13px; }
            .main-content { padding: 40px 24px; gap: 24px; }
            .card-container { padding: 32px; }
        }
    </style>
</head>
<body>
    <header class="app-header">
        <div class="header-inner">
            <div class="app-title">図書室アプリ</div>
            <nav class="app-nav">
                <a href="../../index.php" class="nav-item">ホーム</a>
                <a href="../../mypage/" class="nav-item active">マイページ</a>
            </nav>
        </div>
    </header>

    <main class="main-content">
        <div class="page-header">
            <a href="javascript:history.back()" class="back-link">← 前のページへ戻る</a>
        </div>
        
        <h1 class="page-title">紹介記事投稿</h1>

        <?php if ($error_message): ?>
            <div class="alert alert-error"><?= h($error_message) ?></div>
        <?php endif; ?>

        <div class="card-container">
            <form action="index.php<?= isset($_GET['book_id']) ? '?book_id='.h($_GET['book_id']) : '' ?>" method="POST" id="postForm">
                
                <div class="form-group autocomplete-container">
                    <label for="book_search" class="form-label">対象の書籍</label>
                    <input 
                        type="text" 
                        id="book_search" 
                        class="input-field" 
                        placeholder="本のタイトルを入力して検索..." 
                        autocomplete="off"
                        value="<?= h($selected_book_title) ?>"
                    >
                    <input type="hidden" id="book_id" name="book_id" value="<?= h($selected_book_id) ?>">
                    <ul id="suggest_list" class="autocomplete-list"></ul>
                </div>

                <div class="form-group">
                    <label for="title" class="form-label">タイトル</label>
                    <input 
                        type="text" id="title" name="title" class="input-field" 
                        placeholder="記事のタイトルを入力してください" required
                        value="<?= h(isset($_POST['title']) ? $_POST['title'] : '') ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="content" class="form-label">本文</label>
                    <textarea 
                        id="content" name="content" class="input-field input-textarea" 
                        placeholder="この本の面白かったところ、おすすめのポイントなどを自由に書いてください..." required
                    ><?= h(isset($_POST['content']) ? $_POST['content'] : '') ?></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" id="submitBtn" class="btn btn-primary">
                        投稿する
                    </button>
                </div>
            </form>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('book_search');
            const hiddenBookId = document.getElementById('book_id');
            const suggestList = document.getElementById('suggest_list');
            const submitBtn = document.getElementById('submitBtn');
            let debounceTimer;

            // 最初から book_id がセットされていない場合のみボタンを無効化する
            if(hiddenBookId.value === "") {
                submitBtn.disabled = true;
            }

            searchInput.addEventListener('input', function() {
                const keyword = this.value.trim();
                
                // 入力内容が変わった時点で未選択状態にする
                hiddenBookId.value = '';
                submitBtn.disabled = true;

                if (keyword === '') {
                    suggestList.style.display = 'none';
                    return;
                }

                clearTimeout(debounceTimer);
                
                debounceTimer = setTimeout(() => {
                    fetch(`?action=suggest&q=${encodeURIComponent(keyword)}`)
                        .then(response => response.json())
                        .then(data => {
                            suggestList.innerHTML = ''; 

                            if (data.length === 0) {
                                suggestList.innerHTML = '<li class="no-results">該当する本が見つかりません</li>';
                            } else {
                                data.forEach(book => {
                                    const li = document.createElement('li');
                                    li.className = 'autocomplete-item';
                                    
                                    li.innerHTML = `
                                        <div class="item-title">${escapeHTML(book.title)}</div>
                                        <div class="item-meta">${escapeHTML(book.author)} / ${escapeHTML(book.publisher)}</div>
                                    `;
                                    
                                    li.addEventListener('click', function() {
                                        searchInput.value = book.title; 
                                        hiddenBookId.value = book.id;   
                                        suggestList.style.display = 'none'; 
                                        submitBtn.disabled = false;     
                                    });
                                    
                                    suggestList.appendChild(li);
                                });
                            }
                            suggestList.style.display = 'block';
                        })
                        .catch(error => {
                            console.error('Fetch error:', error);
                        });
                }, 300); 
            });

            document.addEventListener('click', function(e) {
                if (!searchInput.contains(e.target) && !suggestList.contains(e.target)) {
                    suggestList.style.display = 'none';
                }
            });

            function escapeHTML(str) {
                if (!str) return '';
                return str.replace(/[&<>'"]/g, 
                    tag => ({
                        '&': '&amp;',
                        '<': '&lt;',
                        '>': '&gt;',
                        "'": '&#39;',
                        '"': '&quot;'
                    }[tag] || tag)
                );
            }
        });
    </script>
</body>
</html>