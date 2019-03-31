<?php
//==================== URLからファイルをダウンロードする ====================//
// url: ダウンロードするファイルのURL
// filename: 保存先ファイル名
function downloadFile($url, $filename){
	if(is_file($filename))
		showLog("既にファイルが存在しているため上書きします: $filename");
	
	// DL用ファイルポインタの取得
	if(($fp_read = fopen($url, 'r', false, stream_context_create(array('http'=>array('ignore_errors'=>true))))) === false) return false;
	// 200以外はエラー
	preg_match('/HTTP\/1\.[0|1|x] ([0-9]{3})/', $http_response_header[0], $matches);
	if($matches[1] !== '200'){
		fclose($fp_read);
		return false;
	}
	// 書き込み用ファイルポインタの取得
	if(($fp_write = fopen($filename, 'w')) === false) return false;
	
	// 8KBずつ読み込んでファイルに保存していく
	while(!feof($fp_read)){
		// 8KB読み込んでバッファに保存
		if(($buf = fread($fp_read, 8192)) === false){
			$error = true;
			break;
		}
		// バッファを書き出し
		if(fwrite($fp_write, $buf) === false){
			$error = true;
			break;
		}
	}
	fclose($fp_read);
	fclose($fp_write);
	// 成功ならtrue，失敗ならfalseを返す
	return empty($error);
}

//==================== タイムスタンプから，RIPEからのダウンロード等に必要なパラメタ作成する ====================//
// rc: ルートコレクタ
// ts: タイムスタンプ（）
function MakeFilenames($rc, $ts){
	if(!is_dir(DIR_RC[$rc])) return null;
	$Y = date('Y', $ts);
	$Y_m = date('Y.m', $ts);
	$Ymd_Hi = date('Ymd.Hi', $ts);

	$filename = array();
	// url
	if($rc==='ripe_rc00'){
		$filename['fullroute_url'] = "http://data.ris.ripe.net/rrc00/$Y_m/bview.$Ymd_Hi.gz";
		$filename['update_url']    = "http://data.ris.ripe.net/rrc00/$Y_m/updates.$Ymd_Hi.gz";
	}elseif($rc === 'routeview_oregon'){
		$filename['fullroute_url'] = "http://archive.routeviews.org/bgpdata/$Y_m/RIBS/rib.$Ymd_Hi.gz";
		$filename['update_url']    = "http://archive.routeviews.org/bgpdata/$Y_m/UPDATES/updates.$Ymd_Hi.gz";
	}
	
	//------------ BGP Route ------------//
	// FullRouteGZ
	$dirs[] = $dir = DIR_RC[$rc].BGP_FULLROUTE_GZ.$Y;
	$filename['fullroute_gz'] = "$dir/bview.$Ymd_Hi.gz";
	// FullRouteBGPScanner
	$dirs[] = $dir = DIR_RC[$rc].BGP_FULLROUTE_BGPSCANNER.$Y;
	$filename['fullroute_bgpscanner'] = "$dir/$Ymd_Hi.bgpscanner.txt";
	// FullRoutePHPData
	$dirs[] = $dir = DIR_RC[$rc].BGP_FULLROUTE_PHPDATA.$Y;
	$filename['fullroute_phpdata'] = "$dir/$Ymd_Hi.dat";
	// UpadteGZ
	$dirs[] = $dir = DIR_RC[$rc].BGP_UPDATE_GZ.$Y_m;
	$filename['update_gz'] = "$dir/updates.$Ymd_Hi.gz";
	// UpdateBGPScanner
	$dirs[] = $dir = DIR_RC[$rc].BGP_UPDATE_BGPSCANNER.$Y_m;
	$filename['update_bgpscanner'] = "$dir/$Ymd_Hi.bgpscanner.txt";

	//------------ TrackOriginChangedPrefix ------------//
	// Exact
	$dir = DIR_RC[$rc].TRACK_ORIGIN_CHANGED_PREFIX;
	$dirs[] = $dir.$Y;
	$filename['track_exact_change'] =  $dir."TrackOriginExactChangedPrefix_$Ymd_Hi.csv";
	$filename['track_exact_change2'] = $dir."$Y/TrackOriginExactChangedPrefix2_$Ymd_Hi.csv";
	$filename['track_include_change'] =  $dir."TrackOriginIncludeChangedPrefix_$Ymd_Hi.csv";
	$filename['track_include_change2'] = $dir."$Y/TrackOriginIncludeChangedPrefix2_$Ymd_Hi.csv";
	
	//------------ AnalyseBGPUpdate ------------//
	$dir = DIR_RC[$rc].ANALYSE_BGP_UPDATE;
	$dirs[] = $dir.$Y_m;
	$filename['analyse_advertisement'] = $dir."$Y_m/$Ymd_Hi.csv";
	$filename['analyse_advertisement_summary'] = $dir."summary_$Ymd_Hi.csv";

	
	// $file_analyse_advertisement = ANALYSE_ADVERTISEMENT_UPDATE_RESULT."$Y_m/$Ymd_Hi.csv";
	// if(!is_dir(ANALYSE_ADVERTISEMENT_UPDATE_RESULT.$Y_m)) mkdir(ANALYSE_ADVERTISEMENT_UPDATE_RESULT.$Y_m);

	// ディレクトリの作成
	foreach ($dirs as $dir){ if(!is_dir($dir)) mkdir($dir); }
	
	return $filename;
}
	
//==================== ディレクトリはそのまま，ファイル名の最初/最後に文字列を追加 ====================//
// AppendStrToFilename('/hoge/piyo.txt', '_foo') => '/hoge/piyo_foo.txt' 
function AppendStrToFilename($filename, $str){
	// ディレクトリの場合はなにもせず返す
	if(substr($filename, 0, -1)==='/'){
		showLog("ディレクトリ名です：$filename");
		return $filename;
	}
	// appendして返す
	$pathinfo = pathinfo($filename);
	if($pathinfo['dirname']==='/') $pathinfo['dirname'] = '';
	$pathinfo['extension'] = isset($pathinfo['extension'])? '.'.$pathinfo['extension']: '';
	return "{$pathinfo['dirname']}/{$pathinfo['filename']}$str{$pathinfo['extension']}";
}
// PrependStrToFilename('/hoge/piyo.txt', 'foo_') => '/hoge/foo_piyo.txt' 
function PrependStrToFilename($filename, $str){
	// ディレクトリの場合はなにもせず返す
	if(substr($filename, 0, -1)==='/'){
		showLog("ディレクトリ名です：$filename");
		return $filename;
	}
	// prependして返す
	$pathinfo = pathinfo($filename);
	if($pathinfo['dirname']==='/') $pathinfo['dirname'] = '';
	return "{$pathinfo['dirname']}/$str{$pathinfo['basename']}";
}
?>
