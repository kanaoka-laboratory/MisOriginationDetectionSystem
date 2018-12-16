<?php
// 5分おきのアップデートのAdvertisementに注目した分析
// （直前のフルルートと比較してどのような経路がAdvertisementされたか）
// その結果から各時刻毎にtype1〜5がそれぞれどれぐらいずつの量存在するのかを集計する
function AnalyseAdvertisementUpdateSummary($start, $end=null){
	if($end===null) $end = $start;
	// 開始・終了時間を設定
	$ts = strtotime($start);
	$ts_end = strtotime($end);
	
	showLog(date('Y/m/d H:i',$ts).'〜'.date('Y/m/d H:i',$ts_end).'の各typeごとのAdvertisementの数を集計');
	$filename = date('Ymd.Hi',$ts).'_'.date('Ymd.Hi',$ts_end).'.csv';
	$fp_out = fopen(ANALYSE_ADVERTISEMENT_UPDATE_SUMMARY.$filename, 'w');
	// タイトル行の出力
	fwrite($fp_out, 'date,type1,type2,type3,type4,type5'.PHP_EOL);
	for(; $ts<=$ts_end; $ts+=60*5){
		// それぞれのtypeのカウント用配列
		$count = array(1=>0, 2=>0, 3=>0, 4=>0, 5=>0);
		// ファイルオープン
		$ripe = MakeRIPEUpdateParam($ts);
		$fp = fopen($ripe['analyse_advertisement'], 'r');
		// タイトル行をスキップ
		fgets($fp);
		// 1行ずつ読み込んで$countの該当するtypeを+1
		while (($row=fgets($fp))!==false) {
			// conflict_ip_prefix, conflict_asnは今回は不要
			list($ip_prefix, $asn, $type) = explode(',', rtrim($row));
			$count[$type]++;
		}
		// 結果の出力をしてファイルクローズ
		fwrite($fp_out, date('Y/m/d H:i', $ts).",{$count[1]},{$count[2]},{$count[3]},{$count[4]},{$count[5]}".PHP_EOL);
		fclose($fp);
	}
	fclose($fp_out);
	showLog('完了: '.ANALYSE_ADVERTISEMENT_UPDATE_SUMMARY.$filename);
}
?>
