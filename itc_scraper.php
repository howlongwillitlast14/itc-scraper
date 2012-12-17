<?php

// --------------------------------------------------------------------------------
// PHP iTunesConnect scraper
// --------------------------------------------------------------------------------
// License MIT - heixmal - May 2012
// http://heximal.net
// --------------------------------------------------------------------------------
//
// Presentation :
//   PHP ITC scraper is the tool for populating 
//   data from iTunesConnect.apple.com portal regarding apple developer
//   applications such as app state and sales reports
//
// Warning :
//   This software and the associated files are non commercial, non professional
//   work.
//   It should not have unexpected results. However if any damage is caused by
//   this software the author can not be responsible.
//   The use of this software is at the risk of the user.
 
  error_reporting(E_ALL);

  include("sales.php");
  include("db_tools.php");
  include("config.php");
  include('pclzip.lib.php');
  include("http_utils.php");

  function error($msg) {
    global $scrape_options;
    if ($scrape_options["debug"])
      echo("error: $msg\n");
    return false;
  }

  function warn($msg) {
    global $scrape_options;
    if ($scrape_options["debug"])
      echo("warning: $msg\n");
    return true;
  }



  function parse_apple_date($dt) {
    $pat = '/([a-zA-Z]*) ([0-9]*), ([0-9]*)/si';
    preg_match($pat, $dt, $matches);
    if (count($matches)==0) return 'n/a';
    $mon = array(""=>0,"Jan"=>1,"Feb"=>2,"Mar"=>3,"Apr"=>4,"May"=>5,"Jun"=>6,"Jul"=>7,"Aug"=>8,"Sep"=>9,"Oct"=>10,"Nov"=>11,"Dec"=>12);
    return mktime(0,0,0,$mon[$matches[1]],$matches[2],$matches[3]);
  }


  function process_reports($filenames) {
    global $scrape_options;
    if ($scrape_options["debug"])
      echo("processign reports (".count($filenames).")\n");
    $res = array();
    foreach($filenames as $f) {
      $cont = file_get_contents($f);
      if (!$f) {
        warn("some problems with getting saved report $f\n");
        continue;
      }
      $cont = explode("\n", $cont);
      $columns = explode("	", $cont[0]);
      for($i=0,$c=count($columns);$i<$c;$i++) 
        $columns[$i] = ":".trim(str_replace(" ","",$columns[$i]));
      for ($i=1,$c=count($cont);$i<$c;$i++)
        if (trim($cont[$i])!='')
          $res[] = process_report_to_db($columns, explode("	", $cont[$i]));
    }
    return array_unique($res);
  }


//function that parses application page (Manage Your Applications -> MyApp)
//and populate about current versions (version number, status, dates created and released)
//There can be one or two versions: Current and New
//Later this information will be stored in database and meta dir

  function parse_version($cont) {
//parsing app icon
    if (strpos($cont, "btn-blue-new-version.png")!==false) {
      echo("add_new_version marker found\n");
      return true;
    }
    $pat='/<div class="app-icon">[\s]*<a href="[^>]*">[\s]*<div style="position: relative; padding: 0">[\s]*<img border="0" width="121" height="121" src="(.*?)" \/>/si';
    preg_match($pat, $cont, $matches);
    if (count($matches)==0)
      return error("app icon pattern not found");
    $icon = $matches[1];
    $pat='/<p><label>Version<\/label>(.*?)<\/p>/si';
    preg_match($pat, $cont, $matches);    
    if (count($matches)==0)
      return error("Version pattern not found");
    $version = trim(strip_tags($matches[1]));
    global $scrape_options;
    if ($scrape_options["debug"])
      echo("version: $version\n");

    $pat='/<label>Status<\/label>[\s]*<span class="metadataFieldReadonly">[\s]*<span[^>]*>[\s]*<img class="status-icon" src="\/itc\/images\/status-([a-z]*).png" \/>(.*?)<\/span>/si';
    preg_match($pat, $cont, $matches);    
    if (count($matches)==0)
      return error("Status pattern not found");
    $status = trim(strip_tags($matches[2]));
    $status_color = trim(strip_tags($matches[1]));
    if ($scrape_options["debug"])
      echo("status: $status ($status_color)\n");

    $pat='/<p><label>Date Created<\/label>(.*?)<\/p>/si';
    preg_match($pat, $cont, $matches);    
    if (count($matches)==0)
      return error("Date created pattern not found");
    $date_created = trim(strip_tags($matches[1]));
    if ($scrape_options["debug"])
      echo("date created: $date_created\n");

    $pat='/<p><label>Date Released<\/label>(.*?)<\/p>/si';
    preg_match($pat, $cont, $matches);    
    if (count($matches)==0) {
      warn("Date released pattern not found");
      $date_released = false;
    } else
      $date_released = trim(strip_tags($matches[1]));
    if ($scrape_options["debug"])
      echo("date releases: $date_released\n");
    return array(
      "version"=>$version,
      "icon"=>$icon, 
      "status"=>$status,
      "status_color"=>$status_color, 
      "date_created"=>parse_apple_date($date_created), 
      "date_released"=>parse_apple_date($date_released)
    );
  }


