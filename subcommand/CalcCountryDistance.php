<?php
function CalcCountryDistance(){
	global $mysqli;
	global $whitelist;

	// DB初期化
	$mysqli->query('truncate CountryDistance');

	// 接続ノード一覧
	$whitelist = array();

	// 接続ノードの一覧を取得
	$result = $mysqli->query('select * from ConflictCountryWhiteList');
	while($row = $result->fetch_assoc()){
		$whitelist[$row['cc']][$row['conflict_cc']] = 1;
		$whitelist[$row['conflict_cc']][$row['cc']] = 1;
	}
	$result->close();

	// 最短距離一覧
	$distance = array();
	// 各国ごとに全経路を探索する
	foreach(array_keys($whitelist) as $cc1){
		showLog("処理中：$cc1");
		$values = array();
		foreach(Dijkstra($whitelist, $cc1) as $cc2 => $data){
			// 存在する経路の最小ホップ数（コスト）を保存
			if($data['cost']>0){
				$distance[$cc1][$cc2] = $data['cost'];
				// showLog("$cc1 to $cc2: ".$data['cost']);
				$values[] = "('$cc1','$cc2',{$data['cost']})";
			}
		}
		if(count($values)>0)
			$mysqli->query('insert into CountryDistance (cc1,cc2,distance) values '.implode(',', $values));
	}
}

// 配列を返す．$result[$cc] = $cost
function Dijkstra($whitelist, $base_cc){
	// 国コードがホワイトリストに存在しない（隣接する国がない）
	if(!isset($whitelist[$base_cc])) return array();

	// コスト初期化（-1）
	$cc_list = array();
	foreach (array_keys($whitelist) as $cc) {
		$cc_list[$cc] = array('decided'=>false, 'cost'=>-1, 'cc_from'=>null);
	}

	// djikstraで全てのノードのコスト（ホップ数）を計算
	// 初期化
	$decided_cc = $base_cc;
	$cc_list[$decided_cc]['cost'] = 0;
	// 経路探索
	while($decided_cc!==null){
		// 確定ノードに確定フラグを立てる
		$cc_list[$decided_cc]['decided'] = true;

		// 確定ノード周辺のコストを計算
		$cost = $cc_list[$decided_cc]['cost']+1;
		foreach(array_keys($whitelist[$decided_cc]) as $cc){
			if($cc_list[$cc]['cost']<0 || $cost<$cc_list[$cc]['cost']){
				$cc_list[$cc]['cost'] = $cost;
				$cc_list[$cc]['cc_from'] = $decided_cc;
			}
		}
		// コストが最小の未確定ノードを検索
		$decided_cc = null;
		foreach ($cc_list as $cc => $cc_data) {
			// 確定済みor未接続ノードはスキップ
			if($cc_data['decided'] || $cc_data['cost']<0) continue;
			// 確定ノードの更新
			if($decided_cc===null || $cc_data['cost'] < $cc_list[$decided_cc]['cost']){
				$decided_cc = $cc;
			}
		}
	}

	// 経路情報を返す
	return $cc_list;
}

?>
