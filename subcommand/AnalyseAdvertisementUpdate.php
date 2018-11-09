<?php
function AnalyseAdvertisementUpdate($start, $end = null){
	if($end===null) $end = $start;
	// 開始・終了時間を設定
	$ts = strtotime($start);
	$ts_end = strtotime($end);
	
	// 実行内容の表示
	showLog(date('Y-m-d H:i', $ts) . '〜' . date('Y-m-d H:i', $ts_end) . 'のアップデート情報の変更検出をします');

	// 実験対象となるフルルートのデータ情報

	$tmp_Hi = date('Hi', $ts);
	if($tmp_Hi>='1600')		$ripe_full = MakeRIPEParam(strtotime(date('Y-m-d 16:00', $ts)));
	elseif($tmp_Hi>='0800')	$ripe_full = MakeRIPEParam(strtotime(date('Y-m-d 08:00', $ts)));
	else					$ripe_full = MakeRIPEParam(strtotime(date('Y-m-d 00:00', $ts)));
	if(!is_file($ripe_full['phpdata'])) showLog("実験対象となるフルルートのデータ（{$ripe_full['phpdata']}）がありません", true);
	$network_list = unserialize(file_get_contents($ripe_full['phpdata']));
	
	// 5分ごとに時間をずらしながら実行
	for(; $ts<=$ts_end; $ts+=60*5){
		// 事件対象となるフルルートのデータが変わる場合（$tsとフルルートのタイムスタンプが一致する）
		if(in_array(date('Hi',$ts), ['0000','0800','1600'], true)){
			$ripe_full = MakeRIPEParam($ts);
			if(!is_file($ripe_full['phpdata'])) showLog("実験対象となるフルルートのデータ（{$ripe_full['phpdata']}）がありません", true);
			$network_list = unserialize(file_get_contents($ripe_full['phpdata']));
		}
		
		// タイムスタンプからBGPDUMPのファイル名を作成
		$ripe = MakeRIPEUpdateParam($ts);
		if(!is_file($ripe['bgpdump'])) showLog("BGPDUMPファイルがありません: {$ripe['bgpdump']}", true);
		showLog("{$ripe['bgpdump']} の読み込み");
		
		//------------ 1行ずつ読み込み ------------//
		// Advertisementのみを取得し，(OriginAS,AS番号)の重複を削除
		$update_list = array('v4'=>array(), 'v6'=>array());
		$fp = fopen($ripe['bgpdump'], 'r');
		while(($row=fgets($fp)) !== false){
			// データを分割
			$exploded_row = explode('|', rtrim($row));
			// advertisement以外は無視
			if($exploded_row[2]!=='A') continue;
			// それぞれの要素を変数に保存
			list($protocol, $datetime, $type, $peer_ip, $peer_asn, $ip_prefix, $as_path, $origin_attr) = $exploded_row;
			// $ip_protoの検出
			$ip_proto = strpos($ip_prefix, ':')===false? 'v4': 'v6';
			// // $update_list[$ip_proto][$ip_prefix]を作成してネットワークアドレス・ブロードキャストアドレスを登録
			// // 変更検出時に計算するのでここではやらない
			// if(!isset($update_list[$ip_proto][$ip_prefix])){
			// 	list($network, $broadcast) = getNetworkBroadcast($ip_prefix, $ip_proto);
			// 	if($broadcast===null) continue;
			// 	$update_list[$ip_proto][$ip_prefix] = array('network'=>$network, 'broadcast'=>$broadcast);
			// }
			// すべてのOriginASを$update_listに追加
			$as_path_list = explode(' ', $as_path);
			foreach(explode(',', str_replace(array('{','}'), '', end($as_path_list))) as $origin_as){
				$update_list[$ip_proto][$ip_prefix][$origin_as] = true;
			}
		}
	
		//------------ 変更検出 ------------//
		// ファイルをオープンしてタイトル行の出力
		$fp = fopen(ANALYSE_ADVERTISEMENT_UPDATE_RESULT.date('Ymd.Hi', $ts).'.csv', 'w');
		fwrite($fp, 'ip_prefix,asn,type,conflict_ip_prefix'.PHP_EOL);
		// advertisementを以下の5種類に分類する
		// 1. フルルートに重複するIPプレフィックスがなく，全く新しい経路の追加
		// 2. フルルートに全く同じIPプレフィックスが存在し，OriginASが同じである（KeepAlive？）
		// 3. フルルートに全く同じIPプレフィックスが存在し，OriginASが異なる
		// 4. フルルートに衝突する（含むor含まれる）IPプレフィックスが存在し，OriginASが同じ（ハイジャックへの防御？）
		// 5. フルルートに衝突する（含むor含まれる）IPプレフィックスが存在し，OriginASが異なる（ハイジャック？）
		// type4,5は衝突するIPプレフィックスをconflict_ip_prefixとして保存する
		foreach(['v4','v6'] as $ip_proto){
			foreach($update_list[$ip_proto] as $ip_prefix => $ip_prefix_info){
				// 
				// フルルートに全く同じIPプレフィックスが存在する（type2 or type3）
				if(isset($network_list[$ip_proto][$ip_prefix])){
					foreach(array_keys($ip_prefix_info) as $asn){
						// フルルートに同じasnがある（type2）
						if(isset($network_list[$ip_proto][$ip_prefix][$asn]))
							fwrite($fp, "$ip_prefix,$asn,2,".PHP_EOL);
						// フルルートに同じasnがない（type3）
						else
							fwrite($fp, "$ip_prefix,$asn,3,".PHP_EOL);
					}
				}// フルルートに全く同じIPプレフィックスが存在しない（type1 or type4 or type5）
				else{
					// falseならtype1，重複が見つかったらIPプレフィックスを保存（type4 or type5）
					$conflict_ip_prefix=false;
					// ネットワークアドレス，ブロードキャストアドレスを算出
					list($network, $broadcast) = getNetworkBroadcast($ip_prefix, $ip_proto);
					// RIPEのBGPDUMPはIPアドレスが昇順，同じIPアドレスはプレフィックス長で昇順に並んでいるのでこれを利用．
					// 重複するIPの場合，最初に一番広いIPプレフィックス，その後それに含まれるプレフィックスが並んでくる．
					// $network_listをforeachすることで，一番小さいIPからだんだんスライドしていき，$ip_prefixに届く（追い越す）まで繰り返す
					foreach($network_list[$ip_proto] as $fullroute_ip_prefix => $fullroute_ip_prefix_info){
						//まだ重複までたどり着いていない
						if($fullroute_ip_prefix_info['broadcast'] < $broadcast) continue;
						// 追い越した＝重複するIPプレフィックスがなかった（type1） or IPプレフィックスの重複はあったが一致するasnがなかった（type5）
						if($network < $fullroute_ip_prefix_info['network']){
							foreach(array_keys($ip_prefix_info) as $asn){
								if($conflict_ip_prefix===false)	fwrite($fp, "$ip_prefix,$asn,1,".PHP_EOL);
								else							fwrite($fp, "$ip_prefix,$asn,5,$conflict_ip_prefix".PHP_EOL);
							}
							break;
						}
						// IPプレフィックスが重複している（type4 or type5）
						$conflict_ip_prefix = $fullroute_ip_prefix;
						// OriginASが同じものが見つかればtype4
						foreach(array_keys($ip_prefix_info) as $asn){
							// OriginASが同じものが見つかった（type4）
							if(isset($fullroute_ip_prefix_info[$asn])){
								fwrite($fp, "$ip_prefix,$asn,4,$conflict_ip_prefix".PHP_EOL);
								unset($ip_prefix_info[$asn]);
							}
						}
						// ip_prefix_infoが空になった（$ip_prefixをadvertiseしたASは全部type4だった）らこれ以上は不要
						if(count($ip_prefix_info)===0) break;
					}
				}
			}
		}
		fclose($fp);
	}
}










































?>
