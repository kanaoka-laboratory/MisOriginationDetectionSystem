<?php
// 5分おきのアップデートのAdvertisementに注目した分析
// （直前のフルルートと比較してどのような経路がAdvertisementされたか）
// その結果から各時刻毎にtype1〜5がそれぞれどれぐらいずつの量存在するのかを集計する
function AnalyseAdvertisementSummary($rc, $start, $end=null){
	if(!is_dir(DIR_RC[$rc])) showLog('不正なルートコレクタです：'.$rc, true);
	if($end===null) $end = $start;
	// 開始・終了時間を設定
	$ts = strtotime($start);
	$ts_end = strtotime($end);
	
	showLog(date('Y/m/d H:i',$ts).'〜'.date('Y/m/d H:i',$ts_end).'の各typeごとのAdvertisementの数を集計');
	$filename = AppendStrToFilename(MakeFilenames($rc,$ts)['analyse_advertisement_summary'], '_'.date('Ymd.Hi',$ts_end));
	$fp = fopen($filename, 'w');
	// タイトル行の出力
	fwrite($fp, 'date,type1,type2,type3,type4,type5'.PHP_EOL);
	for(; $ts<=$ts_end; $ts+=60*5){
		// それぞれのtypeのカウント用配列
		$count = array(1=>0, 2=>0, 3=>0, 4=>0, 5=>0);
		// ファイルオープン
		$rows = file(MakeFilenames($rc,$ts)['analyse_advertisement'], FILE_SKIP_EMPTY_LINES|FILE_IGNORE_NEW_LINES);
		// タイトル行をスキップ
		array_shift($rows);
		// 1行ずつ読み込んで$countの該当するtypeを+1
		foreach($rows as $row){
			$type = explode(',', $row, 2)[0];
			$count[$type]++;
		}
		// 結果の出力
		fwrite($fp, date('Y/m/d H:i', $ts).",{$count[1]},{$count[2]},{$count[3]},{$count[4]},{$count[5]}".PHP_EOL);
	}
	fclose($fp);
}
?>
