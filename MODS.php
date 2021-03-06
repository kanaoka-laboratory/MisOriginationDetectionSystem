<?php
// プログラムのカレントディレクトリを変更
chdir(__DIR__);
// 設定ファイル読み込み
require_once('config.php');
// メンテナンス時は何もせず終了
if(trim(file_get_contents("maintenance.ini"))==="true") exit("メンテナンスモードがONになっています".PHP_EOL);	

//==================== 引数のエラーチェック ====================//
//------------ サブコマンドの取得 ------------//
$subcommand = isset($argv[1])? $argv[1]: '';
$subcommand_usage = array(
	'GetBGPFullRoute'						=> 'GetBGPFullRoute <RC> <START> [<END>]                      : RIPE RIRから経路情報（FULL）を取得し，BGPDUMP形式に展開する',
	'GetBGPUpdate'							=> 'GetBGPUpdate <RC> <START> [<END>]                         : RIPE RIRから経路情報（UPDATE）を取得し，BGPDUMP形式に展開する',
	'ExtractPHPDataFromBGPScanner'			=> 'ExtractPHPDataFromBGPScanner <RC> <START> [<END>]         : BGPDUMPファイルからネットワークリストを抽出しPHPの配列としてファイルに保存する',
	'TrackOriginExactChangedPrefix'			=> 'TrackOriginExactChangedPrefix <RC> <START> <END>          : OriginASの変更をExactMatchで検出し，変更のあったIPプレフィックスを指定期間追跡する',
	'TrackOriginExactChangedPrefix2'		=> 'TrackOriginExactChangedPrefix2 <RC> <DATE>                : OriginASの変更をExactMatchで検出し，1週間前からのOriginASの変遷を追跡する',
	'TrackOriginIncludeChangedPrefix'		=> 'TrackOriginIncludeChangedPrefix <RC> <START> <END>        : OriginASの変更をIncludeMatchで検出し，変更のあったIPプレフィックスを指定期間追跡する',
	'TrackOriginIncludeChangedPrefix2'		=> 'TrackOriginIncludeChangedPrefix2 <RC> <DATE>              : OriginASの変更をIncludeMatchで検出し，1週間前からのOriginASの変遷を追跡する',
	'AnalyseKindAndChangeNum'				=> 'AnalyseKindAndChangeNum <FILENAME>                        : OriginASに変更のあったIPプレフィックスの追跡結果を，データの種類数と変更回数で統計する',
	'TrackAndAnalyseKindAndChangeNum'		=> 'TrackAndAnalyseKindAndChangeNum <RC> <START|DATE> [<END>] : TrackOriginChangedPrefixを両方実行後，AnalyseKindAndChangeNumを実行する',
	'AnalyseBGPUpdate'						=> 'AnalyseBGPUpdate <RC> <START> [<END>]                     : 5分おきのアップデートのAdvertisementを，直前のフルルートのダンプと比較し変更の検出をする',
	'AnalyseBGPUpdateSummary'				=> 'AnalyseBGPUpdateSummary <RC> <START> [<END>]              : AnalyseAdvertisementの結果から，各時刻毎の各typeの数を集計する（作図用）',
	'FilterSuspiciousBGPUpdate'				=> 'FilterSuspiciousBGPUpdate [<RC>]                          : AnalyseAdvertisementの結果のハイジャックの可能性があるものをホワイトリストを用いて分類する',
	'CronBGPFullRoute'						=> 'CronBGPFullRoute <RC>                                     : Cron実行用（8時間おきのフルルートを取得して変更検出）',
	'CronBGPUpdate'							=> 'CronBGPUpdate <RC>                                        : Cron実行用（5分おきのフルルートを取得し，直前のフルルートとの衝突検出）',
	'CronFilterSuspiciousBGPUpdate'			=> 'CronFilterSuspiciousBGPUpdate [<RC>]                      : Cron実行用（ハイジャックの可能性があるASペアをホワイトリストを用いて分類）',
	'CronASCountry'							=> 'CronASCountry                                             : Cron実行用（ASと国の紐付け）',
	'ImportSubmarineCableList'				=> 'ImportSubmarineCableList <CABLE LIST>                     : SubmarineCableMapより取得したCSVから海底ケーブルで接続された国を探し，DBに登録する',
	'GetWhoisInfoFromAsn'					=> 'GetWhoisInfoFromAsn <ASN>                                 : AS番号のwhois情報を取得してDBに保存する',
	'ReApplyWhitelist'						=> 'ReApplyWhitelist [<SUSPICIOUS_ID>]                        : SuspiciousAsnSetに対してホワイトリストを再適用する',
	'CalcCountryDistance'					=> 'CalcCountryDistance                                       : 全ての2国間の隣接（陸地/海底ケーブル）ホップ数を求める',
	'SummaryCountryDistance'				=> 'SummaryCountryDistance                                    : 各ConflictTypeごとにホップ数ごとの分布を求める',
	'help'									=> 'help                                                      : このドキュメントを表示',
);