//function that performs downloading application icons
  function get_icons($appid, $ver1, $ver2) {
    if ($ver1 && isset($ver1["icon"]))
    $res = getUrlContent2($ver1["icon"]);
    global $scrape_options;
    if (!$res)
      if ($scrape_options["debug"])
        echo("problems with getting app current icon\n");
      else ;
    else
      file_put_contents(BASE_META_DIR."app_$appid/curr_icon.png", $res);

    if ($ver2 && isset($ver2["icon"]))
    $res = getUrlContent2($ver2["icon"]);
    if (!$res)
      if ($scrape_options["debug"])
        echo("problems with getting app new icon\n");
      else ;
    else
      file_put_contents(BASE_META_DIR."app_$appid/new_icon.png", $res);
    
    return true;
  }

//function perfroms collecting all available data about application:
//Attributes like SKU, BundleID, AppleID, Type, Default Language
//Information about app versions etc

  function process_app($app_name, $app_link) {
    global $scrape_options;
    if ($scrape_options["verbose"])
      echo("processing app $app_name\ngetting app page $app_link\n");
//getting and parsing app page
//there may be several versions of app
//we store all versions meta (created date, publis date and current status)
    $res = getUrlContent2($app_link);
    if (!$res)
      return error("failed to get app page\s");
//parsing primary meta

    $pat='/<p><label>SKU<\/label>(.*?)<\/p>[\s]*<p><label>Bundle ID<\/label>(.*?)<\/p>[\s]*<p><label>Apple ID<\/label>(.*?)<\/p>[\s]*<p><label>Type<\/label>(.*?)<\/p>[\s]*<p><label style="white-space: nowrap">Default Language<\/label>(.*?)<\/p>/si';
    $match=preg_match($pat, $res, $matches);
    if (!$match||!is_array($matches)||count($matches)==0)
      return error("Failed to parse app page: meta markers not found");
    $sku=trim(strip_tags($matches[1]));
    $bundle_id = trim(strip_tags($matches[2]));
    $apple_id = trim(strip_tags($matches[3]));
    $app_type = trim(strip_tags($matches[4]));
    $lang = trim(strip_tags($matches[5]));
    if ($scrape_options["verbose"])
      echo("parsed memo:\nsku: $sku\nbundle_id: $bundle_id\napple_id: $apple_id\napp_type: $app_type\ndefault language: $lang\n");

    $pat='/<td class="value"><a target="_blank" href="(.*?)">View in App Store[\s]*<\/a><\/td>/si';
    $match=preg_match($pat, $res, $matches);
    if (!$match||!is_array($matches)||count($matches)==0)
      return error("Failed to parse app page: Appstore link not found");
    $applink = $matches[1];
    if ($scrape_options["verbose"])
      echo("appstore link: $applink\n");

    $pat='/id([0-9]*)\?/si';
    $match=preg_match($pat, $applink, $matches);
    if (!$match||!is_array($matches)||count($matches)==0)
      return error("Failed to parse app page: Appstore ID not found");
    $appid = $matches[1];
    echo("appid: $appid\n");
    
    $p = strpos($res, "version-container");
    if ($p===false) 
      return error("version-container marker not found");
    $p2 = strpos($res, "version-container", $p+10);
    if ($p2===false) {
      if ($scrape_options["debug"])
        echo("second version-container marker not found. parsing only one\n");
      $str1 = substr($res, $p, strlen($res) - $p);
      $str2 = false;
    } else {
      if ($scrape_options["debug"])
        echo("both version-container markers found\n");
      $str1 = substr($res, $p, $p2 - $p);
      $str2 = substr($res, $p2, strlen($res) - $p2);
   }
   
    $ver1 = parse_version($str1);
    if (!$ver1)
      return error("parse Current version failed");
    if ($str2) {
      $ver2= parse_version($str2);
//      if (!$ver2)
//        return error("parse New version failed");
    } else 
      $ver2 = false;
    if (!is_dir(BASE_META_DIR."/app_$appid")&&!mkdir(BASE_META_DIR."/app_$appid"))
      return error("couldn't access to application directory [".BASE_META_DIR."/app_$appid]");
    $obj = array(
        "app_name"=>$app_name,
        "apple_id"=>$apple_id,
        "sku" => $sku,
        "bundle_id" => $bundle_id,
        "app_type" => $app_type,
        "lang" => $lang,
        "ituneslink"=>$applink,
        "current_version"=>$ver1,
        "new_version"=>$ver2
    );
    $fn = BASE_META_DIR."/app_$appid/appmeta.dat";
    if (file_exists($fn)) {
      $cont = file_get_contents($fn);
      if ($cont) {
        $row = unserialize($cont);
        if ($row) {
          $obj["stat_max_report_date"] = $row["stat_max_report_date"];
          $obj["stat_min_report_date"] = $row["stat_min_report_date"];
          $obj["stat_last_day"] = $row["stat_last_day"];
          $obj["stat_last_month"] = $row["stat_last_month"];
          $obj["stat_whole_period"] = $row["stat_whole_period"];
        }
      }
    }
      
    $res = file_put_contents($fn,serialize($obj));
    $res=get_icons($appid, $ver1, $ver2);
    process_app_to_db($obj);
    if (!$res)
      return error("some problems while writing appmeta.dat file");
    return true;
  } 


