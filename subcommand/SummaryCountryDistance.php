<?php
function SummaryCountryDistance(){
	global $mysqli;

	//==================== 国の距離一覧を取得 ====================//
	$country_distance = array();
	$result = $mysqli->query("select * from CountryDistance");
	while($row = $result->fetch_assoc()){
		$country_distance[$row["cc1"]][$row["cc2"]] = (int)$row["distance"];
	}
	$result->close();
	$distance_max = (int)$mysqli->query("select max(distance) from CountryDistance")->fetch_assoc()['max(distance)'];

	//==================== 出力先を作成 ====================//
	$outdir = SUMMARY_COUNTRY_DISTANCE.date("Ymd_His");
	mkdir($outdir);

	//==================== データテンプレートを作成 ====================//
	$template = array();
	$consts = get_defined_constants(TRUE)["user"];
	foreach($consts as $const=>$conflict_type){
		if(preg_match("/^CONFLICT_TYPE_/",$const)){
			$template[$conflict_type][100] = 0;
			$template[$conflict_type][90] = 0;
			for($i=0; $i<=$distance_max; $i++){
				$template[$conflict_type][$i] = 0;
			}
		}
	}

	//==================== メイン処理 ====================//
	// ルートコレクタごとに処理
	$data_total = $template;
	foreach(array_keys(DIR_RC) as $rc){
		// データの取得
		$data = $template;
		$result = $mysqli->query("select conflict_type,asn_cc,conflict_asn_cc,count from PrefixConflictedUpdate as t1 ".
								"left join SuspiciousAsnSet as t2 on t1.suspicious_id=t2.suspicious_id where rc='$rc'");
		while($row = $result->fetch_assoc()){
			// 国の距離を決定（conflict_asn_ccが複数ある場合最も短い距離）
			$distance = 100;	// 非常に大きな値で初期化

			foreach(explode("/", $row["conflict_asn_cc"]) as $asn_cc2){
				$asn_cc = $row["asn_cc"];
				// 距離決定ができない
				if($asn_cc==='-P' || $asn_cc==='-X' || $asn_cc2==='-P' || $asn_cc2==='-X'){
					continue;
				}elseif($asn_cc==='EU' || $asn_cc2==='EU'){
					if(90 < $distance) $distance=90;
				}elseif($asn_cc===$asn_cc2){
					$distance=0;
					break;
				}elseif(isset($country_distance[$asn_cc][$asn_cc2]) && $country_distance[$asn_cc][$asn_cc2] < $distance){
					$distance = $country_distance[$asn_cc][$asn_cc2];
				}
			}
			// if(!($asn_cc==='-P' || $asn_cc==='-X' || $asn_cc2==='-P' || $asn_cc2==='-X') && $distance===100 )
			// 	echo $row["asn_cc"]."-".$row["conflict_asn_cc"].PHP_EOL;
			// dataに追加
			$data[$row["conflict_type"]][$distance] += $row["count"];
		}
		$result->close();

		// 出力先ファイルオープン
		$fp = fopen("$outdir/$rc.csv", "w");
		// タイトル行
		for($i=0;$i<=$distance_max;$i++) fwrite($fp, ",$i");
		fwrite($fp, ",EU,unknown".PHP_EOL);	
		foreach ($data as $conflict_type => $distances) {
			fwrite($fp, $conflict_type);
			for($i=0;$i<=$distance_max;$i++){
				fwrite($fp, ",".$distances[$i]);
				$data_total[$conflict_type][$i] += $distances[$i];
			}
			fwrite($fp, ",".$distances[90].",".$distances[100].PHP_EOL);
			$data_total[$conflict_type][90] += $distances[90];
			$data_total[$conflict_type][100] += $distances[100];

		}
		// 出力先ファイルクローズ
		fclose($fp);
	}
	// data_totalの出力
	$fp = fopen("$outdir/total.csv", "w");
	// タイトル行
	for($i=0;$i<=$distance_max;$i++) fwrite($fp, ",$i");
	fwrite($fp, ",EU,unknown".PHP_EOL);	
	foreach ($data_total as $conflict_type => $distances) {
		fwrite($fp, $conflict_type);
		for($i=0;$i<=$distance_max;$i++){
			fwrite($fp, ",".$distances[$i]);
		}
		fwrite($fp, ",".$distances[90].",".$distances[100].PHP_EOL);
	}
	// 出力先ファイルクローズ
	fclose($fp);
}
?>
