<?php
// セッションが開始されていない場合は開始する
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ログイン状態によって右上のナビゲーションの文字とリンク先を切り替える
// ※どの階層から呼ばれてもリンク切れしないよう「/」から始まるルート相対パスを使用します
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    $nav_text = "マイページ";
    $nav_link = "/kosen-library/mypage/index.php"; 
} else {
    $nav_text = "ログイン";
    $nav_link = "/kosen-library/login/index.php";
}
?>
