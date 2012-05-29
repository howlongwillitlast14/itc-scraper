<?php

//MAXATTEMPT specifies max attempts for performing http request. 
//If http request fails (due to network failure or server reject) 
//script will try to perform it MAXATTEMPT times. 
  define("BASE_URL", "https://itunesconnect.apple.com");
  define("MAXATTEMPT", 10);


//If uncomment next line of code and specify second parameter of define function
//script will save all meta information to that specified directory
//By default meta is saved to the `meta` dir which is created in the same directory with itc_scraper.php
//  define("BASE_META_DIR", "/Users/iamgreat/workspace/apps/itc/meta/");

//specifying correct timezone to avoid php warnings
  date_default_timezone_set('Europe/Moscow');

//database credentials
  define ('DB_HOST', '127.0.0.1');
  define ('DB_USER', 'root');
  define ('DB_PASS', '');
  define ('DB_NAME', 'itc');
  define ('DB_CHARSET', 'utf8');
  define ('DB_COLLATE', '');

//iTunesConnect credentials
//"appleid" and "password" is your iTunesConnect acount login info
//"apps" array should contain a list of App names you want to watch
//there may be several iTunesAccount in logins array
  $logins = array(
    "myitunesconnectaccount"=>array(
      "appleid"=>"mydevaccount",
      "password"=>"mysuperduperpass",
      "apps"=>array("AppName1","AppName2")
    ),
  );

//One can specify some special scraping options below
  $scrape_options = array(
    "unzipFile" => True,  //whether or not unzip sales reports since they're being downloaded as gz archives
    "verbose" => True,  //If true processing information will be outputted to console
    "daysToDownload" => 0, //One can specify depth for downloading sales reports. If zero all available reports will be downloaded
    "dateToDownload" => false, //or specify custom date
    "outputFormat" =>  false, //If false then original iTunesConnect filenames will be used
    "overWriteFiles" => False, //If false and particular report has been already downloading then it skipping 
    "debug" => false //a lot of debug information will be ouputted to console if set to true
  );

//specifying location for storing cURL cookies
  $cookiefile = tempnam("/tmp", "itc_cookies");  

