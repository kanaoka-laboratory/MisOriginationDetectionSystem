<?php
ini_set("display_errors", "On");
//==================== 初期設定 ====================//
chdir("..");
// 設定ファイル読み込み
require_once("config.php");
// 関数などの読み込み
foreach(glob("import/*.php") as $filename) require_once($filename);
$mysqli = new mymysqli();
// URLの無視リストの取得
$ignore_url_list = array();
$result = $mysqli->query("select url from MOASCleaningIgnore");
while($row = $result->fetch_assoc()){ $ignore_url_list[] = $row['url']; }
$result->close();

//================================================//
?>

<html lang=“ja”>
<head>
	<meta charset=“UTF-8”>
	<title></title>
</head>
<body>
<div id=“container”>
<?php
// AS番号が指定されてない場合は処理しない
if(!isset($_GET["asn"])) exit("asn not set");
$asn_list = explode(',', $_GET["asn"]);
foreach($asn_list as $asn){
    // whoisを取得（whois中のURL特定は非常に困難：RIRのURLが混在するため，どれが本当のURLかわからない．URLが書いてない場合もある）
    $whois = $mysqli->query("select name,body,date_query as date from Whois where query='as$asn'")->fetch_assoc();
    echo"<div class='as_info'><h2>{$asn}（{$whois['name']}）</h2>",
        "<h3>whois（{$whois['date']}）</h3>",
        "<pre style='border:solid 1px black;height:200px;overflow:scroll;'>",
        "<code>{$whois['body']}</code></pre>",
        "</div>";
    // GoogleCustonAPIでの検索
    /* CSA_API_KEY
       CSA_SEARCH_ENGINE_ID
    */
    // 3種類のキーワードで検索
    echo"<h3>Search Result</h3>",
        "<div style='display:table;'>";
    foreach(array("{$whois['name']} DDoS mitigation", "{$whois['name']} CDN", "{$whois['name']} IP lease") as $query){
        // パラメタの作成
        $param = http_build_query(array(
                "q" => $query,
                "key" => CSA_API_KEY,
                "cx" => CSA_SEARCH_ENGINE_ID,
                "alt" => "json",
                "start" => 1,
        ));
        // クエリを投げる
        $result = json_decode(file_get_contents("https://www.googleapis.com/customsearch/v1?".$param), true);
        
        echo"<div style='padding:10px; display:table-cell;border-left: solid 1px black;border-right: solid 1px black;'>",
            "<h3>query: '$query'</h3>";
        foreach($result["items"] as $data){
            // URL無視リストに含まれていたらスキップ
            if(in_array($data['displayLink'], $ignore_url_list, true)) continue;
            // 表示
            echo"<div style='border-top:solid 1px black;border-bottom:solid 1px black;'>",
                "<p style='font-size:large;'><a href='{$data['link']}' target='_blank'>{$data['title']}</a></p>",
                "<span style='color:#006621;'>url: {$data['link']}</span><br>",
                "<input type=button class='ignore_web' value='ignore {$data['displayLink']}' data-url='{$data['displayLink']}'>",
                "<p style='color:#545454;'>{$data['htmlSnippet']}</p>",
                "</div>";
        }
        echo "</div>";
    }
    echo "</div>";
}
?>
</div>
<script src="_/jquery-3.3.1.min.js"></script>				<!-- JavaScript: jQuery -->
<script src="_/mods.js" charset="UTF-8"></script>							<!-- JavaScript: MODS標準 -->
<script src="_/search_as_info.js" charset="UTF-8"></script>	<!-- JavaScript: 専用JS -->
</body>
</html>
