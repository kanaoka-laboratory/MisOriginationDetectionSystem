<?php
// 
function ImportSubmarineCableList($filename){
	if(!is_file($filename)) showLog("ファイルが存在しません：$filename", true);
	if(!preg_match("/fusion-cables-(\d{8})\d{4}\.csv/", basename($filename), $m)) showLog("ファイル名が不正です：$filename", true);
	$Ymd = $m[1];
	global $mysqli;

	// 国コード変換テーブルを取得
	$country_cc = array();
	$result = $mysqli->query("select cc,country_name from CountryInfo");
	while($row = $result->fetch_assoc()) $country_cc[$row["country_name"]] = $row["cc"];
	$result->close();

	// ホワイトリスト
	$whitelist = array();

	// ファイルオープン
	$fp = fopen($filename, "r");
	// タイトル行スキップ
	fgets($fp);

	// 1行ずつCSVとして読み込む．
	while(($row = fgetcsv($fp)) !== false){
		// jsonとして読み込み
		$json = json_decode($row[2], true);
		// 国リストのリセット
		$country = array();
		
		// すべての接続地点を確認
		foreach ($json as $landing_point) {
			// カンマが複数ある
			$landing_point = explode(",", strtoupper($landing_point["name"]));
			$country_name = array_pop($landing_point);

			// 最終要素が国名でない場合への対処（ex: "Korea, Rep."）
			if(strpos($country_name, " REP.")!==false)
				$country_name = array_pop($landing_point).",".$country_name;
			// 中国/香港/マカオの表記ゆれ
			elseif($country_name===" China" && array_pop($landing_point)===" Hong Kong")
				$country_name = "Hong Kong";
			
			// 空白の除去
			$country_name = ltrim($country_name);
			
			// 国コードの存在確認：DBに登録がない場合は標準入力から取得
			if(!isset($country_cc[$country_name])){
				$cc = "";
				while(!in_array($cc, $country_cc, true)){
					echo "$country_name -> ";
					$cc = rtrim(fgets(STDIN));
				}
				$country_cc[$country_name] = $cc;
			}

			// 国リストに追加
			$country[] = $country_name;	
		}

		// 接続地点が国内のケーブルの場合はスキップ
		$country = array_unique($country);
		sort($country);
		$country_count = count($country);
		if($country_count===1) continue;
		
		// ホワイトリストに追加
		for($i=0; $i<$country_count; $i++){
			for($j=$i+1; $j<$country_count; $j++){
				$whitelist[$country[$i]][$country[$j]] = true;
			}
		}
	}

	// 出力ファイルオープン
	$fp = fopen(SUBMARINE_CABLE_LIST."CountryListConnectedBySubmarineCable_$Ymd.csv", 'w');
	fwrite($fp, "country_name1,cc1,country_name2,cc2".PHP_EOL);
	// DBの海底ケーブル接続国情報リセット
	$conflict_type = CONFLICT_TYPE_CONNECTED_BY_SUBMARINE_CABLE;
	$mysqli->query("delete from ConflictCountryWhiteList where conflict_type=$conflict_type");
	// ホワイトリストに追加
	foreach($whitelist as $country1 => $country2_info){
		foreach ($country2_info as $country2 => $null){
			$cc1 = $country_cc[$country1];
			$cc2 = $country_cc[$country2];
			fwrite($fp, "\"$country1\",$cc1,\"$country2\",$cc2".PHP_EOL);
			$mysqli->query("insert into ConflictCountryWhiteList (conflict_type,cc,conflict_cc) ".
							"values ($conflict_type,'$cc1','$cc2'),($conflict_type,'$cc2','$cc1');");
		}
	}
	fclose($fp);
}

?>