// サブコマンドが存在しない
if(!in_array("$subcommand.php", scandir('subcommand/'), true)){
	echo'Usage: php MODS.php <subcommand> [<options>]',PHP_EOL,PHP_EOL,
		'subcommand',PHP_EOL;
	foreach ($subcommand_usage as $text) echo "  $text",PHP_EOL;
	exit(1);
}

//==================== 初期設定 ====================//
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
	case 'GetBGPFullRoute':
	case 'GetBGPUpdate':
	case 'ExtractPHPDataFromBGPScanner':
		if(!isset($option[1])) throw new Exception();
		startLogging($subcommand);
		$subcommand($option[0], $option[1], isset($option[2])?$option[2]:null);
		break;
	//------------ TrackOriginExactChangedPrefix, TrackOriginIncludeChangedPrefix ------------//
	case 'TrackOriginExactChangedPrefix':
	case 'TrackOriginIncludeChangedPrefix':
		if(!isset($option[2])) throw new Exception();
		startLogging($subcommand);
		$subcommand($option[0], $option[1], $option[2]);
		break;
	//------------ TrackOriginExactChangedPrefix2, TrackOriginIncludeChangedPrefix2 ------------//
	case 'TrackOriginExactChangedPrefix2':
	case 'TrackOriginIncludeChangedPrefix2':
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
	//------------ TrackAndAnalyseKindAndChangeNum ------------//
	case 'TrackAndAnalyseKindAndChangeNum':
		if(!isset($option[1])) throw new Exception();
		startLogging($subcommand);
		$subcommand($option[0], $option[1], isset($option[2])?$option[2]:null);
		break;
	//------------ AnalyseBGPUpdate(Summary) ------------//
	case 'AnalyseBGPUpdate':
	case 'AnalyseBGPUpdateSummary':
		if(!isset($option[1])) throw new Exception();
		startLogging($subcommand);
		$subcommand($option[0], $option[1], isset($option[2])?$option[2]:null);
		break;
	//------------ FilterSuspiciousBGPUpdate ------------//
	case 'FilterSuspiciousBGPUpdate':
		startLogging($subcommand);
		$subcommand(isset($option[0])?$option[0]:null);
		break;
	//------------ CronBGPFullRoute/Update ------------//
	case 'CronBGPFullRoute':
	case 'CronBGPUpdate':
		if(!isset($option[0])) throw new Exception();
		startLogging($subcommand);
		$subcommand($option[0]);
		break;
	//------------ CronFilterSuspiciousBGPUpdate ------------//
	case 'CronFilterSuspiciousBGPUpdate':
		startLogging($subcommand);
		$subcommand(isset($option[0])?$option[0]:null);
		break;
	//------------ CronASCountry ------------//
	case 'CronASCountry':
		startLogging($subcommand);
		$subcommand();
		break;
	//------------ ImportSubmarineCableList ------------//
	case 'ImportSubmarineCableList':
		if(!isset($option[0])) throw new Exception();
		startLogging($subcommand);
		$subcommand($option[0]);
		break;
	//------------ GetWhoisInfoFromAsn ------------//
	case 'GetWhoisInfoFromAsn':
		if(!isset($option[0])) throw new Exception();
		startLogging($subcommand);
		$subcommand($option[0]);
		break;
	//------------ ReApplyWhitelist ------------//
	case 'ReApplyWhitelist':
		startLogging($subcommand);
		$subcommand(isset($option[0])?$option[0]:null);
		break;
	//------------ CalcCountryDistance ------------//
	case 'CalcCountryDistance':
	case 'SummaryCountryDistance':
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
	case 'GetBGPFullRoute':
	case 'GetBGPUpdate':
	case 'ExtractPHPDataFromBGPScanner':
		echo'  RC    : 取得するルートコレクタ',PHP_EOL,
			'  START : 取得を開始する日時 ex. 20180101.0000',PHP_EOL,
			'  END   : 取得を終了する日時',PHP_EOL,
			'          省略した場合はSTARTと同時刻',PHP_EOL;
		break;
	//------------ TrackOriginExactChangedPrefix, TrackOriginIncludeChangedPrefix ------------//
	case 'TrackOriginExactChangedPrefix':
	case 'TrackOriginIncludeChangedPrefix':
		echo'  RC    : 取得するルートコレクタ',PHP_EOL,
			'  START : 変更検出の基準となる日時',PHP_EOL,
			'          この日時と次（8時間後）の日時を比較して変更があったIPプレフィックスを追跡する',PHP_EOL,
			'  END   : 変更検出を行う期間の終了日時',PHP_EOL;
		break;
	//------------ TrackOriginExactChangedPrefix2, TrackOriginIncludeChangedPrefix2 ------------//
	case 'TrackOriginExactChangedPrefix2':
	case 'TrackOriginIncludeChangedPrefix2':
		echo'  RC   : 取得するルートコレクタ',PHP_EOL,
			'  DATE : 変更検出の基準となる日時',PHP_EOL,
			'         この日時と前（8時間前）の日時を比較して変更があったIPプレフィックスを1週間前から追跡する',PHP_EOL;
		break;
	//------------ AnalyseKindAndChangeNum, GroupChangesOfOriginAS ------------//
	case 'AnalyseKindAndChangeNum':
		echo'  FILENAME : TrackOrigin(Exact|Include)ChangedPrefixサブコマンドで出力されたcsvファイル',PHP_EOL;
		break;
	//------------ TrackAndAnalyseKindAndChangeNum ------------//
	case 'TrackAndAnalyseKindAndChangeNum':
		echo'  RC         : 取得するルートコレクタ',PHP_EOL,
			'  START|DATE : 変更検出の基準となる日時',PHP_EOL,
			'  END        : 変更検出を行う期間の終了日時指定がなければ過去方向に1週間追跡する',PHP_EOL;
		break;
	//------------ AnalyseBGPUpdate(Summary) ------------//
	case 'AnalyseBGPUpdate':
	case 'AnalyseBGPUpdateSummary':
		echo'  START : 分析対象の日付',PHP_EOL,
			'  END   : 複数の連続した日付のデータを分析する場合にその終了日を指定',PHP_EOL;
		break;
	//------------ CronBGPFullRoute/Update ------------//
	case 'CronBGPFullRoute':
	case 'CronBGPUpdate':
		echo'  RC         : 取得するルートコレクタ',PHP_EOL;
		break;
	//------------ ImpotSubmarineCableList ------------//
	case 'ImpotSubmarineCableList':
		echo'  <CABLE LIST> : SubmarineCableMapより取得したfusion-cables-(YmdHi).csv',PHP_EOL;
		break;
	//------------ GetWhoisInfoFromAsn ------------//
	case 'GetWhoisInfoFromAsn':
		echo'  <ASN> : Whois情報を取得するAS番号',PHP_EOL;
		break;
	//------------ ReApplyWhitelist ------------//
	case 'ReApplyWhitelist':
		echo'  <SUSPICIOUS_ID> : 指定がある場合はSuspiciousAsnSetの該当idのみホワイトリストを再適用する',PHP_EOL;
		break;
	//------------ 引数なしで実行可能 ------------//
	case 'FilterSuspiciousBGPUpdate':
	case 'CronFilterSuspiciousBGPUpdate':
	case 'CronASCountry':
	case 'CalcCountryDistance':
	case 'SummaryCountryDistance':
		break;
	}
	exit(1);
}

//==================== 実行完了 ====================//
endLogging();
?>
