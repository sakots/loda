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

$self = PHP_SELF;

$dat['ver'] = LODA_VER;
$dat['btitle'] = TITLE;
$dat['self'] = PHP_SELF;

$dat['themedir'] = THEMEDIR;
$dat['tname'] = THEME_NAME;
$dat['tver'] = THEME_VER;

$dat['addinfo'] = $addinfo;

