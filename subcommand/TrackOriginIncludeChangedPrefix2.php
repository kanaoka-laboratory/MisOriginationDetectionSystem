<?php
function TrackOriginIncludeChangedPrefix2($date){
	// 引数処理
	$base_ts = $ts = strtotime($date);

	//==================== 基準時データ読み込み ====================//
	// ファイル名を作成
	$ripe = MakeRIPEParam($ts);
	// ファイルの存在確認
	if(!is_file($ripe['phpdata'])) showLog("PHPDataファイルが存在しません: $filename", true);
	// 読み込み
	showLog("{$ripe['phpdata']} の読み込み");
	$next_network_list = unserialize(file_get_contents($ripe['phpdata']));	
	
	//==================== 基準時の前のデータ読み込み ====================//
	$ts -= 60*60*8;
	$ripe = MakeRIPEParam($ts);
	if(!is_file($ripe['phpdata'])) showLog("PHPDataファイルが存在しません: $filename", true);
	// 読み込み
	showLog("{$ripe['phpdata']} の読み込み");
	$prev_network_list = unserialize(file_get_contents($ripe['phpdata']));	
	
	//==================== 変更抽出 ====================//
	// RIPEのBGPDUMPはIPアドレスが昇順，同じIPアドレスはプレフィックス長で昇順に並んでいるのでこれを利用．
	// 重複するIPの場合，最初に一番広いIPプレフィックス，その後それに含まれるプレフィックスが並んでくる．
	// なので一番広いIPプレフィックスに関してfor文を回す（重複に関してはスキップしながら処理する）
	// 変更を保存する配列
	$update_list = array( 'v4'=>array(), 'v6'=>array() );
	//------------ 変更検出 ------------//
	foreach(['v4','v6'] as $ip_proto){
		showLog("変更検出 ($ip_proto)");
		// 各配列のkeyリストを取得
		$prev_network_list_keys = array_keys($prev_network_list[$ip_proto]);
		$next_network_list_keys = array_keys($next_network_list[$ip_proto]);
		$prev_key_max = count($prev_network_list_keys);
		$next_key_max = count($next_network_list_keys);
		// 0時のIPプレフィックスについて繰り返し：$prev_network_list[$ip_proto][$prev_network_list_keys[$key]]についての繰り返し
		for($prev_key=0; $prev_key<$prev_key_max; $prev_key++){
			$ip_prefix = $prev_network_list_keys[$prev_key];
			$ip_prefix_broadcast = $prev_network_list[$ip_proto][$ip_prefix]['broadcast'];
			// showLog('変更検出：'.$ip_prefix);
			// 当該IPプレフィックスおよびそれに含まれるIPプレフィックスでOriginASの変更がある場合はExceptionを投げて処理
			try{
				// next_network_listに当該プレフィックスが存在する場合
				if(isset($next_network_list[$ip_proto][$ip_prefix])){
					$next_key=array_search($ip_prefix, $next_network_list_keys, true);
					
					// 当該プレフィックスに含まれるプレフィックスの消失がある場合は追跡
					for($key=$prev_key+1; $key<$prev_key_max && $prev_network_list[$ip_proto][$prev_network_list_keys[$key]]['network'] < $ip_prefix_broadcast; $key++){
						if(!isset($next_network_list[$ip_proto][$prev_network_list_keys[$key]]))
							throw new Exception();
					}// 当該プレフィックスに含まれるプレフィックスの追加がある場合は追跡
					for($key=$next_key+1; $key<$next_key_max && $next_network_list[$ip_proto][$next_network_list_keys[$key]]['network'] < $ip_prefix_broadcast; $key++){
						if(!isset($prev_network_list[$ip_proto][$next_network_list_keys[$key]]))
							throw new Exception();
					}

					// 当該プレフィックスに含まれるプレフィックスのOriginASに変更がある場合は追跡
					$base_origin_as_list = array_keys($prev_network_list[$ip_proto] [$ip_prefix]);
					sort($base_origin_as_list);
					$base_origin_as_list = implode($base_origin_as_list);
					
					// 重なるIPプレフィックスのOriginASが同じかどうかを検索
					// next_network_listを検索
					for($key=$next_key; $key<$next_key_max && $next_network_list[$ip_proto][$next_network_list_keys[$key]]['network'] < $ip_prefix_broadcast; $key++){
						// OriginASが異なる場合は追いかける
						$origin_as_list = array_keys($next_network_list[$ip_proto][$next_network_list_keys[$key]]);
						sort($origin_as_list);
						if($base_origin_as_list !== implode($origin_as_list))
							throw new Exception();
					}// prev_network_listを検索
					for($key=$prev_key+1; $key<$prev_key_max && $prev_network_list[$ip_proto][$prev_network_list_keys[$key]]['network'] < $ip_prefix_broadcast; $key++){
						// OriginASが異なる場合は追いかける
						$origin_as_list = array_keys($prev_network_list[$ip_proto][$prev_network_list_keys[$key]]);
						sort($origin_as_list);
						if($base_origin_as_list !== implode($origin_as_list))
							throw new Exception();
					}
				}// next_network_listに当該プレフィックスが存在しない場合：追いかける
				else{
					throw new Exception();
				}
			}catch(Exception $e){
				// 配列の作成
				$update_list[$ip_proto][$ip_prefix] = array('network'	=> $prev_network_list[$ip_proto][$ip_prefix]['network'],
															'broadcast'	=> $prev_network_list[$ip_proto][$ip_prefix]['broadcast'] );
				// プレフィックスとそのOriginASリストの追加
				for(; $prev_key<$prev_key_max && $prev_network_list[$ip_proto][$prev_network_list_keys[$prev_key]]['network'] < $ip_prefix_broadcast; $prev_key++){			
					$origin_as_list = array_keys($prev_network_list[$ip_proto][$prev_network_list_keys[$prev_key]]);
					unset($origin_as_list[0]);	// network
					unset($origin_as_list[1]);	// bradcast
					sort($origin_as_list);		// 並び替え
					$update_list[$ip_proto][$ip_prefix][$prev_network_list_keys[$prev_key]][$ts] = implode('/', $origin_as_list);
					// showLog('変更検出スキップ：'.$prev_network_list_keys[$prev_key]);
				}
			}
		}
	}
	// メモリ解放
	$prev_network_list = null;
	$next_network_list = null;
	$prev_network_list_keys = null;
	$next_network_list_keys = null;

	//==================== 1週間追いかける ====================//
	// 1週間 = 7日間 = 8時間 x (3x7) =>21回分の経路情報
	// 最後の2回は変更検出で読み込んでいるので残りは19回分
	for($i=0;$i<19;$i++){
		// タイムスタンプを更新
		$ts -= 60*60*8;
		
		// ファイル名を作成
		$ripe = MakeRIPEParam($ts);
		// ファイルの存在確認
		if(!is_file($ripe['phpdata'])) showLog("PHPDataファイルが存在しません: $filename", true);
		// network_listの読み込み
		showLog("{$ripe['phpdata']} の読み込み");
		$network_list = unserialize(file_get_contents($ripe['phpdata']));	
		
		//------------ IncludeMatchによるフィルタリング ------------//
		foreach(['v4','v6'] as $ip_proto){
			$network_list_keys = array_keys($network_list[$ip_proto]);
			$key_max = count($network_list_keys);
			$key = 0;
			// update_listの全てのbase_ip_prefixについてIncludeMatchを行う
			foreach($update_list[$ip_proto] as $base_ip_prefix => $base_ip_prefix_info){
				// $network_listのIPプレフィックスが現在のbase_ip_prefixより後になったら次のbase_ip_prefixに移る
				for(; $key<$key_max; $key++){
					// 当該IPプレフィックスは$base_ip_prefixより前の範囲にある：スキップ
					if($network_list[$ip_proto][$network_list_keys[$key]]['network'] < $base_ip_prefix_info['network'])
						continue;
					// 当該IPプレフィックスは$base_ip_prefixに重なる（含んでいる or 含まれる）
					if($network_list[$ip_proto][$network_list_keys[$key]]['network'] <= $base_ip_prefix_info['broadcast']){
						// 該当IPプレフィックスは$base_ip_prefixに含まれる：情報を保存
						if($network_list[$ip_proto][$network_list_keys[$key]]['broadcast'] <= $base_ip_prefix_info['broadcast']){
							$origin_as_list = array_keys($network_list[$ip_proto][$network_list_keys[$key]]);
							unset($origin_as_list[0]);	// network
							unset($origin_as_list[1]);	// bradcast
							sort($origin_as_list);		// 並び替え
							$update_list[$ip_proto][$base_ip_prefix][$network_list_keys[$key]][$ts] = implode('/', $origin_as_list);
						}
					}else{
						continue 2;
					}
				}
			}
		}
	}
	// この時点で$tsには一番古いタイムスタンプが入っている
	$oldest_ts = $ts;

	//==================== 結果の出力 ====================//
	// 出力ファイル名を作成
	$filebasename = TRACK_PREFIX_RESULT2 . 'TrackOriginIncludeChangedPrefix2_' . date('Ymd_Hi', $base_ts) . '.csv';
	
	// 結果の出力
	showLog("結果の出力: $filebasename");
	$fp = fopen($filebasename, 'w');

	// ヘッダ
	$row = 'base_ip_prefix,ip_prefix';
	for($ts=$oldest_ts; $ts<=$base_ts; $ts+=60*60*8){ $row .= date(',Y/m/d H:i:s', $ts); }
	fwrite($fp, $row.PHP_EOL);

	// データ行
	foreach(['v4','v6'] as $ip_proto){
		foreach($update_list[$ip_proto] as $base_ip_prefix => $base_ip_prefix_info){
			fwrite($fp, $base_ip_prefix.PHP_EOL);
			foreach($base_ip_prefix_info as $ip_prefix => $ip_prefix_info){
				if($ip_prefix==='network' || $ip_prefix==='broadcast') continue;
				$row = ",$ip_prefix";
				for($ts=$oldest_ts; $ts<=$base_ts; $ts+=60*60*8){
					$row .= isset($ip_prefix_info[$ts])? ','.$ip_prefix_info[$ts]: ',';
				}
				fwrite($fp, $row.PHP_EOL);
			}
		}
		fwrite($fp, PHP_EOL);
	}

	// クローズ
	fclose($fp);

	// 結果ファイルのファイル名を返す
	return $filebasename;
}
?>
