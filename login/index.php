<?php
// URLは /login/ のまま変わらない
session_start();

// 50%の確率で遊び心のあるログイン画面を表示
if (rand(0, 1) === 0) {
    // URLを変えずにファイルの中身だけを表示する
    include('oasobiyou_login.php');
} else {
    // 通常のログイン画面を表示
    include('normal_login.php');
}
?>