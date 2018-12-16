<?php
// プログラムのカレントディレクトリを変更
chdir(dirname(__FILE__));

//==================== 引数のエラーチェック ====================//
//------------ サブコマンドの取得 ------------//
$subcommand = isset($argv[1])? $argv[1]: '';
$subcommand_usage = array(
	'GetRIPE'							=> 'GetRIPE <START> [<END>]                          : RIPE RIRから経路情報（FULL）を取得し，BGPDUMP形式に展開する',
	'GetRIPEUpdate'						=> 'GetRIPEUpdate <START> [<END>]                    : RIPE RIRから経路情報（UPDATE）を取得し，BGPDUMP形式に展開する',
	'ExtractPHPDataFromBGPDUMP'			=> 'ExtractPHPDataFromBGPDUMP <START> [<END>]        : BGPDUMPファイルからネットワークリストを抽出しPHPの配列としてファイルに保存する',
	'TrackOriginExactChangedPrefix'		=> 'TrackOriginExactChangedPrefix <START> <END>      : OriginASの変更をExactMatchで検出し，変更のあったIPプレフィックスを追跡する',
	'TrackOriginExactChangedPrefix2'	=> 'TrackOriginExactChangedPrefix2 <DATE>            : OriginASの変更をExactMatchで検出し，1週間前からのOriginASの変遷を追跡する',
	'TrackOriginIncludeChangedPrefix'	=> 'TrackOriginIncludeChangedPrefix <START> <END>    : OriginASの変更をIncludeMatchで検出し，変更のあったIPプレフィックスを追跡する',
	'TrackOriginIncludeChangedPrefix2'	=> 'TrackOriginIncludeChangedPrefix2 <DATE>          : OriginASの変更をIncludeMatchで検出し，1週間前からのOriginASの変遷を追跡する',
	'AnalyseKindAndChangeNum'			=> 'AnalyseKindAndChangeNum <FILENAME>               : OriginASに変更のあったIPプレフィックスの追跡結果を，データの種類数と変更回数で統計する',
	'TrackAndAnalyseKindAndChangeNum'	=> 'TrackAndAnalyseKindAndChangeNum <START> <END>    : TrackOrigin(Exact|Include)ChangedPrefixを両方実行後，AnalyseKindAndChangeNumを実行する',
	'GroupChangesOfOriginAS'			=> 'GroupChangesOfOriginAS <FILENAME>                : OriginASに変更のあったIPプレフィックスの追跡結果を，OriginASの変更の仕方により細分化する',
	'AnalyseAdvertisementUpdate'		=> 'AnalyseAdvertisementUpdate <START> [<END>]       : 5分おきのアップデートのAdvertisementを，直前のフルルートのダンプと比較し変更の検出をする',
	'AnalyseAdvertisementUpdateSummary'	=> 'AnalyseAdvertisementUpdateSummary <START> [<END>]: AnalyseAdvertisementUpdateの結果から，各時刻毎の各typeの数を集計する（Excelでのグラフ作成用）',
	'CronRIPEFull'						=> 'CronRIPEFull                                     : Cron実行用（8時間おきのフルルートを取得して変更検出，過去方向に1週間追跡）',
	'CronRIPEUpdate'					=> 'CronRIPEUpdate                                   : Cron実行用（5分おきのフルルートを取得し，直前のフルルートとの衝突検出）',
	'CronASCountry'						=> 'CronASCountry                                    : Cron実行用（ASと国の紐付け）',
	'help'								=> 'help                                             : このドキュメントを表示',
);

// サブコマンドが存在しない
if(!in_array("$subcommand.php", scandir('subcommand/'), true)){
	echo'Usage: php MODS.php <subcommand> [<options>]',PHP_EOL,PHP_EOL,
		'subcommand',PHP_EOL;
	foreach ($subcommand_usage as $text) echo "  $text",PHP_EOL;
	exit(1);
}

//==================== 初期設定 ====================//
// 設定ファイル読み込み
require_once('config.php');
// 関数などの読み込み
foreach(glob('import/*.php') as $filename) require_once($filename);
require_once("subcommand/$subcommand.php");
// MySQL接続
$mysqli = new mymysqli();

