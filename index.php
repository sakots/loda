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

