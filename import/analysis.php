<?php
//==================== 変更前と変更後の経路情報を比較して変更があった経路の情報を返す ====================//
function detectUpdate($prev_network_list, $next_network_list){
	// 変更を保存する配列
	$update_list = array(	'v4'=>array('add'=>array(),'delete'=>array(),'update'=>array()),
							'v6'=>array('add'=>array(),'delete'=>array(),'update'=>array())	);
	
	// 重複する経路を$prev_network_listからunset
	foreach(array('v4','v6') as $ip_proto){
		foreach($next_network_list[$ip_proto] as $as => $as_info){
			foreach($as_info as $prefix => $prefix_info){
				// 変更がない（変更前・変更後どちらにも同じ経路が存在する）場合はスキップ
				if(isset($prev_network_list[$ip_proto][$as][$prefix])){
					unset($prev_network_list[$ip_proto][$as][$prefix]);
					continue;
				}
				// 変更（追加された経路）を$update_list['add']に追加
				$update_list[$ip_proto]['add'][] = array($as, $prefix, $prefix_info[NETWORK_LIST_IP_MIN], $prefix_info[NETWORK_LIST_IP_MAX]);
				// 変更のあったASをキーとして$update_list['update']に追加
				$update_list[$ip_proto]['update'][$as] = true;
			}
		}
		// $prev_network_listに残った経路を検出
		foreach($prev_network_list[$ip_proto] as $as => $as_info){
			foreach($as_info as $prefix => $prefix_info){
				// 残っている要素（経路）$update_list['delete']に追加
				$update_list[$ip_proto]['delete'][] = array($as, $prefix);
				// 変更のあったASをキーとして$update_list['update']に追加
				$update_list[$ip_proto]['update'][$as] = true;
			}
		}
	}

	return $update_list;
}

//==================== その日のnetwork_listを分析してプレフィックスが衝突するASを検索 ====================//
// network_list: ネットワーク情報が入った配列
// 個人メモ：PHPはCopy on Writeがあるから，Read-Onlyの場合は巨大な配列でも参照渡しの必要がない
function detectConflict($network_list){
	// 経路が衝突したASの組み合わせを保存する配列
	$conflict_list = array('v4'=>array(), 'v6'=>array());
	
	//------------ v4 ------------//
	// network_listのASを総当りで比較（同じAS同士はスキップ）
	$length = count($network_list['v4']);
	for($i=0; $i<$length-1; $i++){
		// showLog('衝突検知中：'.($i+1).'/'.$length);
		$as1 = key(array_slice($network_list['v4'], $i, 1, true));
		$as_info1 = $network_list['v4'][$as1];
		$network_list2 = array_slice($network_list['v4'], $i+1, null, true);
		foreach($network_list2 as $as2 => $as_info2){
			// 2つのASがそれぞれ広告しているIPプレフィックス同士を比較
			foreach($as_info1 as $prefix1 => $prefix_info1){
				foreach($as_info2 as $prefix2 => $prefix_info2){
					// 衝突するプレフィックスがあれば出力
					if($prefix_info2[NETWORK_LIST_IP_MIN] <= $prefix_info1[NETWORK_LIST_IP_MAX]
							&& $prefix_info1[NETWORK_LIST_IP_MIN] <= $prefix_info2[NETWORK_LIST_IP_MAX]){
						// echo "AS$as1:$prefix1<=>AS$as2:$prefix2\n";
						$conflict_list['v4'][] = $as1.','.$as2;
						continue 3;
					}
				}
			}
		}
	}

	//------------ v6 ------------//
	$length = count($network_list['v6']);
	for($i=0; $i<$length-1; $i++){
		// showLog('衝突検知中：'.$i.'/'.$length);
		$as1 = key(array_slice($network_list['v6'], $i, 1, true));
		$as_info1 = $network_list['v6'][$as1];
		$network_list2 = array_slice($network_list['v6'], $i+1, null, true);
		foreach($network_list2 as $as2 => $as_info2){
			// 2つのASがそれぞれ広告しているIPプレフィックス同士を比較
			foreach($as_info1 as $prefix1 => $prefix_info1){
				foreach($as_info2 as $prefix2 => $prefix_info2){
					// 衝突するプレフィックスがあれば出力
					if(strcmp($prefix_info2[NETWORK_LIST_IP_MIN], $prefix_info1[NETWORK_LIST_IP_MAX]) <= 0 
							&& strcmp($prefix_info1[NETWORK_LIST_IP_MIN], $prefix_info2[NETWORK_LIST_IP_MAX]) <= 0){
						// echo "AS$as1:$prefix1<=>AS$as2:$prefix2\n";
						$conflict_list['v6'][] = $as1.','.$as2;
						continue 3;
					}
				}
			}
		}
	}

	return $conflict_list;
}

?>
