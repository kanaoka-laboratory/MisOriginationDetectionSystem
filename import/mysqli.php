<?php
//==================== mymysqli class ====================//
class mymysqli extends mysqli{
	function __construct(){
		// // MySQLの資格情報を取得
		// $filename='mysql_credential.ini';
		// if(!is_readable($filename)){
		// 	exit('MySQLの資格情報を読み込めません'.PHP_EOL);
		// }
		// list($user, $pass) = explode("\n", file_get_contents($filename));
		
		//MySQLへ接続
		parent::__construct(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DB);
		if($this->connect_error) exit('MySQLの接続に失敗しました：'.$this->connect_error.PHP_EOL);

		//文字コード指定
		$this->set_charset('utf8mb4');
	}

	function query($query, $resultmode = NULL){
		$result = parent::query($query, $resultmode);
		if($this->errno>0){
			showLog($this->errno.": ".$this->error." (".$query.")");
		}
		return $result;
	}

	//------------ 特定の日付のASと国の紐付けデータを取得 ------------//
	function GetASCountry($ts_or_date = "now"){
		// dateを取得
		if(is_int($ts_or_date))
			$date = (new DateTime(null, new DateTimeZone("UTC")))->setTimestamp($ts_or_date)->format("Y-m-d");
		else
			$date = date('Y-m-d', strtotime($ts_or_date));
		
		// データを格納する配列
		$ASCountry = array();
		// 各RIRごとに取得
		$result = $this->query("select name,value2 from CronProgress where cron='ASCountry'");
		while($row = $result->fetch_assoc()){
			$rir = $row['name'];
			// 信憑性：$dateの日がすでに取得されているか，それともされていないからひとまず最新データを返すのか
			$credible = $date<=$row['value2'];
			// 厳密なデータの参照 or ひとまず最新データを取得
			if($credible)	$query = "select asn,country,date_since,date_until from ASCountry where rir='$rir' and date_until>='$date' and date_since<='$date'";
			else 			$query = "select asn,country,date_since,date_until from ASCountry where rir='$rir' and date_until='{$row["value2"]}'";
			// データを配列に格納していく
			$result2 = $this->query($query);
			while($row = $result2->fetch_assoc()){
				$ASCountry[$row['asn']] = array(ASCOUNTRY_COUNTRY=>$row['country'], ASCOUNTRY_CREDIBLE=>$credible,
												ASCOUNTRY_DATE_SINCE=>$row['date_since'], ASCOUNTRY_DATE_UNTIL=>$row['date_until']);
			}
			$result2->close();
		}
		$result->close();

		return $ASCountry;
	}

	//------------ 国単位でのホワイトリストでの検証 ------------//
	function VerifyConflictCountryWhiteList($cc, $conflict_cc){
		$result =  $this->query("select conflict_type from ConflictCountryWhiteList where cc='$cc' and conflict_cc='$conflict_cc'");
		if($result->num_rows===0) return null;
		else return $result->fetch_assoc()['conflict_type'];
	}

	//------------ 国単位でのホワイトリストでの検証 ------------//
	function VerifyConflictAsnWhiteList($asn, $conflict_asn){
		$result =  $this->query("select conflict_type from ConflictAsnWhiteList where asn=$asn and conflict_asn='$conflict_asn' and disabled is null");
		if($result->num_rows>0) return $result->fetch_assoc()['conflict_type'];
		$result =  $this->query("select conflict_type from ConflictAsnWhiteList where asn=$asn and conflict_asn='0' and disabled is null");
		if($result->num_rows>0) return $result->fetch_assoc()['conflict_type'];
		$result =  $this->query("select conflict_type from ConflictAsnWhiteList where asn=0 and conflict_asn='$conflict_asn' and disabled is null");
		if($result->num_rows>0) return $result->fetch_assoc()['conflict_type'];
		// DDoS軽減の逆を確認
		$result = $this->query("select * from SuspiciousAsnSet where asn='$conflict_asn' and conflict_asn='$asn' and conflict_type=".CONFLICT_TYPE_WHITELIST_DDOS_MITIGATION);
		if($result->num_rows>0) return CONFLICT_TYPE_WHITELIST_DDOS_MITIGATION_CLIENT;
		// typoを確認
		if(isTypo($asn, $conflict_asn)) return CONFLICT_TYPE_BLACKLIST_TYPO;
		return null;
	}
}

?>