//function fetches apps array from config.php for current login and runs scraping data about each app
  function process_apps($manage_apps_url, $login) {
  
    $res = getUrlContent2($manage_apps_url);
    if (!$res)
      return error("Couldn't get ManageYourApp page");

//parse manageapps page. there may be a few apps.
    $pat='/<div class="movieTitle app-search-recent" align="center">[\s]*<a title="(.*?)" href="([^>]*)">/si';
    $match=preg_match_all($pat, $res, $matches);
    if (!$match||!is_array($matches)||count($matches[1])==0)
      return error("parsing manageapps page failed");
    for ($i=0,$c=count($matches[1]);$i<$c;$i++) 
      if (!in_array($matches[1][$i],$login["apps"])) {
        echo($matches[1][$i]." app is not in a list of watchable apps. bypassing\n");
      } else
        process_app($matches[1][$i], $matches[2][$i]);

  }




//function fetches apps array from config.php for current login and runs scraping data about each app
//this version uses See All page content which sligthly differs by scructure
  function process_apps_seeall($see_all_url, $login) {
  
    $res = getUrlContent2($see_all_url);
    if (!$res)
      return error("Couldn't get ManageYourApp page");


//parse manageapps page. there may be a few apps.
//    $pat='/<div class="movieTitle app-search-recent" align="center">[\s]*<a title="(.*?)" href="([^>]*)">/si';
    $pat='/<div class="software-column-type-col-0 sorted">[\s]*<p>[\s]*<a href="([^>]*)">(.*?)<\/a>[\s]*<p>/si';
    $match=preg_match_all($pat, $res, $matches);
    if (!$match||!is_array($matches)||count($matches[1])==0)
      return error("parsing manageapps page failed");
    for ($i=0,$c=count($matches[1]);$i<$c;$i++) 
      if (!in_array($matches[2][$i],$login["apps"])) {
        echo($matches[2][$i]." app is not in a list of watchable apps. bypassing\n");
      } else {
        process_app($matches[2][$i], $matches[1][$i]);
//         echo("processing ".$matches[2][$i].", ".$matches[1][$i]." \n");
//    die();
    }
  }




