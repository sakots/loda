<?php
//--------------------------------------------------
// 「loda」v0.1.0～用テーマ「basic」設定ファイル
//  by sakots
//--------------------------------------------------

//テーマ名
define('THEME_NAME', "basic");

//テーマのバージョン
define('THEME_VER', "v1.0.0 lot.230802.0");

/* -------------------- */

//テーマがXHTMLか 1:XHTML 0:HTML
define('TH_XHTML', 0);

//テンプレートファイル
/* テンプレートファイル名に".blade.php"は不要 */

//メインのテンプレートファイル
define('MAINFILE', "basic_main");
//その他のテンプレートファイル
define('OTHERFILE', "basic_other");

//エラーメッセージ
define('MSG001', "アップロードに失敗しました[It failed in up-loading.]<br>サーバーがサポートしていない可能性があります[There is a possibility that the server doesn't support it.]");
define('MSG002', "アップロードに失敗しました[It failed in up-loading.]<br>音声ファイル以外は受け付けません[It is not accepted excluding the voice or music file.]");
define('MSG003', "不正な投稿です[Please do not do an illegal contribution.]<br>POST以外での投稿は受け付けません[The contribution excluding 'POST' is not accepted.]");
define('MSG004', "異常です[Abnormality]");
define('MSG005', "ログの読み込みに失敗しました[It failed in reading the log.]");
define('MSG006', "削除に失敗しました(ユーザー)[failed in deletion.(User)]");
define('MSG007', "削除に失敗しました(管理者)[failed in deletion.(Admin)]");
define('MSG008', "該当ファイルが見つからないか、パスワードが間違っています[article is not found or password is wrong.]");
define('MSG009', "パスワードが違います[password is wrong.]");
define('MSG010', "ファイルNoが未入力です[Please input No.]");
define('MSG011', "拒絶されました[was rejected.]<br>不正な文字列があります[illegal character string.]");
define('MSG012', "予備");
define('MSG013', "予備");
define('MSG014', "予備");
define('MSG015', "予備");
define('MSG016', "予備");
define('MSG017', "予備");
define('MSG018', "予備");
define('MSG019', "予備");
define('MSG020', "予備");