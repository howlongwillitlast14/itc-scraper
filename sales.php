<?php
//this php unit contains main routines for scraping iTunesConnect sales reports

  function process_sales($login, $options, $reptype) {
    global $last_http_headers;
    $appleid = $login["appleid"];

//  function process_sales($sales_url, $appleid, $options, $reptype) {
    $reptype_l = strtolower($reptype);
    if (!is_dir(BASE_META_DIR."/salesreports") && !mkdir(BASE_META_DIR."/salesreports"))
      return error("couldn't access salrereports directory");
    if (!is_dir(BASE_META_DIR."/salesreports/$appleid") && !mkdir(BASE_META_DIR."/salesreports/$appleid"))
      return error("couldn't access salrereports directory for appleid=$appleid");
    if (!is_dir(BASE_META_DIR."/salesreports/$appleid/$reptype_l") && !mkdir(BASE_META_DIR."/salesreports/$appleid/$reptype_l"))
      return error("couldn't access salrereports $reptype_l directory for appleid=$appleid");
    $filenames = array();
    $attcnt = 0;

    if ($reptype_l=='daily') {
      $from = 0;
      $to = 30;
    } else {
      $from = 0;
      $to = 24;
    }

    $weekbeg = mktime(0, 0, 0, date('m'), date('d'), date('Y')) - ((date('N')-1)*24*60*60) - 24*60*60;
    for ($i=$from;$i<$to;$i++) {
      if ($reptype_l=='daily')
        $dt = date("Ymd",time()-$i*24*60*60);
      else
        $dt = date("Ymd",$weekbeg-7*$i*24*60*60);

      $fntocheck = "S_".$reptype[0]."_".$login["vendorid"]."_$dt.txt";
      echo("checkin report file $fntocheck... ");
      if (file_exists(BASE_META_DIR."salesreports/$appleid/$reptype_l/".$fntocheck)) {
        echo("report previously got. skipping \n");
        continue;
      }
      echo("new report, getting\n");
      echo("$reptype processing date $dt\n");
      $postvars = array(
         "USERNAME"=>$login["appleid"],
         "PASSWORD"=>$login["password"],
         "VNDNUMBER"=>$login["vendorid"],
         "TYPEOFREPORT"=>"Sales",
         "DATETYPE"=>$reptype,
         "REPORTTYPE"=>"Summary",
         "REPORTDATE"=>$dt
      );

      $res = getUrlContent("https://reportingitc.apple.com/autoingestion.tft?",$postvars,true);

      $filename = isset($last_http_headers["content-disposition"])?$last_http_headers["content-disposition"]:false;
      $ERRORMSG = isset($last_http_headers["ERRORMSG"])?$last_http_headers["ERRORMSG"]:false;

      if ($ERRORMSG)
        echo("ITC Error: $ERRORMSG\n");


      echo("filename=$filename\n");
      if (!$filename && $attcnt == 5) {
       if ($options["debug"])
         echo("content-disposition http header not found. skipping this report\n");
       continue;
      }
      $attcnt++;


      if(!$filename) continue;
      $ar = explode("=", $filename);
      $filename = $ar[1];
      # Check for an override of the file name. If found then change the file
      # name to match the outputFormat.
      if ($options["outputFormat"])
          $filename = date($options["outputFormat"], $downloadReportDate);


      $filebuffer = $res;
      $filename = BASE_META_DIR."salesreports/$appleid/$reptype_l/".$filename;
      
      if ($options["unzipFile"] && substr($filename, -3) == '.gz')  #Chop off .gz extension if not needed
          $filename = substr($filename, 0, strlen($filename) - 3);

      if ($options["debug"])
        echo("Saving download file: $filename\n");
      file_put_contents($filename, $filebuffer);
      if ($options["unzipFile"]) {
        if ($options["debug"])
          echo("unzipping report file\n");
        $HandleRead = gzopen($filename, "rb");
        $rest = substr($filebuffer, -4);
        $GZFileSize = end(unpack("V", $rest));
        $report = gzread($HandleRead, $GZFileSize);
        gzclose($HandleRead);
        if (!$report) {
          warn("some problems with unzipping\n");
          continue;
        }                
        file_put_contents($filename, $report);
        if ($options["debug"])
          echo("unzipping succeeded\n");
      }
      $filenames[] = $filename;

    }
    return $filenames;
  }

  function process_sales_daily($login, $options) {
    return process_sales($login, $options, 'Daily');
  }
  function process_sales_weekly($login, $options) {
    return process_sales($login, $options, 'Weekly');
  }

