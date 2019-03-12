<?php
function AnalyseKindAndChangeNum($track_result){
	//==================== 引数チェック・前処理 ====================//
	// 引数チェック（ファイルの存在確認）
	if(!is_file($track_result) || substr($track_result, -4)!=='.csv') showLog("不正なファイル名です: $track_result", true);
	// 出力ディレクトリ名を作成
	$outdir = substr($track_result, 0, -4);
	if(is_dir($outdir)) showLog("出力先ディレクトリがすでに存在します: $outdir", true);
	mkdir($outdir);

	//==================== ファイルを読み込む ====================//
	// 入力ファイルを開く
	showLog("ファイルの読み込み: $track_result");
	$fpin = fopen($track_result, 'r');
	// 出力ファイル（AnalysePrefixTrackingResult.csv）を開く
	$fpout = fopen("$outdir/AnalysePrefixTrackingResult.csv", 'w');

	// タイトル行をスキップ
	$row = fgets($fpin);
	// タイトル行を出力
	fwrite($fpout, rtrim($row).',type,change_num,data_kind_num,has_blank,has_2as,data_num'.PHP_EOL);

	// データ保存用の配列
	$result = array();
	// 行ごとに統計してく
	$base_ip_prefix = '0.0.0.0/0';
	while(($row = fgets($fpin)) !== false){
		//------------ データ行以外のスキップ ------------//
		// カンマで分割
		$row = rtrim($row);
		$row_data = explode(',', $row);
		// base_ip_prefixを保存してcontinue
		if(!isset($row_data[1])){
			$base_ip_prefix = $row_data[0];
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
		$type = 2;
		$always_exists = explode('/', $row_data[0]);
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
			// type処理
			$asn = explode('/', $data);
			// 今まで存在し続けたASが今期間も存在するかの確認
			for($i=0; $i<count($always_exists); $i++){
				// 存在しないASがあればリストから削除
				if(!in_array($always_exists[$i], $asn)) unset($always_exists[$i]);
			}
			// キーを詰める
			$always_exists = array_values($always_exists);
			// 存在し続けたASがなくなればtypeを4にする
			if(count($always_exists)===0) $type=4;
		}
		// データ数を降順でソート（キー情報は失われる）
		rsort($data_nums);
		$data_kind_num = count($data_nums);
		// typeの決定（2,4以外）
		if    ($change_num===0) $type=0;
		elseif($change_num===1) $type=1;
		elseif($has_blank===1){
			if($data_kind_num===2) $type=3;
			else $type=5;
		}

		//------------ 行ごとの統計データの作成 ------------//
		// AnalysePrefixTrackingResult
		$row = "$base_ip_prefix$row,$type,$change_num,$data_kind_num,$has_blank,$has_2as";
		foreach($data_nums as $data_num){ $row .= ','.$data_num; }
		fwrite($fpout, $row.PHP_EOL);
		// AggregateKindAndChangeNum.csv
		if(isset($result[$change_num][$data_kind_num]))
			$result[$change_num][$data_kind_num]['count']++;
		else
			$result[$change_num][$data_kind_num]['count'] = 1;
	}
	fclose($fpin);
	fclose($fpout);
	
	//==================== データの統計 ====================//
	showLog('生データ・統計データを出力');
	// ファイルオープン
	$fp = fopen("$outdir/AggregateKindAndChangeNum.csv", 'w');
	// タイトル行の出力
	fwrite($fp, 'change_num, data_kind_num,prefix_count'.PHP_EOL);
	// データの出力
	ksort($result);
	foreach($result as $change_num => $result2){
		ksort($result2);
		foreach($result2 as $data_kind_num => $result3){
			//------------ AggregateKindAndChangeNum.csv ------------//
			fwrite($fp, "$change_num,$data_kind_num,{$result3['count']}".PHP_EOL);
		}
	}
	fclose($fp);
}
?>
