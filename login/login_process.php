<?php
// セッションの開始（ログイン状態を保持するために必要になります）
session_start();

// デモ用の正しいユーザー情報
$demo_user_id = "22000";
$demo_password = "password";

// フォームからデータが正しくPOSTされたかチェック
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 入力値の取得（空文字対策）
    $input_user_id = isset($_POST['user_id']) ? trim($_POST['user_id']) : '';
    $input_password = isset($_POST['password']) ? $_POST['password'] : '';

    // 判定：入力された内容がデモ用データと完全に一致するか
    if ($input_user_id === $demo_user_id && $input_password === $demo_password) {
        
        // 【成功】セッションにログイン情報を記録（今後のマイページ等で使います）
        $_SESSION['user_id'] = $input_user_id;
        $_SESSION['logged_in'] = true;

        // ホーム画面（index.php）に自動で移動
        header("Location: ../index.php");
        exit();

    } else {
        // 【失敗】エラー番号「error=1」をURLにつけて、ログイン画面に戻す
        header("Location: login.php?error=1");
        exit();
    }
    
} else {
    // 直接このURLにアクセスされた場合はログイン画面に強制送還
    header("Location: login.php");
    exit();
}