<?php
function TrackOriginExactChangedPrefix($rc, $start, $end){
	// 引数チェック
	if(!isset(DIR_RC[$rc])) showLog('不正なルートコレクタです：'.$rc, true);
	$start_ts = $ts = strtotime($start);
	$end_ts = strtotime($end);
	if($start_ts>$end_ts) showLog('終了日時が開始日時より前です', true);
	
	//==================== 基準時データ読み込み ====================//
	// ファイル名を作成
	$filename = MakeFilenames($rc, $ts);
	// ファイルの存在確認
	if(!is_file($filename['fullroute_phpdata'])) showLog("PHPDataファイルが存在しません: {$filename['fullroute_phpdata']}", true);
	// 読み込み
	showLog("{$filename['fullroute_phpdata']} の読み込み");
	$prev_network_list = unserialize(file_get_contents($filename['fullroute_phpdata']));

	//==================== 基準時の次のデータ読み込み ====================//
	$ts += 60*60*8;
	// ファイル名を作成
	$filename = MakeFilenames($rc, $ts);
	// ファイルの存在確認
	if(!is_file($filename['fullroute_phpdata'])) showLog("PHPDataファイルが存在しません: {$filename['fullroute_phpdata']}", true);
	// 読み込み
	showLog("{$filename['fullroute_phpdata']} の読み込み");
	$next_network_list = unserialize(file_get_contents($filename['fullroute_phpdata']));

	//==================== 変更抽出 ====================//
	showLog('変更抽出');
	$origin_prev_network_list = $prev_network_list;
	// 変更を保存する配列
	$update_list = array( 'v4'=>array(), 'v6'=>array() );
	//------------ 重複する経路を$prev_network_listからunset ------------//
	foreach(['v4','v6'] as $ip_proto){
		// nextで追加された経路を検出
		foreach($next_network_list[$ip_proto] as $prefix => $as_info){
			foreach($as_info as $asn => $true){
				// 変更がない（変更前・変更後どちらにも同じ経路が存在する）場合はスキップ
				if(isset($prev_network_list[$ip_proto][$prefix][$asn])){
					unset($prev_network_list[$ip_proto][$prefix][$asn]);
					continue;
				}// 変更がある場合は変更を保存する
				else{
					// prevにも経路は存在している
					if(isset($prev_network_list[$ip_proto][$prefix])){
						unset($prev_network_list[$ip_proto][$prefix]);
						// update_listに経路を登録，prevでprefixを広告していたASを保存
						$update_list[$ip_proto][$prefix][] = array_slice(array_keys($origin_prev_network_list[$ip_proto][$prefix]), 2);
					}// nextに初めて現れた経路情報
					else{
						// update_listに経路を登録，prevでprefixを広告していたASは無いので空の配列を保存
						$update_list[$ip_proto][$prefix][] = array();
					}
					// update_listに，nextでprefixを広告しているASを保存
					$update_list[$ip_proto][$prefix][] = array_slice(array_keys($next_network_list[$ip_proto][$prefix]), 2);	// array_keys($as_info)と同義
					continue 2;
				}
			}
		}
		// $prev_network_listに残った（削除された）経路を検出
		foreach($prev_network_list[$ip_proto] as $prefix => $as_info){
			// すべて消えている（prevとnextが完全一致してる）場合は無視
			if(count($as_info)===0) continue;
			// prebでprefixを広告していたASを保存
			$update_list[$ip_proto][$prefix][] = array_slice(array_keys($origin_prev_network_list[$ip_proto][$prefix]), 2);
			// nextでprefixを広告していたASを保存
			if(isset($next_network_list[$ip_proto][$prefix]))
				$update_list[$ip_proto][$prefix][] = array_slice(array_keys($next_network_list[$ip_proto][$prefix]), 2);	// array_keys($as_info)と同義
			else
				$update_list[$ip_proto][$prefix][] = array();
		}
		// 配列のソート
		ksort($update_list[$ip_proto]);
	}
	// メモリ解放
	$origin_prev_network_list = null;
	$prev_network_list = null;
	$next_network_list = null;

	//==================== 追いかける ====================//
	for($ts += 60*60*8; $ts < $end_ts; $ts += 60*60*8){
		// ファイル名を作成
		$filename = MakeFilenames($rc, $ts);
		// ファイルの存在確認
		if(!is_file($filename['fullroute_phpdata'])) showLog("PHPDataファイルが存在しません: {$filename['fullroute_phpdata']}", true);
		// 読み込み
		showLog("{$filename['fullroute_phpdata']} の読み込み");
		$network_list = unserialize(file_get_contents($filename['fullroute_phpdata']));

		//------------ update_listに追加 ------------//
		foreach(['v4','v6'] as $ip_proto){
			foreach($update_list[$ip_proto] as $ip_prefix => $asn){
				if(isset($network_list[$ip_proto][$ip_prefix])){
					$origin_as_list = array_keys($network_list[$ip_proto][$ip_prefix]);
					unset($origin_as_list[0]);	// network
					unset($origin_as_list[1]);	// bradcast
					$update_list[$ip_proto][$ip_prefix][] = $origin_as_list;
				}else{
					$update_list[$ip_proto][$ip_prefix][] = array();
				}
			}
		}
	}

	//==================== 結果の出力 ====================//
	// 結果の出力
	$filename = MakeFilenames($rc, $start_ts);
	$filename['track_exact_change'] = AppendStrToFilename($filename['track_exact_change'], '_'.date('Ymd.Hi', $end_ts));
	showLog("結果の出力: {$filename['track_exact_change']}");
	$fp = fopen($filename['track_exact_change'], 'w');

	// ヘッダ
	$row = 'base_ip_prefix,ip_prefix';
	for($ts=$start_ts; $ts<$end_ts; $ts+=60*60*8){ $row .= date(',Y/m/d H:i:s', $ts); }
	fwrite($fp, $row.PHP_EOL);
	// base_ip_prefix
	fwrite($fp, '0.0.0.0/0'.PHP_EOL);
	
	// データ行
	foreach(['v4','v6'] as $ip_proto){
		foreach($update_list[$ip_proto] as $ip_prefix => $as_info){
			$row =  ",$ip_prefix";
			foreach($as_info as $asn_list){
				 sort($asn_list);
				$row .= ','.implode('/', $asn_list);
			}
			fwrite($fp, $row.PHP_EOL);
		}
	}

	// クローズ
	fclose($fp);

	// 結果ファイルのファイル名を返す
	return $filename['track_exact_change'];
}
?>
