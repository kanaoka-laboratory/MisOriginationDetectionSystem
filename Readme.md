# MODS
## Requirements
- MySQL（MariaDB）
    - you can create databases and tables by executing sql which is in `db_setup`
    - credentials are written in `config.php`
- [bgpscanner](https://gitlab.com/Isolario/bgpscanner)

## Installation
You need more 2 steps after `git clone`

### DB Setup
Install MySQL (or MariaDB), then execute `db_Setup/*.sql`.
note) Execute `00_table_structure.sql` first.

### cron Setup  
set up these cron: 

    # BGPFullRoute: execute in every 5 minutes（every RC）
    */5 * * * * php /path/to/MisOriginationDetectionSystem/MODS.php CronBGPFullRoute ripe_rc00
    */5 * * * * php /path/to/MisOriginationDetectionSystem/MODS.php CronBGPFullRoute ripe_rc01
    */5 * * * * php /path/to/MisOriginationDetectionSystem/MODS.php CronBGPFullRoute routeviews_oregon

    # BGPUpdate: execute in every 2 minutes（every RC）
    */2 * * * * php /path/to/MisOriginationDetectionSystem/MODS.php CronBGPUpdate ripe_rc00
    */2 * * * * php /path/to/MisOriginationDetectionSystem/MODS.php CronBGPUpdate ripe_rc01
    */2 * * * * php /path/to/MisOriginationDetectionSystem/MODS.php CronBGPUpdate routeviews_oregon


    # FilterSuspiciousBGPUpdate: execute in every 2 minutes
    1-59/2 * * * * php /path/to/MisOriginationDetectionSystem/MODS.php CronFilterSuspiciousBGPUpdate 

    # ASCountry: execute in every an hour
    3 */1 * * * php /path/to/MisOriginationDetectionSystem/MODS.php CronASCountry

### Google Custom Search APIの設定
Get API Key and Search Engine ID, and write those into `config.php`.

## Usage
    Usage: php MODS.php <subcommand> [<options>]

### subcommands & options
- GetBGPFullRoute \<RC\> \<START\> [\<END\>]  
Obtain route information (FULL) from RIPE RIR and expand to BGPDUMP format
- GetBGPUpdate \<RC\> \<START\> [\<END\>]  
Obtain route information (UPDATE) from RIPE RIR and expand to BGPDUMP format
- ExtractPHPDataFromBGPScanner \<RC\> \<START\> [\<END\>]  
Extract network_list from BGPDUMP file and save it as PHP array in file
- TrackOriginExactChangedPrefix \<RC\> \<START\> \<END\>  
Detect changes in OriginAS with ExactMatch and track the changed IP prefix for a specified period
- TrackOriginExactChangedPrefix2 \<RC\> \<DATE\>  
Detect changes in OriginAS with ExactMatch, and track changes in OriginAS from one week ago
- TrackOriginIncludeChangedPrefix \<RC\> \<START\> \<END\>  
Detect OriginAS changes with IncludeMatch and track the changed IP prefix for a specified period
- TrackOriginIncludeChangedPrefix2 \<RC\> \<DATE\>  
Detect changes in OriginAS with IncludeMatch, and track changes in OriginAS from one week ago
- AnalyseKindAndChangeNum \<FILENAME\>  
Statistic results of IP prefixes that have changed in OriginAS by the number of data types and the number of changes
- TrackAndAnalyseKindAndChangeNum \<RC\> \<START|DATE\> [\<END\>]  
After executing both TrackOriginChangedPrefix, execute AnalyseKindAndChangeNum
- AnalyseBGPUpdate \<RC\> \<START\> [\<END\>]  
Detect changes by comparing advertisements in updates every 5 minutes with previous full route dumps
- AnalyseBGPUpdateSummary \<RC\> \<START\> [\<END\>]  
From the result of AnalyzeAdvertisement, count the number of each type at each time (for plotting)
- FilterSuspiciousBGPUpdate [\<RC\>]  
Classify AnalyzeAdvertisement results that may be hijacked using a whitelist
- CronBGPFullRoute \<RC\>  
For Cron execution (changes are detected by obtaining a full route every 8 hours)
- CronBGPUpdate \<RC\>  
For Cron execution (obtains update every 5 minutes and detects collision with previous full route)
- CronFilterSuspiciousBGPUpdate [\<RC\>]  
For Cron execution (classify AS pairs that may be hijacked using whitelist)
- CronASCountry  
For Cron execution (Association of AS and country)
- ImportSubmarineCableList \<CABLE LIST\>  
Search for countries connected by submarine cables from CSV obtained from SubmarineCableMap and register them in DB
- GetWhoisInfoFromAsn \<ASN\>  
Get whois information of AS number and save it in DB
- ReApplyWhitelist [\<SUSPICIOUS_ID\>]  
Reapply whitelist to SuspiciousAsnSet
- CalcCountryDistance  
Find the number of adjacent (land / submarine cable) hops between all two areas
- SummaryCountryDistance  
Calculate distribution for each hop number for each ConflictType
- help  
View this document

## Directory Structures
|- MODS.php：main file  
|  
|- config.php：config for MODS  
|  
|- import/：functions called by almost all subcommands  
|  
|- subcommand/：functions for subcommands  
|  
|- data/：data directory  
|  
|- script/：scripts independent of MODS  
|  
|- web/：scripts for web  
|  
|- log/：log directory    
|  
|- Readme.txt：this file

### append subcommand
- Create a file with the same name as subcommand in the subcommand directory
- Describe processing in function with the same name as subcommand
- Create a directory with the same name as subcommand in the log directory
- Edit MODS.php (simple explanation to MODS option list, detailed explanation for each option, argument check)

## Notes
### $network_list internal structure
    {
        "v4": {
            "192.168.1.0/24": {
                'network': 100000000,	// min IP（int）
                'broadcast': 200000000,	// max IP（int）
                100: true,			// ASN（save as index of array）
                200: true,			// ASN（save as index of array）
                300: true,			// ASN（save as index of array）
            },
            "172.16.32.0/22": [
                'network': 100000000,	// min IP（int）
                'broadcast': 200000000,	// max IP（int）
                2886737920: true,		// ASN（save as index of array）
                2886738175: true,		// ASN（save as index of array）
            ],
        },
    }