//function processes single login from config.php $logins array
//it calls function for populating data about app and sales reports
  function process_login($login) {

    $res = getUrlContent2("/WebObjects/iTunesConnect.woa");
    if (!$res) 
      return error("getting start page failed");
//parsing action url (/WebObjects/iTunesConnect.woa/wo/1.0.9.3.5.2.1.1.3.1.1)
    $pat='/<form name="appleConnectForm" method="post" action="(.*?)">/si';
    preg_match($pat, $res, $matches);
    if (!is_array($matches)||count($matches)==0)
      return error("parsing auth url failed");
    $auth_url = $matches[1];

    global $scrape_options;
    if ($scrape_options["debug"])
      echo("ok. auth url: $auth_url\n");

    $res = getUrlContent2($auth_url, array("theAccountName"=>$login["appleid"], "theAccountPW"=>$login["password"]));
    if (!$res)
      return error("failed to perform auth");
    if (strpos($res,"Your AppleID or password was entered incorrectly")!==false)
      return error("auth is bad");

    if ($scrape_options["debug"])
      echo("auth OK\n");
//scraping mainboard page

    $pat='/<a href="([^>]*)">[\s]*<img border="0" onclick="" class="customActionButton" src="https:\/\/itc.mzstatic.com\/itc\/images\/btn-continue.png"[\s]*\/>[\s]*<\/a>/si';
    $match=preg_match($pat, $res, $matches);
    if ($match && is_array($matches)) {
      $url = $matches[1];
      if ($scrape_options["debug"])
        echo("some welcome warning detected. bypassing with url $url\n");
      $res = getUrlContent2($url);
      if (!$res)
        return error("failed to bypass warning page");
    }

//    file_put_contents("mainboard.html", $res);


//parsing mainboard page
//    $pat='/<form name="signOutForm" method="post" action="(.*?)">/si';
    $pat='/<li class="menu-item sign-out">[\s]*<a style="text-decoration:none; color: Gray;" href="([^"]*)">Sign Out<\/a>[\s]*<\/li>/si';
    $match=preg_match($pat, $res, $matches);
    if (!$match||!is_array($matches)||count($matches)==0)
      return error("Sign out form not found");
    $signOutUrl = $matches[1];
    if ($scrape_options["debug"])
      echo("signOutUrl=$signOutUrl\n");
//scraping Manage Your Applications

    $pat='/<a href="([^>]*)">Manage Your Applications<\/a>/si';


    $match=preg_match($pat, $res, $matches);
    if (!$match||!is_array($matches)||count($matches)==0)
      return error("Manage your apps pattern not found");

    $manage_apps_url = $matches[1];
//Getting Manage Your Applications url content
    $res_sa = getUrlContent2($manage_apps_url);
    if (!$res_sa) 
      return error("failed get Manage Your Applications page");
//Extracting See All page url
    $pat = '/<a href="([^>]*)">See All/si';
    $match=preg_match($pat, $res_sa, $matches_sa);
    if (!$match||!is_array($matches_sa)||count($matches_sa)==0)
      return error("See All pattern not found");

    $see_all_url = $matches_sa[1];
    process_apps_seeall($see_all_url, $login);


    $pat='/<td class="content">[\s]*<a href="(.*?)">[\s]*<b>Sales and Trends<\/b>[\s]*<\/a><br>/si';
    $match=preg_match($pat, $res, $matches);
    if (!$match||!is_array($matches)||count($matches)==0)
      return error("Sales and trends pattern not found");
    $sales_url = $matches[1];
    if ($scrape_options["debug"])
      echo("sales url: $sales_url\n");


//scraping sales report
    if (!NEED_SALES) return true;
    global $scrape_options, $need_log_requests;
    $filenames1 = process_sales_daily($sales_url, $login["appleid"], $scrape_options);
    $filenames2 = process_sales_weekly($sales_url, $login["appleid"], $scrape_options);
    $filenames = array();
    if (is_array($filenames1)) 
      $filenames = array_merge($filenames, $filenames1);
    if (is_array($filenames2)) 
      $filenames = array_merge($filenames, $filenames2);
    $need_log_requests = true;
    if (!$filenames1 || !$filenames2)
      warn("Some problems with getting sales reports");
    $app_ids=process_reports($filenames);
    if ($scrape_options["debug"])
      echo("got reports filenames:\n".implode("\n",$filenames)."\n");
//    if ($app_ids)
    $need_log_requests = false;
    if ($scrape_options["debug"])
      echo("signing out. ". $login["appleid"]." is saying good bye\n");
    $res = getUrlContent2($signOutUrl);
    return true;
  }







