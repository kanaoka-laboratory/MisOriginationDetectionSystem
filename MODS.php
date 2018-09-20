<?php
//==================== 引数のエラーチェック ====================//
//------------ サブコマンドの取得 ------------//
$subcommand = isset($argv[1])? $argv[1]: '';
// サブコマンドが存在しない
if(!in_array("$subcommand.php", scandir('subcommand/'), true)){
	echo'Usage: php MODS.php <subcommand> [<options>]',PHP_EOL,PHP_EOL,
		'subcommand',PHP_EOL,
		'  GetRIPE <START> [<END>]                       : RIPEから経路情報をDLしてBGPDUMPに展開する',PHP_EOL,
		'  GetRIPEUpdate <START> [<END>]                 : RIPEから経路情報をDLしてBGPDUMPに展開する',PHP_EOL,
		'  TrackOriginExactChangedPrefix <START> <END>   : OriginASの変更をExactMatchで検出し，変更のあったIPプレフィックスを追跡する',PHP_EOL,
		'  TrackOriginIncludeChangedPrefix <START> <END> : OriginASの変更をIncludeMatchで検出し，変更のあったIPプレフィックスを追跡する',PHP_EOL,
		'  AnalyseKindAndChangeNum <FILENAME>            : OriginASに変更のあったIPプレフィックスの追跡結果を，データの種類数と変更回数で統計する',PHP_EOL,
		'  help                                          : このドキュメントを表示',PHP_EOL;
	exit(1);
}

//==================== 初期設定 ====================//
// プログラムのカレントディレクトリを変更
chdir(dirname(__FILE__));
// 設定ファイル読み込み
require_once('config.php');
// 関数などの読み込み
foreach(glob('import/*.php') as $filename) require_once($filename);
require_once("subcommand/$subcommand.php");

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
		if(!isset($option[0])) throw new Exception();
		startLogging($subcommand);
		$subcommand($option[0], isset($option[1])?$option[1]:'now');
		break;
	//------------ TrackOriginExactChangedPrefix, TrackOriginIncludeChangedPrefix ------------//
	case 'TrackOriginExactChangedPrefix':
	case 'TrackOriginIncludeChangedPrefix':
		if(!isset($option[1])) throw new Exception();
		startLogging($subcommand);
		$subcommand($option[0], $option[1]);
		break;
	//------------ AnalyseKindAndChangeNum ------------//
	case 'AnalyseKindAndChangeNum':
		if(!isset($option[0])) throw new Exception();
		startLogging($subcommand);
		$subcommand($option[0]);
		break;
	//------------ hoge ------------//
	case 'hoge':
		if(!isset($option[0])) throw new Exception();
		startLogging($subcommand);
		$subcommand($option[0]);
		break;
	}
}
//------------ コマンド毎の説明 ------------//
catch(Exception $e){
	switch($subcommand){
	//------------ GetRIPE, GetRIPEUpdate ------------//
	case 'GetRIPE':
	case 'GetRIPEUpdate':
		$str_array = array('GetRIPE'=>'FULL', 'GetRIPEUpdate'=>'UPDATE');
		echo"$subcommand: RIPE RIRから経路情報（{$str_array[$subcommand]}）を取得し，BGPDUMP形式に展開する",PHP_EOL,PHP_EOL,
			"Usage: php MODS.php $subcommand <START> [<END>]",PHP_EOL,PHP_EOL,
			'Options',PHP_EOL,
			'  START : 取得を開始する日時 ex. 2018-01-01_00:00',PHP_EOL,
			'  END   : 取得を終了する日時',PHP_EOL,
			'          省略した場合は可能な限り最新の日時',PHP_EOL;
		break;
	//------------ TrackOriginExactChangedPrefix, TrackOriginIncludeChangedPrefix ------------//
	case 'TrackOriginExactChangedPrefix':
	case 'TrackOriginIncludeChangedPrefix':
		$str_array = array('TrackOriginExactChangedPrefix'=>'ExactMatch', 'TrackOriginIncludeChangedPrefix'=>'IncludeMatch');
		echo"$subcommand: OriginASの変更を{$str_array[$subcommand]}で検出し，変更のあったASを追跡する",PHP_EOL,PHP_EOL,
			"Usage: php MODS.php $subcommand <START> <END>",PHP_EOL,PHP_EOL,
			'Options',PHP_EOL,
			'  START : 変更検出の基準となる日時',PHP_EOL,
			'          この日時と次（8時間後）の日時を比較して変更があったIPプレフィックスを追跡する',PHP_EOL,
			'  END   : 変更検出を行う期間の終了日時',PHP_EOL;
		break;
	//------------ AnalyseKindAndChangeNum ------------//
	case 'AnalyseKindAndChangeNum':
		echo'AnalyseKindAndChangeNum: OriginASに変更のあったIPプレフィックスの追跡結果を，データの種類数と変更回数で統計する',PHP_EOL,PHP_EOL,
			'Usage: php MODS.php AnalyseKindAndChangeNum <FILENAME>',PHP_EOL,PHP_EOL,
			'Options',PHP_EOL,
			'  FILENAME : TrackOrigin(Exact|Include)ChangedPrefixサブコマンドで出力されたcsvファイル',PHP_EOL;
		break;
	//------------ hoge ------------//
	case 'hoge':
		echo'hoge: 概要説明',PHP_EOL,PHP_EOL,
			'Usage: php MODS.php hoge <OPTION1>',PHP_EOL,PHP_EOL,
			'Options',PHP_EOL,
			'  OPTION1 : その説明',PHP_EOL;
		break;
	}
	exit(1);
}

//==================== 実行完了 ====================//
endLogging();
?>
