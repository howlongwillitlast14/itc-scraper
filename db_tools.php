<?php
//this php unit contains a set of functions for interacting with MySQL database engine
//One can implement interaction with some other known db engine

function get_connect() {
  $pdo = new PDO( 
     'mysql:host='.DB_HOST.';dbname='.DB_NAME, 
     DB_USER, 
     DB_PASS, 
     array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8;")
   );
   return $pdo;
}


function execSQL($conn, $sql, $values = false) {
  global $scrape_options;
  if ($scrape_options["debug"])
    echo("execSQL: $sql\n");

  try {
    $stmt = $conn->prepare($sql);
    if ($values) 
      foreach($values as $param=>$value)
        $stmt->bindValue($param, $value);
      if ( ! $stmt->execute() ) {
        if ($scrape_options["debug"])
          echo ("PDO Error: ".var_dump($stmt->errorInfo())."\n");
        return false;
      }
  } catch (PDOException $e) {
      if ($scrape_options["debug"])
        echo ("Exception: " . $e->getMessage() . "\n");
      return false;
  }
  return $stmt;
}


function sec2hms ($sec, $padHours = false) {
    $hms = "";
    $hours = intval(intval($sec) / 3600); 
    $hms .= ($padHours) 
          ? str_pad($hours, 2, "0", STR_PAD_LEFT). ":"
          : $hours. ":";
    $minutes = intval(($sec / 60) % 60); 
    $hms .= str_pad($minutes, 2, "0", STR_PAD_LEFT). ":";
    $seconds = intval($sec % 60); 
    $hms .= str_pad($seconds, 2, "0", STR_PAD_LEFT);
    return $hms;    
}

function process_app_to_db ($app) {
  global $conn;
  $ids = array();
  $sql = "
call sp_itc_process_app(
:Title,
:SKU,
:BundleId,
:AppleId, 
:AppType,
:DefaultLanguage,
:AppstoreLink,
:cver_version,
:cver_status,
:cver_status_color,
:cver_date_created,
:cver_date_released,
:nver_version,
:nver_status,
:nver_status_color,
:nver_date_created,
:nver_date_released);    
  ";
  if (isset($conn) && $conn) {
    return execSQL($conn, $sql, array (
":Title" => $app["app_name"],
":SKU" => $app["sku"],
":BundleId" => $app["bundle_id"],
":AppleId" => $app["apple_id"], 
":AppType" => $app["app_type"],
":DefaultLanguage" => $app["lang"],
":AppstoreLink" => $app["ituneslink"],
":cver_version" => $app["current_version"]["version"],
":cver_status" => $app["current_version"]["status"],
":cver_status_color" => $app["current_version"]["status_color"],
":cver_date_created" => $app["current_version"]["date_created"],
":cver_date_released" => $app["current_version"]["date_released"],
":nver_version" => $app["new_version"]["version"],
":nver_status" => $app["new_version"]["status"],
":nver_status_color" => $app["new_version"]["status_color"],
":nver_date_created" => $app["new_version"]["date_created"],
":nver_date_released" => $app["new_version"]["date_released"]
         )
    );
  } else
    return false;
}

function DMYToTime($dt) {
// 04/05/2012
  if (trim($dt)=='') return false;
  $dt = explode('/', $dt);
  if (count($dt)!=3) return false;
  return mktime(0,0,0,$dt[0], $dt[1], $dt[2]);
}

function process_report_to_db($columns, $values) {
  global $conn;
  $sql = "call sp_itc_process_report(".implode(",",$columns).");";
  $vals = array();
  $appid = false;
  for ($i=0,$c=count($columns);$i<$c;$i++) {
    $vals[$columns[$i]] = trim($values[$i]);
    if ($columns[$i] == ":AppleIdentifier")
      $appid = $values[$i];
  }
  $vals[":BeginDate"] = DMYToTime($vals[":BeginDate"]);
  $vals[":EndDate"] = DMYToTime($vals[":EndDate"]);

  if (isset($conn) && $conn)
    return execSQL($conn, $sql, $vals)?$appid:false;
  else
    return false;
}


//this function perform agregation of sales reports
//it stores download stats about Last report day, Last report month and whole available period 
//into meta directory so the script which displays stat in browser doesn't need to make db-connection
function agregate_sales() {
   global $conn, $scrape_options;

  $dir = scandir(BASE_META_DIR);
  if (!$dir)
    return error("No apss were found.\n");

  $cnt = 0;
  $weekbeg = mktime(0, 0, 0, date('m'), date('d'), date('Y')) - ((date('N')-1)*24*60*60);
//  echo("agregating apps ".implode(",",$app_ids)."\n");
  foreach($dir as $f) {
    if (substr($f,0,4) != "app_") continue;
//    if (!$apple_id) continue;
    $app = file_get_contents(BASE_META_DIR."$f/appmeta.dat");
    if (!$app) continue;
    $app = unserialize($app);
    if (!$app) continue;
    $apple_id = $app["apple_id"];

    if ($scrape_options["debug"])
      echo("agregating stats for apple_id=$apple_id\n");

   $res = execSQL($conn, "
select
(select max(BeginDate) from itc_sales where AppleIdentifier=:apple_id) as stat_max_report_date,
(select min(BeginDate) from itc_sales where AppleIdentifier=:apple_id) as stat_min_report_date,
(select sum(units) from itc_sales where AppleIdentifier=:apple_id and BeginDate= EndDate and BeginDate = ifnull((select max(BeginDate) from itc_sales where AppleIdentifier=:apple_id), 0) group by AppleIdentifier) stat_last_day,
(select sum(units) from itc_sales where AppleIdentifier=:apple_id and BeginDate= EndDate and BeginDate > unix_timestamp() - 30 * 24 * 60 * 60 group by AppleIdentifier) stat_last_month,
(select sum(units) from itc_sales where AppleIdentifier=:apple_id and (BeginDate<>EndDate or (BeginDate=EndDate and BeginDate>=$weekbeg)) group by AppleIdentifier) stat_whole_period
from
dual    
    ", array(":apple_id"=>$apple_id));
    if (!$res) {
      echo("some problems with performing sql request. skipping\n");
      continue;
    }
    $row=$res->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
      echo("no data has been returned\n");
      continue;
    }
    $app["stat_max_report_date"] = $row["stat_max_report_date"];
    $app["stat_min_report_date"] = $row["stat_min_report_date"];
    $app["stat_last_day"] = $row["stat_last_day"];
    $app["stat_last_month"] = $row["stat_last_month"];
    $app["stat_whole_period"] = $row["stat_whole_period"];
    $cont = serialize($app);
    if (!$cont) {
      echo("some problems with serializing app data: ". json_encode($app)."\n skipping");
      continue;
    }
    if (!file_put_contents(BASE_META_DIR."$f/appmeta.dat", $cont))
      echo("some problems with writing serialized data\n");
  }
}