//script body. initializing environment and running basic mechanisms

  if (!defined("BASE_META_DIR")) {
    $curdir=dirname(__FILE__);
    if ($curdir[strlen($curdir)-1]!='/') $curdir.='/';
    $curdir.="meta/";
    define("BASE_META_DIR", $curdir);
    echo("BASE_META_DIR is not defined. assigned to script dir ".BASE_META_DIR."\n");
  }

  if (isset($scrape_sales_at) && (int)date("H")!=$scrape_sales_at) {
    if ($scrape_options["debug"]) 
      echo("skipping sales report scraping - it's not a time (need wait for $scrape_sales_at)\n");
    define("NEED_SALES", false);
  } else
    define("NEED_SALES", true);

  $conn = get_connect();
  if (!$conn) 
    die("DB connection failed to established\n");
  else
    echo("DB connection established\n");

  if (!isset($logins)||!is_array($logins))
    die('nothing to scrape');

  if (!is_dir(BASE_META_DIR) && !mkdir(BASE_META_DIR))
    die("couldn't access to directory for storing meta data [".BASE_META_DIR."]\n");
  if (!is_dir(BASE_META_DIR.'/temp') && !mkdir(BASE_META_DIR.'/temp'))
    die("couldn't access to directory for storing meta data [".BASE_META_DIR."/temp]\n");

  $success = false;
  $succ_cnt = 0;
  $total_cnt = 0;
  $cookie_files = array();
  foreach($logins as $login=>$data) {
    $total_cnt++;
    if ($scrape_options["verbose"])
      echo("processing login $login\n");
  //specifying location for storing cURL cookies
    $cookiefile = BASE_META_DIR."temp/itc_cookies".time();  
    $cookie_files[] = $cookiefile;
    $res = process_login($data);
    if ($res)  {
      $success = true;
      if ($scrape_options["verbose"])
        echo("\n\n\n**********************\nprocessing login $login succeeded\n");
      $succ_cnt++;
    } else
      if ($scrape_options["verbose"])
        die("processing login $login failed\n");
    curl_close($ch);
    $ch = false;
  }
  agregate_sales();
  if ($scrape_options["verbose"]) {
    echo("removing temp cookie files\n");
    foreach($cookie_files as $c)
      unlink($c);
  }
    
  if ($success) {
    file_put_contents(BASE_META_DIR."/last_update_date", time());
    if (NEED_SALES)
      file_put_contents(BASE_META_DIR."/sales_last_update_date", time());
  }
  echo("\n*******************\nALL DONE!\nLogins processed: $total_cnt\nLogins Successfully processed: $succ_cnt\n\n");