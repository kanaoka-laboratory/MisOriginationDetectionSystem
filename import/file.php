<?php
//==================== URLからファイルをダウンロードする ====================//
function downloadFile($url, $filename){
	// ファイルポインタの取得
	if(($fp_read = fopen($url, 'r')) === false) return false;
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

	// 成功ならtrue，失敗ならfalseを返す
	return empty($error);
}
?>
