<?php
//--------------------------------------------------
//  音声うｐろだ「loda」
//  by sakots 
//--------------------------------------------------

//スクリプトのバージョン
define('LODA_VER', 'v0.1.0'); //lot.230802.0

//設定の読み込み
require(__DIR__ . '/config.php');
require(__DIR__ . '/theme/' . THEMEDIR . '/theme_conf.php');

//タイムゾーン設定
date_default_timezone_set(DEFAULT_TIMEZONE);

//phpのバージョンが古い場合動かさせない
if (($phpver = phpversion()) < "7.3.0") {
	die("PHP version 7.3 or higher is required for this program to work. <br>\n(Current PHP version:{$phpver})");
}

//BladeOne v4.9
include(__DIR__ . '/BladeOne/lib/BladeOne.php');
use eftec\bladeone\BladeOne;

$views = __DIR__ . '/theme/' . THEMEDIR; // テンプレートフォルダ
$cache = __DIR__ . '/cache'; // キャッシュフォルダ

//キャッシュフォルダがなかったら作成
if (!file_exists($cache)) {
	mkdir($cache, PERMISSION_FOR_DIR);
}

$blade = new BladeOne($views, $cache, BladeOne::MODE_AUTO); // MODE_DEBUGだと開発モード MODE_AUTOが速い。
$blade->pipeEnable = true; // パイプのフィルターを使えるようにする

$dat = array(); // bladeに格納する変数

// jQueryバージョン
define('JQUERY','jquery-3.7.0.min.js');

//絶対パス取得
$path = realpath("./").'/'.'data/';
$temppath = realpath("./").'/'.'tmp/';

define('IMG_PATH', $path);
define('TMP_PATH', $temppath);

$self = PHP_SELF;

$dat['ver'] = LODA_VER;
$dat['btitle'] = TITLE;
$dat['self'] = PHP_SELF;

$dat['themedir'] = THEMEDIR;
$dat['tname'] = THEME_NAME;
$dat['tver'] = THEME_VER;

$dat['addinfo'] = $addinfo;

//----------

//データベース接続PDO
define('DB_PDO', 'sqlite:' . DB_NAME . '.db');

//初期設定
init();

deltemp();

//ユーザーip
function get_uip()
{
	if ($user_ip = getenv("HTTP_CLIENT_IP")) {
		return $user_ip;
	} elseif ($user_ip = getenv("HTTP_X_FORWARDED_FOR")) {
		return $user_ip;
	} elseif ($user_ip = getenv("REMOTE_ADDR")) {
		return $user_ip;
	} else {
		return $user_ip;
	}
}

//csrfトークンを作成
function get_csrf_token()
{
	if (!isset($_SESSION)) {
		session_save_path(__DIR__ . '/session/');
		session_start();
	}
	header('Expires:');
	header('Cache-Control:');
	header('Pragma:');
	return hash('sha256', session_id(), false);
}
//csrfトークンをチェック
function check_csrf_token()
{
	session_save_path(__DIR__ . '/session/');
	session_start();
	$token = filter_input(INPUT_POST, 'token');
	$session_token = isset($_SESSION['token']) ? $_SESSION['token'] : '';
	if (!$session_token || $token !== $session_token) {
		error(MSG006);
	}
}

/*-----------mode-------------*/

$mode = filter_input(INPUT_POST, 'mode');

if (filter_input(INPUT_GET, 'mode') === "regist") {
	$mode = "regist";
}
if (filter_input(INPUT_GET, 'mode') === "del") {
	$mode = "del";
}
if (filter_input(INPUT_GET, 'mode') === "search") {
	$mode = "search";
}

switch ($mode) {
	case 'regist':
		return regist();
	case 'search':
		return search();
	case 'del':
		return delmode();
	default:
		return def();
}
exit;

/*-----------Main-------------*/

function init()
{
	try {
		if (!is_file(DB_NAME . '.db')) {
			// はじめての実行なら、テーブルを作成
			// id, 投稿日時, オリジナルファイル名, ファイルサイズ
			$db = new PDO(DB_PDO);
			$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$sql = "CREATE TABLE tlog (tid integer primary key autoincrement, created TIMESTAMP, origin text, size )";
			$db = $db->query($sql);
			$db = null; //db切断
		}
	} catch (PDOException $e) {
		echo "DB接続エラー:" . $e->getMessage();
	}
	$err = '';
	if (!is_writable(realpath("./"))) error("カレントディレクトリに書けません<br>");
	if (!is_dir('data/')) {
		mkdir('data/', PERMISSION_FOR_DIR);
		chmod('data/', PERMISSION_FOR_DIR);
	}
	if (!is_dir('data/')) $err .= 'data/' . "がありません<br>";
	if (!is_writable('data/')) $err .= 'data/' . "を書けません<br>";
	if (!is_readable('data/')) $err .= 'data/' . "を読めません<br>";

	if (!is_dir('tmp/')) {
		mkdir('tmp/', PERMISSION_FOR_DIR);
		chmod('tmp/', PERMISSION_FOR_DIR);
	}
	if (!is_dir(__DIR__ . '/session/')) {
		mkdir(__DIR__ . '/session/', PERMISSION_FOR_DIR);
		chmod(__DIR__ . '/session/', PERMISSION_FOR_DIR);
	}
	if (!is_dir('tmp/')) $err .= 'tmp/' . "がありません<br>";
	if (!is_writable('tmp/')) $err .= 'tmp/' . "を書けません<br>";
	if (!is_readable('tmp/')) $err .= 'tmp/' . "を読めません<br>";
	if ($err) error($err);
}