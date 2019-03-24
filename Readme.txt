//============== MODS 説明書 ==============//

Usage: php MODS.php <subcommand> <options>

MySQLのDBとbgpscannerコマンドが必要．
DBが不要なコマンドを動かしたいだけの場合はMODS.phpの"$mysqli = new mymysqli();"をコメントアウトすれば一応動く．
DBSetup.sqlに各テーブル情報が記載してある．
接続用のユーザ名・パスワードはconfig.phpにて記述．
bgpscannerコマンドは，Ubuntuなら以下のリンクからlibisocoreとbgpscannerのdebファイルをDLしてaptでインストール
https://isolario.it/web_content/php/site_content/tools.php
Macの場合は以下のリンクのインストールを参照
https://nstgt.hatenablog.jp/entry/2019/02/19/235555

//------------ ファイル・ディレクトリ構成 ------------//
/
|- MODS.php：基本プログラムファイル
|
|- config.php：MODSの設定ファイル
|
|- import/：必ず使うであろう自作の関数群
|
|- subcommand/：サブコマンド関連の関数群
|
|- data/：データディレクトリ
|	|- RIPE/：RIPEから収集したデータ
|	|- TrackOriginChangedPrefix：OriginASに変更があったIPプレフィックスの追跡実験
|
|- script/：単独で実行可能なスクリプト
|
|- web/：Webページ用のスクリプト等
|
|- log/：MODSの実行ログ
|	|- （サブコマンド毎のログディレクトリ）
|
|- Readme.txt：このファイル

//------------ subcommand追加後にやること ------------//
subcommandディレクトリに，subcommandと同名ファイルを作成，subcomandと同名関数がmain関数となるようにする
logディレクトリにsubcommandと同名のディレクトリを作成
MODS.phpを編集（MODSオプション一覧への簡易説明，オプション毎の詳細説明，引数チェック）


//==================== プログラムの実装メモ ====================//
//------------ $network_list の内部構造 ------------//
{
	"v4": {
		"192.168.1.0/24": {
			'network': 100000000,	// 最小IP（int）
			'broadcast': 200000000,	// 最大IP（int）
			100: true,			// AS番号（配列キーとして保存）
			200: true,			// AS番号（配列キーとして保存）
			300: true,			// AS番号（配列キーとして保存）
		},
		"172.16.32.0/22": [
			'network': 100000000,	// 最小IP（int）
			'broadcast': 200000000,	// 最大IP（int）
			2886737920: true,		// AS番号（配列キーとして保存）
			2886738175: true,		// AS番号（配列キーとして保存）
		],
	},
}


//------------ AnalyseKindAndChangeNumのタイプ ------------//
Type0: 変化無し（特記事項なし）
	ex. A A A A A A A A
	ex. A/B A/B A/B A/B 
Type1: 1回のみ変化（正常な変化と思われる）
	ex. A B B B B B B B B
	ex. A A A A . . . . .
	ex. A A A A/B A/B 	A/B
Type2: 常にある1ASが登場（MisOrigination可能性あり）
	ex. A A/B A A A/B A/B A A/B
Type3: 空白と1種類のOriginASのみ（何やってるのか怪しいAS）
	ex. A A . A . . . A A
	ex. A/B . . A/B A/B .
Type4: その他・空白なし（どうなってるのかわからない・MultipleOrigin？）
	ex. A A B B A A A B A
	ex. A A/B B B B A A/B
Type5: その他・空白あり（どうなってるのかわからない）
	ex. A . A/B B A/B A . 
	ex. A A . B . B A . A

				1	2	3	4	5	6
data_kind_num	1	2	≧2	2	≧2	≧3
change_num		0	1	≧2	≧2	≧2	≧2
has_blank		F	-	F	T	F	T

change_num 0->(0)
	≧2	   1->(1)
	|
has_blank false--------常にある1ASが存在する true->(2)
   true 						false
    |							  |
data_kind_num ≧3->(5)			 (4)
	2
	|
   (3)

//------------ AnalyseBGPUpdateのタイプ（adv_type） ------------//
1. フルルートに重複するIPプレフィックスがなく，全く新しい経路の追加
2. フルルートに全く同じIPプレフィックスが存在し，OriginASが同じである（KeepAlive？）
3. フルルートに全く同じIPプレフィックスが存在し，OriginASが異なる											// ARTEMISにおける Exact prefix hijacking
4. フルルートに衝突する（含むor含まれる）IPプレフィックスが存在し，OriginASが同じ（ハイジャックへの防御？）
5. フルルートに衝突する（含むor含まれる）IPプレフィックスが存在し，OriginASが異なる（ハイジャック？）		// ARTEMISにおける Sub-prefix hijacking

//------------ FilterSuspiciousUpdates（conf_type） ------------//
0. プライベートAS番号
1. 怪しい
2. 地理的に隣接する国
3. 海底ケーブルで接続された国
7. 同一国
10. ホワイトリスト（その他）
11. ホワイトリスト（US DoD）
12. ホワイトリスト（Akamai）
