<?php
function AnalyseKindAndChangeNum($track_result){
	//==================== 引数チェック・前処理 ====================//
	// 引数チェック（ファイルの存在確認）
	if(!is_file($track_result)) showLog("ファイルが存在しません: $track_result", true);
	// 出力ディレクトリ名を作成
	$basename = explode('_', basename($track_result,'.csv'), 2);
	$outdir = ANALYSE_KIND_AND_CHANGE_NUM_RESULT . (strpos($basename[0],'Include')===false?'ExactMatch_':'IncludeMatch_') . $basename[1];
	if(is_dir($outdir)) showLog("出力先ディレクトリがすでに存在します: $outdir", true);
	mkdir($outdir);

	//==================== ファイルを読み込む ====================//
	// 入力ファイルを開く
	showLog("ファイルの読み込み: $track_result");
	$fpin = fopen($track_result, 'r');
	// 出力ファイル（AnalysePrefixTrackingResult.csv）を開く
	$fpout = fopen("$outdir/AnalysePrefixTrackingResult.csv", 'w');

	// タイトル行をスキップ
	fgets($fpin);
	// タイトル行を出力
	fwrite($fpout, 'base_ip_prefix,ip_prefix,change_num,data_kind_num,has_blank,has_2as,data_num'.PHP_EOL);

	// データ保存用の配列
	$result = array();
	// 行ごとに統計してく
	$base_ip_prefix = '0.0.0.0/0';
	while(($row = fgets($fpin)) !== false){
		//------------ データ行以外のスキップ ------------//
		// v4とv6の境界の改行をスキップ
		if($row==="\n") continue;
		// カンマで分割
		$row_data = explode(',', rtrim($row));
		// base_ip_prefixを保存してcontinue
		if(!isset($row_data[1])){
			$base_ip_prefix = $row_data[0];
			fwrite($fpout, $base_ip_prefix.PHP_EOL);
			continue;
		}
		
		//------------ データの分析 ------------//
		// IPプレフィックスの保存
		$ip_prefix = $row_data[1];
		// $row_dataをデータ部のみに
		$row_data = array_slice($row_data, 2);
		$data_nums = array();
		$prev_data = $row_data[0];
		$has_blank = 0;
		$has_2as = 0;
		$change_num = 0;
		foreach($row_data as $data){
			// データの切り替わりがあればカウント
			if($data!==$prev_data) $change_num++;
			$prev_data = $data;
			// 空白・2ASのデータが有れば記録
			if($data==='') $has_blank=1;
			if(strpos($data,'/')!==false) $has_2as=1;
			// データの数を保存
			if(isset($data_nums[$data])) $data_nums[$data]++;
			else $data_nums[$data]=1;
		}
		// データ数を降順でソート（キー情報は失われる）
		rsort($data_nums);
		
		//------------ 行ごとの統計データの作成 ------------//
		// AnalysePrefixTrackingResult
		$data_kind_num = count($data_nums);
		$rowtmp = ",$ip_prefix, $change_num,$data_kind_num,$has_blank,$has_2as";
		foreach($data_nums as $data_num){ $rowtmp .= ','.$data_num; }
		fwrite($fpout, $rowtmp.PHP_EOL);
		// AggregateKindAndChangeNum.csv, RawData
		if(isset($result[$change_num][$data_kind_num])){
			$result[$change_num][$data_kind_num]['count']++;
			$result[$change_num][$data_kind_num]['text'] .= $base_ip_prefix.$row;
		}else{
			$result[$change_num][$data_kind_num]['count'] = 1;
			$result[$change_num][$data_kind_num]['text'] = $base_ip_prefix.$row;
		}
	}
	fclose($fpin);
	fclose($fpout);
	
	//==================== データの統計 ====================//
	showLog('生データ・統計データを出力');
	// ファイルオープン
	$fp = fopen("$outdir/AggregateKindAndChangeNum.csv", 'w');
	mkdir("$outdir/RawData");
	// タイトル行の出力
	fwrite($fp, 'change_num, data_kind_num,prefix_count'.PHP_EOL);
	// データの出力
	ksort($result);
	foreach($result as $change_num => $result2){
		ksort($result2);
		foreach($result2 as $data_kind_num => $result3){
			//------------ AggregateKindAndChangeNum.csv ------------//
			fwrite($fp, "$change_num,$data_kind_num,{$result3['count']}".PHP_EOL);
			//------------ RawData ------------//
			file_put_contents("$outdir/RawData/$change_num-$data_kind_num.csv", $result3['text']);
		}
	}
	fclose($fp);
}
?>
