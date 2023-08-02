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

// jQuery
const JQUERY='jquery-3.7.0.min.js';

//絶対パス取得
$path = realpath("./").'/'.'data/';
$temppath = realpath("./").'/'.'tmp/';

define('IMG_PATH', $path);
define('TMP_PATH', $temppath);

$self = PHP_SELF;

$dat['ver'] = LODA_VER;
$dat['title'] = TITLE;
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

$pwdc = filter_input(INPUT_COOKIE, 'pwdc');

$req_method = isset($_SERVER["REQUEST_METHOD"]) ? $_SERVER["REQUEST_METHOD"] : "";
//INPUT_SERVER が動作しないサーバがあるので$_SERVERを使う。

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
function get_csrf_token(){
	session_sta();
	$token = hash('sha256', session_id(), false);
	$_SESSION['token'] = $token;

	return $token;
}
//csrfトークンをチェック	
function check_csrf_token(){
	if(($_SERVER["REQUEST_METHOD"]) !== "POST"){
		return error('失敗しました。[This operation has failed.]');
	} 
	check_same_origin();
	session_sta();
	$token=(string)filter_input(INPUT_POST,'token');
	$session_token=isset($_SESSION['token']) ? (string)$_SESSION['token'] : '';
	if(!$session_token||$token!==$session_token){
		return error("CSRFトークンが一致しません。\nリロードしてください。[CSRF token mismatch.\nPlease reload.]");
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
			// はじめての実行なら、テーブルを作成
			// id, 投稿日時, アップロード前ファイル名, アップロード後ファイル名, コメント, ファイルサイズ, DL/再生カウント, 削除キー
			$db = new PDO(DB_PDO);
			$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$sql = "CREATE TABLE IF NOT EXISTS up (
        tid integer primary key autoincrement,
        created TIMESTAMP,
        origin_file_name text,
        file_name text,
        comment text,
        size integer,
        count integer,
        del_key text
        )";
			$db = $db->query($sql);
			$db = null; //db切断
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

/* テンポラリ内のゴミ除去 */
function deltemp()
{
	$handle = opendir('tmp/');
	while ($file = readdir($handle)) {
		if (!is_dir($file)) {
			$lapse = time() - filemtime('tmp/' . $file);
			if ($lapse > (7 * 24 * 3600)) { //7日間
				unlink('tmp/' . $file);
			}
		}
	}
	closedir($handle);
}

//書き込み
function regist(){
  global $admin_pass,$req_method,$dat;

  //CSRFトークンをチェック
	if (CHECK_CSRF_TOKEN) {
		check_csrf_token();
	}

  $upfile = filter_input(INPUT_POST, 'upfile');
  $comment = (string)filter_input(INPUT_POST, 'comment');
  $pwd = (string)trim(filter_input(INPUT_POST, 'pwd'));
	$pwdh = password_hash($pwd, PASSWORD_DEFAULT);

  if ($req_method !== "POST") {
		error("不正な投稿です。[Please do not do an illegal contribution.]<br>POST以外での投稿は受け付けません。[The contribution excluding 'POST' is not accepted.]");
	}
}

//ログの行数が最大値を超えていたら削除
function logdel()
{
	//オーバーした行の画像とスレ番号を取得
	try {
		$db = new PDO(DB_PDO);
		$sql_del = "SELECT * FROM up ORDER BY tid LIMIT 1";
		$msgs = $db->prepare($sql_del);
		$msgs->execute();
		$msg = $msgs->fetch();

		$del_tid = (int)$msg["tid"]; //消す行のスレ番号
		$msgfile = $msg["filename"]; //ファイルの名前取得できた
		//削除処理
		if (is_file('data/' . $msgfile)) {
      $ext = substr( $msgfile, strrpos( $msgfile, '.') + 1);
			$msgdat = pathinfo($msgfile, PATHINFO_FILENAME); //拡張子除去
			if (is_file('data/'. $msgdat . $ext)) {
				unlink('data/' . $msgdat . $ext);
			}
		}

		//sql削除
		$delths = "DELETE FROM up WHERE tid = $del_tid";
		$db->exec($delths);

		$sql_del = null;
		$msg = null;
		$del_tid = null;
		$db = null; //db切断
	} catch (PDOException $e) {
		echo "DB接続エラー:" . $e->getMessage();
	}
}

/* 改行を<br>に */
function tobr($com)
{
	if (TH_XHTML !== 1) {
		$com = nl2br($com, false);
	} else {
		$com = nl2br($com);
	}
	return $com;
}

//sessionの確認
function adminpost_valid(){
	global $second_pass;
	session_sta();
	return isset($_SESSION['adminpost'])&&($second_pass && $_SESSION['adminpost']===$second_pass);
}
function admindel_valid(){
	global $second_pass;
	session_sta();
	return isset($_SESSION['admindel'])&&($second_pass && $_SESSION['admindel']===$second_pass);
}

//session開始
function session_sta(){
	if(!isset($_SESSION)){
		ini_set('session.use_strict_mode', 1);
		session_set_cookie_params(
			0,"","",false,true
		);
		session_start();
		header('Expires:');
		header('Cache-Control:');
		header('Pragma:');
	}
}

function check_same_origin(){
	if(!isset($_SERVER['HTTP_ORIGIN']) || !isset($_SERVER['HTTP_HOST'])){
		return error("お使いのブラウザはサポートされていません。[Your browser is not supported. ]");
	}
	if(parse_url($_SERVER['HTTP_ORIGIN'], PHP_URL_HOST) !== $_SERVER['HTTP_HOST']){
		return error("拒絶されました。[The post has been rejected.]");
	}
}

function check_open_no($no){
	global $en;
	if($no && !is_numeric($no)){
		return error("失敗しました。[This operation has failed.]");
	}
}

//Asyncリクエストの時は処理を中断
function check_AsyncRequest($upfile='') {
	//ヘッダーが確認できなかった時の保険
	$asyncflag = (bool)filter_input(INPUT_POST,'asyncflag',FILTER_VALIDATE_BOOLEAN);
	$http_x_requested_with= (bool)(isset($_SERVER['HTTP_X_REQUESTED_WITH']));
	if($http_x_requested_with || $asyncflag){ //非同期通信ならエラーチェックだけすませて処理中断。通常フォームでやりなおし。
		safe_unlink($upfile);
		exit;
	}
}

//ファイルがあれば削除
function safe_unlink ($path) {
	if ($path && is_file($path)) {
		return unlink($path);
	}
	return false;
}

/* エスケープ */
function h($str){
	if($str===0 || $str==='0'){
		return '0';
	}
	if(!$str){
		return '';
	}
	return htmlspecialchars($str,ENT_QUOTES,"utf-8",false);
}

/* エラー */
function error($str,$historyback=true){
	global $blade,$dat;

	$asyncflag = (bool)filter_input(INPUT_POST,'asyncflag',FILTER_VALIDATE_BOOLEAN);
	$http_x_requested_with= (bool)(isset($_SERVER['HTTP_X_REQUESTED_WITH']));
	if($http_x_requested_with||$asyncflag){
		return die(h("error\n{$str}"));
	}
	echo $blade->run(ERRORFILE, $dat);
	exit;
}
