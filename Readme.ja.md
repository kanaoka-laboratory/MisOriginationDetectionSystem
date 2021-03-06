# MODS
## Requirements
- MySQL（MariaDB）
    - db_setupの中のsqlを発行することでデータベースとテーブルを作成できる
    - 接続用のユーザ・パスワードはconfig.php内にて記述
- [bgpscanner](https://gitlab.com/Isolario/bgpscanner)

## Installation
git cloneとは別に以下の手順が必要

### 設定ファイルのコピー
`config_sample.php` を `config.php` にコピー
認証情報を記述 (DB ユーザ名/パスワード, GoogleCustomAPI APIKey/検索エンジンID)

### DBのセットアップ  
MySQL（MariaDB）を用意し `db_Setup/*.sql` を順番に読み込む  
注）00_table_structure.sqlを最初に読み込む

### cronの登録  
以下のcronを登録する

    # BGPFullRoute：5分おきに実行（rc毎に記述）
    */5 * * * * php /path/to/MisOriginationDetectionSystem/MODS.php CronBGPFullRoute ripe_rc00
    */5 * * * * php /path/to/MisOriginationDetectionSystem/MODS.php CronBGPFullRoute ripe_rc01
    */5 * * * * php /path/to/MisOriginationDetectionSystem/MODS.php CronBGPFullRoute routeviews_oregon

    # BGPUpdate：2分おきに実行（rc毎に記述）
    */2 * * * * php /path/to/MisOriginationDetectionSystem/MODS.php CronBGPUpdate ripe_rc00
    */2 * * * * php /path/to/MisOriginationDetectionSystem/MODS.php CronBGPUpdate ripe_rc01
    */2 * * * * php /path/to/MisOriginationDetectionSystem/MODS.php CronBGPUpdate routeviews_oregon


    # FilterSuspiciousBGPUpdate：2分おきに実行（BGPUpdateとかぶらないようにずらす）
    1-59/2 * * * * php /path/to/MisOriginationDetectionSystem/MODS.php CronFilterSuspiciousBGPUpdate 

    # ASCountry：1時間ごとに実行（0分だと他のcronと重なるので適当に時間ずらす）
    3 */1 * * * php /path/to/MisOriginationDetectionSystem/MODS.php CronASCountry

### Google Custom Search APIの設定
API KeyとSearch Engine IDを取得して `config.php` に記載する

## Usage
    Usage: php MODS.php <subcommand> [<options>]

### subcommands & options
- GetBGPFullRoute \<RC\> \<START\> [\<END\>]  
RIPE RIRから経路情報（FULL）を取得し，BGPDUMP形式に展開する
- GetBGPUpdate \<RC\> \<START\> [\<END\>]  
RIPE RIRから経路情報（UPDATE）を取得し，BGPDUMP形式に展開する
- ExtractPHPDataFromBGPScanner \<RC\> \<START\> [\<END\>]  
BGPDUMPファイルからネットワークリストを抽出しPHPの配列としてファイルに保存する
- TrackOriginExactChangedPrefix \<RC\> \<START\> \<END\>  
OriginASの変更をExactMatchで検出し，変更のあったIPプレフィックスを指定期間追跡する
- TrackOriginExactChangedPrefix2 \<RC\> \<DATE\>  
OriginASの変更をExactMatchで検出し，1週間前からのOriginASの変遷を追跡する
- TrackOriginIncludeChangedPrefix \<RC\> \<START\> \<END\>  
OriginASの変更をIncludeMatchで検出し，変更のあったIPプレフィックスを指定期間追跡する
- TrackOriginIncludeChangedPrefix2 \<RC\> \<DATE\>  
OriginASの変更をIncludeMatchで検出し，1週間前からのOriginASの変遷を追跡する
- AnalyseKindAndChangeNum \<FILENAME\>  
OriginASに変更のあったIPプレフィックスの追跡結果を，データの種類数と変更回数で統計する
- TrackAndAnalyseKindAndChangeNum \<RC\> \<START|DATE\> [\<END\>]  
TrackOriginChangedPrefixを両方実行後，AnalyseKindAndChangeNumを実行する
- AnalyseBGPUpdate \<RC\> \<START\> [\<END\>]  
5分おきのアップデートのAdvertisementを，直前のフルルートのダンプと比較し変更の検出をする
- AnalyseBGPUpdateSummary \<RC\> \<START\> [\<END\>]  
AnalyseAdvertisementの結果から，各時刻毎の各typeの数を集計する（作図用）
- FilterSuspiciousBGPUpdate [\<RC\>]  
AnalyseAdvertisementの結果のハイジャックの可能性があるものをホワイトリストを用いて分類する
- CronBGPFullRoute \<RC\>  
Cron実行用（8時間おきのフルルートを取得して変更検出）
- CronBGPUpdate \<RC\>  
Cron実行用（5分おきのフルルートを取得し，直前のフルルートとの衝突検出）
- CronFilterSuspiciousBGPUpdate [\<RC\>]  
Cron実行用（ハイジャックの可能性があるASペアをホワイトリストを用いて分類）
- CronASCountry  
Cron実行用（ASと国の紐付け）
- ImportSubmarineCableList \<CABLE LIST\>  
SubmarineCableMapより取得したCSVから海底ケーブルで接続された国を探し，DBに登録する
- GetWhoisInfoFromAsn \<ASN\>  
AS番号のwhois情報を取得してDBに保存する
- ReApplyWhitelist [\<SUSPICIOUS_ID\>]  
SuspiciousAsnSetに対してホワイトリストを再適用する
- CalcCountryDistance  
全ての2国間の隣接（陸地/海底ケーブル）ホップ数を求める
- SummaryCountryDistance  
各ConflictTypeごとにホップ数ごとの分布を求める
- help  
このドキュメントを表示

## Directory Structures
|- MODS.php：基本プログラムファイル  
|  
|- config.php：MODSの設定ファイル  
|  
|- import/：必ず使うであろう自作の関数群  
|  
|- subcommand/：サブコマンド関連の関数群  
|  
|- data/：データディレクトリ  
| |- RIPE/：RIPEから収集したデータ  
| |- TrackOriginChangedPrefix：OriginASに変更があったIPプレフィックスの追跡実験  
|  
|- script/：単独で実行可能なスクリプト  
|  
|- web/：Webページ用のスクリプト等  
|  
|- log/：MODSの実行ログ  
| |- （サブコマンド毎のログディレクトリ）  
|  
|- Readme.txt：このファイル

### subcommandの追加
- subcommandディレクトリに，subcommandと同名ファイルを作成
- subcommandと同名functionに処理を記述
- logディレクトリにsubcommandと同名のディレクトリを作成
- MODS.phpを編集（MODSオプション一覧への簡易説明，オプション毎の詳細説明，引数チェック）

## Notes
### $network_listの内部構造
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
