<?php
function TrackOriginExactChangedPrefix2($rc, $date){
	// 引数の処理
	if(!isset(DIR_RC[$rc])) showLog('不正なルートコレクタです：'.$rc, true);
	$base_ts = $ts = strtotime($date);
	
	//==================== 基準時データ読み込み ====================//
	// ファイル名を作成
	$filename = MakeFilenames($rc, $ts);
	// ファイルの存在確認
	if(!is_file($filename['fullroute_phpdata'])) showLog("PHPDataファイルが存在しません: {$filename['fullroute_phpdata']}", true);
	// 読み込み
	showLog("{$filename['fullroute_phpdata']} の読み込み");
	$next_network_list = unserialize(file_get_contents($filename['fullroute_phpdata']));	
	
	//==================== 基準時の前のデータ読み込み ====================//
	$ts -= 60*60*8;
	// ファイル名を作成
	$filename = MakeFilenames($rc, $ts);
		// ファイルの存在確認
	if(!is_file($filename['fullroute_phpdata'])) showLog("PHPDataファイルが存在しません: {$filename['fullroute_phpdata']}", true);
	// 読み込み
	showLog("{$filename['fullroute_phpdata']} の読み込み");
	$prev_network_list = unserialize(file_get_contents($filename['fullroute_phpdata']));	
	
	//==================== 変更抽出 ====================//
	$origin_prev_network_list = $prev_network_list;
	// 変更を保存する配列
	$update_list = array( 'v4'=>array(), 'v6'=>array() );
	//------------ 重複する経路を$prev_network_listからunset ------------//
	foreach(['v4','v6'] as $ip_proto){
		showLog("変更検出 ($ip_proto)");
		// nextで追加された経路を検出
		foreach($next_network_list[$ip_proto] as $prefix => $as_info){
			foreach($as_info as $asn => $true){
				// 変更がない（変更前・変更後どちらにも同じ経路が存在する）場合はスキップ
				if(isset($prev_network_list[$ip_proto][$prefix][$asn])){
					unset($prev_network_list[$ip_proto][$prefix][$asn]);
					continue;
				}// 変更がある場合は変更を保存する
				else{
					// update_listに，nextでprefixを広告しているASを保存
					$update_list[$ip_proto][$prefix][] = array_slice(array_keys($next_network_list[$ip_proto][$prefix]), 2);	// array_keys($as_info)と同義
					// ############### ↑↓$update_listに格納する順序は時系列とは逆なのでこの順番 ###############
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
					continue 2;
				}
			}
		}
		// $prev_network_listに残った（削除された）経路を検出
		foreach($prev_network_list[$ip_proto] as $prefix => $as_info){
			// すべて消えている（prevとnextが完全一致してる）場合は無視
			if(count($as_info)===0) continue;
			// nextでprefixを広告していたASを保存
			if(isset($next_network_list[$ip_proto][$prefix]))
				$update_list[$ip_proto][$prefix][] = array_slice(array_keys($next_network_list[$ip_proto][$prefix]), 2);	// array_keys($as_info)と同義
			else
				$update_list[$ip_proto][$prefix][] = array();
			// ############### ↑↓$update_listに格納する順序は時系列とは逆なのでこの順番 ###############
			// prebでprefixを広告していたASを保存
			$update_list[$ip_proto][$prefix][] = array_slice(array_keys($origin_prev_network_list[$ip_proto][$prefix]), 2);	
		}
	}
	// メモリ解放
	$origin_prev_network_list = null;
	$prev_network_list = null;
	$next_network_list = null;

	//==================== 1週間追いかける ====================//
	// 1週間 = 7日間 = 8時間 x (3x7) =>21回分の経路情報
	// 最後の2回は変更検出で読み込んでいるので残りは19回分
	for($i=0;$i<19;$i++){
		// タイムスタンプを更新
		$ts -= 60*60*8;
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
				if(isset($network_list[$ip_proto][$ip_prefix]))
					$update_list[$ip_proto][$ip_prefix][] = array_slice(array_keys($network_list[$ip_proto][$ip_prefix]), 2);
				else
					$update_list[$ip_proto][$ip_prefix][] = array();
			}
		}
	}
	// この時点で$tsには一番古いタイムスタンプが入っている

	//==================== 結果の出力 ====================//
	// 結果の出力
	$filename = MakeFilenames($rc, $base_ts);
	showLog("結果の出力: {$filename['track_exact_change2']}");
	$fp = fopen($filename['track_exact_change2'], 'w');

	// ヘッダ
	$row = 'base_ip_prefix,ip_prefix';
	for(; $ts<=$base_ts; $ts+=60*60*8){ $row .= date(',Y/m/d H:i:s', $ts); }
	fwrite($fp, $row.PHP_EOL);
	// base_ip_prefix
	fwrite($fp, '0.0.0.0/0'.PHP_EOL);
	
	// データ行
	foreach(['v4','v6'] as $ip_proto){
		foreach($update_list[$ip_proto] as $ip_prefix => $as_info){
			$row =  ",$ip_prefix";
			foreach(array_reverse($as_info) as $asn_list){
				sort($asn_list);
				$row .= ','.implode('/', $asn_list);
			}
			fwrite($fp, $row.PHP_EOL);
		}
	}

	// クローズ
	fclose($fp);

	// 結果ファイルのファイル名を返す
	return $filename['track_exact_change2'];
}
?>
