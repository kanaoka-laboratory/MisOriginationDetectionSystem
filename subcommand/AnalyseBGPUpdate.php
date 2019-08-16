<?php
function AnalyseBGPUpdate($rc, $start, $end = null){
	if(!isset(DIR_RC[$rc])) showLog('不正なルートコレクタです：'.$rc, true);
	if($end===null) $end = $start;

	global $mysqli;
	// 開始・終了時間を設定
	$ts = strtotime($start);
	$ts_end = strtotime($end);
	
	// 実行内容の表示
	showLog(date('Y-m-d H:i', $ts) . '〜' . date('Y-m-d H:i', $ts_end) . 'のアップデート情報の変更検出をします');

	// 実験対象となるフルルートのデータ情報

	$tmp_Hi = date('Hi', $ts);
	if($tmp_Hi>='1600')		$fullroute_filename = MakeFilenames($rc, strtotime(date('Y-m-d 16:00', $ts)))['fullroute_phpdata'];
	elseif($tmp_Hi>='0800')	$fullroute_filename = MakeFilenames($rc, strtotime(date('Y-m-d 08:00', $ts)))['fullroute_phpdata'];
	else					$fullroute_filename = MakeFilenames($rc, strtotime(date('Y-m-d 00:00', $ts)))['fullroute_phpdata'];
	if(!is_file($fullroute_filename)) showLog("実験対象となるフルルートのデータ（{$fullroute_filename}）がありません", true);
	$network_list = unserialize(file_get_contents($fullroute_filename));
	
	// 5分ごとに時間をずらしながら実行
	for(; $ts<=$ts_end; $ts+=UPDATE_INTERVAL[$rc]*60){
		// 事件対象となるフルルートのデータが変わる場合（$tsとフルルートのタイムスタンプが一致する）
		if(in_array(date('Hi',$ts), ['0000','0800','1600'], true)){
			$fullroute_filename = MakeFilenames($rc, $ts)['fullroute_phpdata'];
			if(!is_file($fullroute_filename)){
				showLog("実験対象となるフルルートのデータ（$fullroute_filename）がありません", true);
				continue;
			}
			$network_list = unserialize(file_get_contents($fullroute_filename));
		}
		
		// タイムスタンプからBGPDUMPのファイル名を作成
		$filename = MakeFilenames($rc, $ts);
		if(!is_file($filename['update_bgpscanner'])){
			showLog("BGPScannerファイルがありません: {$filename['update_bgpscanner']}");
			continue;
		}
		showLog("{$filename['update_bgpscanner']} の読み込み");
		
		//------------ 1行ずつ読み込み ------------//
		// Advertisementのみを取得し，(OriginAS,AS番号)の重複を削除
		$update_list = array('v4'=>array(), 'v6'=>array());
		foreach(file($filename['update_bgpscanner'], FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES) as $row){
			// データを分割（IGNORE_NEW_LINESで読み込んでるのでrtrim不要）
			$exploded_row = explode('|', $row);
			// advertisement以外は無視
			if($exploded_row[0]!=='+') continue;
			// それぞれの要素を変数に保存
			list($type, $ip_prefix_list, $as_path, $peer_ip, $origin_attr, $null1, $null2, $null3, $peer_info, $timestamp, $null4) = $exploded_row;
			$datetime = (new DateTime(null, new DateTimeZone('UTC')))->setTimestamp($timestamp)->format('Y-m-d H:i:s');

			// すべてのip_prefixに対して実行
			foreach(explode(' ', $ip_prefix_list) as $ip_prefix){
				// $ip_protoの検出
				$ip_proto = strpos($ip_prefix, ':')===false? 'v4': 'v6';
				// すべてのOriginASを$update_listに追加
				$as_path_list = explode(' ', $as_path);
				foreach(explode(',', str_replace(array('{','}'), '', end($as_path_list))) as $origin_as){
					if(!isset($update_list[$ip_proto][$ip_prefix][$origin_as]))
						$update_list[$ip_proto][$ip_prefix][$origin_as] = $datetime;
				}
			}
		}
	
		//------------ 変更検出 ------------//
		// ファイルをオープンしてタイトル行の出力
		$fp = fopen($filename['analyse_advertisement'], 'w');
		fwrite($fp, 'adv_type,ip_prefix,conflict_ip_prefix,asn,conflict_asn,datetime'.PHP_EOL);

		// トランザクション開始
		$mysqli->begin_transaction();

		// advertisementを以下の5種類に分類する
		// 1. フルルートに重複するIPプレフィックスがなく，全く新しい経路の追加
		// 2. フルルートに全く同じIPプレフィックスが存在し，OriginASが同じである（KeepAlive？）
		// 3. フルルートに全く同じIPプレフィックスが存在し，OriginASが異なる
		// 4. フルルートに衝突する（含むor含まれる）IPプレフィックスが存在し，OriginASが同じ（ハイジャックへの防御？）
		// 5. フルルートに衝突する（含むor含まれる）IPプレフィックスが存在し，OriginASが異なる（ハイジャック？）
		// type4,5は衝突するIPプレフィックスをconflict_ip_prefixとして保存する
		// type3,5は衝突・一致したIPプレフィックスを所有するASの番号をconflict_asnとして保存する
		foreach(['v4','v6'] as $ip_proto){
			foreach($update_list[$ip_proto] as $ip_prefix => $ip_prefix_info){
				// フルルートに全く同じIPプレフィックスが存在する（type2 or type3）
				if(isset($network_list[$ip_proto][$ip_prefix])){
					$conflict_asn = implode('/', array_slice(array_keys($network_list[$ip_proto][$ip_prefix]), 2));
					foreach($ip_prefix_info as $asn => $datetime){
						// フルルートに同じasnがある（type2）
						if(isset($network_list[$ip_proto][$ip_prefix][$asn])){
							fwrite($fp, "2,$ip_prefix,$ip_prefix,$asn,$conflict_asn,$datetime".PHP_EOL);
						}
						// フルルートに同じasnがない（type3）
						else{
							fwrite($fp, "3,$ip_prefix,$ip_prefix,$asn,$conflict_asn,$datetime".PHP_EOL);
							$mysqli->query("insert into PrefixConflictedUpdate (ip_protocol, adv_type, asn, conflict_asn, ip_prefix, conflict_ip_prefix, date_update, rc) ".
									"values('$ip_proto', 3, $asn, '$conflict_asn', '$ip_prefix', '$ip_prefix', '$datetime', '$rc') ".
									"on duplicate key update count=count+1");
						}
					}
				}// フルルートに全く同じIPプレフィックスが存在しない（type1 or type4 or type5）
				else{
					// falseならtype1，重複が見つかったらIPプレフィックスを保存（type4 or type5）
					$conflict=false;
					// ネットワークアドレス，ブロードキャストアドレスを算出
					list($network, $broadcast) = getNetworkBroadcast($ip_prefix, $ip_proto);
					// フルルートのダンプはIPアドレスが昇順，同じIPアドレスはプレフィックス長で昇順に並んでいるのでこれを利用．
					// 重複するIPの場合，最初に一番広いIPプレフィックス，その後それに含まれるプレフィックスが並んでくる．
					// $network_listをforeachすることで，一番小さいIPからだんだんスライドしていき，$ip_prefixに届く（追い越す）まで繰り返す
					foreach($network_list[$ip_proto] as $fullroute_ip_prefix => $fullroute_ip_prefix_info){
						//まだ重複までたどり着いていない
						if($fullroute_ip_prefix_info['broadcast'] < $broadcast) continue;
						// 追い越した＝重複するIPプレフィックスがなかった（type1） or IPプレフィックスの重複はあったが一致するasnがなかった（type5）
						if($network < $fullroute_ip_prefix_info['network']){
							foreach($ip_prefix_info as $asn => $datetime){
								// type1：衝突せずに追い越した
								if($conflict===false){
									fwrite($fp, "1,$ip_prefix,-,$asn,-,$datetime".PHP_EOL);
								}// type5：重複はあったが一致はなかった
								else{
									fwrite($fp, "5,$ip_prefix,{$conflict['ip_prefix']},$asn,{$conflict['asn']},$datetime".PHP_EOL);
									$mysqli->query("insert into PrefixConflictedUpdate (ip_protocol, adv_type, asn, conflict_asn, ip_prefix, conflict_ip_prefix, date_update, rc) ".
											"values('$ip_proto', 5, $asn, '{$conflict['asn']}', '$ip_prefix', '{$conflict['ip_prefix']}', '$datetime', '$rc') ".
											"on duplicate key update count=count+1");
								}
							}
							break;
						}
						// IPプレフィックスが重複している（type4 or type5）
						$conflict = array(	'ip_prefix'=>$fullroute_ip_prefix,
											'asn'=>implode('/', array_slice(array_keys($fullroute_ip_prefix_info), 2)) );
						// OriginASが同じものが見つかればtype4
						foreach(array_keys($ip_prefix_info) as $asn){
							// OriginASが同じものが見つかった（type4）
							if(isset($fullroute_ip_prefix_info[$asn])){
								fwrite($fp, "4,$ip_prefix,{$conflict['ip_prefix']},$asn,{$conflict['asn']},$datetime".PHP_EOL);
								unset($ip_prefix_info[$asn]);
							}
						}
						// ip_prefix_infoが空になった（$ip_prefixをadvertiseしたASは全部type4だった）らこれ以上は不要
						if(count($ip_prefix_info)===0) break;
					}
				}
			}
		}
		// MySQLコミット・ファイルクローズ
		$mysqli->commit();
		fclose($fp);
	}
}
?>