//------------ サブコマンドへのオプションを取得 ------------//
$option = array_slice($argv, 2);
// オプションのエラーチェック
try{
	// ヘルプドキュメントを求めているときは表示する
	if(isset($option[0]) && $option[0]==='help')
		throw new Exception($subcommand);

	// 各サブコマンドごとに引数の前処理をして実行
	switch($subcommand){
	//------------ GetRIPE, GetRIPEUpdate ------------//
	case 'GetRIPE':
	case 'GetRIPEUpdate':
	case 'ExtractPHPDataFromBGPDUMP':
		if(!isset($option[0])) throw new Exception();
		startLogging($subcommand);
		$subcommand($option[0], isset($option[1])?$option[1]:null);
		break;
	//------------ TrackOriginExactChangedPrefix, TrackOriginIncludeChangedPrefix ------------//
	case 'TrackOriginExactChangedPrefix':
	case 'TrackOriginIncludeChangedPrefix':
		if(!isset($option[1])) throw new Exception();
		startLogging($subcommand);
		$subcommand($option[0], $option[1]);
		break;
	//------------ TrackOriginExactChangedPrefix2, TrackOriginIncludeChangedPrefix2 ------------//
	case 'TrackOriginExactChangedPrefix2':
	case 'TrackOriginIncludeChangedPrefix2':
		if(!isset($option[0])) throw new Exception();
		startLogging($subcommand);
		$subcommand($option[0]);
		break;
	//------------ AnalyseKindAndChangeNum, GroupChangesOfOriginAS ------------//
	case 'AnalyseKindAndChangeNum':
	case 'GroupChangesOfOriginAS':
		if(!isset($option[0])) throw new Exception();
		startLogging($subcommand);
		$subcommand($option[0]);
		break;
	//------------ TrackAndAnalyseKindAndChangeNum ------------//
	case 'TrackAndAnalyseKindAndChangeNum':
		if(!isset($option[1])) throw new Exception();
		startLogging($subcommand);
		$subcommand($option[0], $option[1]);
		break;
	//------------ AnalyseAdvertisementUpdate(Summary) ------------//
	case 'AnalyseAdvertisementUpdate':
	case 'AnalyseAdvertisementUpdateSummary':
		if(!isset($option[0])) throw new Exception();
		startLogging($subcommand);
		$subcommand($option[0], isset($option[1])?$option[1]:null);
		break;
	//------------ GetASCountry ------------//
	case 'GetASCountry':
		startLogging($subcommand);
		$subcommand(isset($option[0])? $option[0]: null);
		break;
	//------------ Cron* ------------//
	case 'CronRIPEFull':
	case 'CronRIPEUpdate':
	case 'CronASCountry':
		startLogging($subcommand);
		$subcommand();
		break;
	//------------ hoge ------------//
	case 'hoge':
		if(!isset($option[0])) throw new Exception();
		startLogging($subcommand);
		$subcommand();
		break;
	}
}
//------------ コマンド毎の説明 ------------//
catch(Exception $e){
	echo $subcommand_usage[$subcommand],PHP_EOL,PHP_EOL;
	echo 'Options',PHP_EOL;
	switch($subcommand){
	//------------ GetRIPE, GetRIPEUpdate ------------//
	case 'GetRIPE':
	case 'GetRIPEUpdate':
	case 'ExtractPHPDataFromBGPDUMP':
		echo'  START : 取得を開始する日時 ex. 2018-01-01_00:00',PHP_EOL,
			'  END   : 取得を終了する日時',PHP_EOL,
			'          省略した場合はSTARTと同時刻',PHP_EOL;
		break;
	//------------ TrackOriginExactChangedPrefix, TrackOriginIncludeChangedPrefix ------------//
	case 'TrackOriginExactChangedPrefix':
	case 'TrackOriginIncludeChangedPrefix':
		echo'  START : 変更検出の基準となる日時',PHP_EOL,
			'          この日時と次（8時間後）の日時を比較して変更があったIPプレフィックスを追跡する',PHP_EOL,
			'  END   : 変更検出を行う期間の終了日時',PHP_EOL;
		break;
	//------------ TrackOriginExactChangedPrefix2, TrackOriginIncludeChangedPrefix2 ------------//
	case 'TrackOriginExactChangedPrefix2':
	case 'TrackOriginIncludeChangedPrefix2':
		echo'  DATE : 変更検出の基準となる日時',PHP_EOL,
			'         この日時と前（8時間前）の日時を比較して変更があったIPプレフィックスを追跡する',PHP_EOL;
		break;
	//------------ AnalyseKindAndChangeNum, GroupChangesOfOriginAS ------------//
	case 'AnalyseKindAndChangeNum':
	case 'GroupChangesOfOriginAS':
		echo'  FILENAME : TrackOrigin(Exact|Include)ChangedPrefixサブコマンドで出力されたcsvファイル',PHP_EOL;
		break;
	//------------ TrackAndAnalyseKindAndChangeNum ------------//
	case 'TrackAndAnalyseKindAndChangeNum':
		echo'  START : 変更検出の基準となる日時',PHP_EOL,
			'          この日時と次（8時間後）の日時を比較して変更があったIPプレフィックスを追跡する',PHP_EOL,
			'  END   : 変更検出を行う期間の終了日時',PHP_EOL;
		break;
	//------------ AnalyseAdvertisementUpdate(Summary) ------------//
	case 'AnalyseAdvertisementUpdate':
	case 'AnalyseAdvertisementUpdateSummary':
		echo'  START : 分析対象の日付',PHP_EOL,
			'  END   : 複数の連続した日付のデータを分析する場合にその終了日を指定',PHP_EOL;
		break;
	//------------ GetASCountry ------------//
	case 'GetASCountry':
		echo' DATE : 紐づけ情報の取得対象日',PHP_EOL;
		break;
	//------------ Cron* ------------//
	case 'CronRIPEFull':
	case 'CronRIPEUpdate':
	case 'CronASCountry':
		break;
	//------------ hoge ------------//
	case 'hoge':
		echo'  OPTION1 : その説明',PHP_EOL;
		break;
	}
	exit(1);
}

//==================== 実行完了 ====================//
endLogging();
?>