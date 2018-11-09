<?php
function GroupChangesOfOriginAS($track_result){
	//==================== 引数チェック・前処理 ====================//
	// 引数チェック（ファイルの存在確認）
	if(!is_file($track_result)) showLog("ファイルが存在しません: $track_result", true);
	// 出力ディレクトリ名を作成
	$basename = explode('_', basename($track_result,'.csv'), 2);
	$outdir = GROUP_CHANGES_OF_ORIGINAS_RESULT . (strpos($basename[0],'Include')===false?'ExactMatch_':'IncludeMatch_') . $basename[1];
	if(is_dir($outdir)) showLog("出力先ディレクトリがすでに存在します: $outdir", true);
	mkdir($outdir);

	//==================== AnalysePrefixTrackingResultの読み込み ====================//
	// ファイルオープン
	showLog('AnalysePrefixTrackingResultの読み込み');
	$fp = fopen(ANALYSE_KIND_AND_CHANGE_NUM_RESULT.basename($outdir).'/AnalysePrefixTrackingResult.csv', 'r');
	$analyse_result = array();
	define('CHANGE_NUM',0);
	define('DATA_KIND_NUM',1);
	define('HAS_BLANK',2);
	// タイトル行スキップ
	fgets($fp);
	while(($row = fgets($fp))!== false){
		// カンマで分割
		$row_data = explode(',', rtrim($row));
		// データ行以外はスキップ
		if(!isset($row_data[1])) continue;
		// データを個別に保存
		// $analyse_result[$ip_prefix] = array($change_num, $data_kind_num, $has_blank);
		$analyse_result[$row_data[1]] = array((int)$row_data[2], (int)$row_data[3], (bool)$row_data[4]);
	}
	fclose($fp);

	//==================== ファイルを読み込む ====================//
	// 入力ファイルを開く
	showLog("ファイルの読み込み: $track_result");
	$fp = fopen($track_result, 'r');

	// タイトル行をスキップ（あとで流用するので保存）
	$title_row = fgets($fp);
	
	// データ保存用の配列
	$result = array($title_row, $title_row, $title_row, $title_row, $title_row, $title_row);
	// 行ごとに統計してく
	$base_ip_prefix = '0.0.0.0/0';
	while(($row = fgets($fp)) !== false){
		//------------ データ行以外のスキップ ------------//
		// v4とv6の境界の改行をスキップ
		if($row==="\n") continue;
		// カンマで分割
		$row_data = explode(',', rtrim($row));
		// base_ip_prefixを保存してcontinue
		if(!isset($row_data[1])){
			$base_ip_prefix = $row_data[0];
			continue;
		}
		
		//------------ IPプレフィックスの保存 ------------//
		$ip_prefix = $row_data[1];

		//------------ パターン分類 ------------//
		// まずは$analyse_resultで分類
		$type = -1;
		if    ($analyse_result[$ip_prefix][CHANGE_NUM]===0) $type=0;
		elseif($analyse_result[$ip_prefix][CHANGE_NUM]===1) $type=1;
		elseif($analyse_result[$ip_prefix][HAS_BLANK] ===true){
			if($analyse_result[$ip_prefix][DATA_KIND_NUM]===2) $type=3;
			else $type=5;
		}// 行を分析
		else{
			$type=2;
			$row_data = array_slice($row_data, 2);
			$always_exists = explode('/', $row_data[0]);
			foreach($row_data as $data){
				$data = explode('/', $data);
				// 今まで存在し続けたASが今期間も存在するかの確認
				for($i=0; $i<count($always_exists); $i++){
					// 存在しないASがあればリストから削除
					if(!in_array($always_exists[$i], $data)){
						unset($always_exists[$i]);
						$always_exists = array_values($always_exists);
					}
				}
				// 存在し続けたASがなくなればtypeを4にしてbreak
				if(count($always_exists)===0){
					$type=4;
					break;
				}
			}
		}
		
		$result[$type] .= $base_ip_prefix.$row;
	}
	fclose($fp);
	
	//==================== データの出力 ====================//
	showLog('結果の出力');
	$imax = count($result);
	for($i=0;$i<$imax;$i++) file_put_contents("$outdir/Type$i.csv", $result[$i]);
}

?>
