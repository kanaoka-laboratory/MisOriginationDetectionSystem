<?php
class mymysqli extends mysqli{
	function __construct(){
		// // MySQLの資格情報を取得
		// $filename='mysql_credential.ini';
		// if(!is_readable($filename)){
		// 	exit('MySQLの資格情報を読み込めません'.PHP_EOL);
		// }
		// list($user, $pass) = explode("\n", file_get_contents($filename));
		
		//MySQLへ接続
		parent::__construct('localhost', 'bgp_team_read', '9K80ClGRMgn8gIG0', 'MisOriginationDetectionSystem');
		if($this->connect_error){
			exit('MySQLの接続に失敗しました：'.$this->connect_error);
		}

		//文字コード指定
		$this->set_charset('utf8mb4');
	}

	function query($query, $resultmode = NULL){
		$result = parent::query($query, $resultmode);
		if($this->errno>0){
			echo $this->errno.': '.$this->error.' ('.$query.')'.PHP_EOL;
		}
		return $result;
	}
}
?>
