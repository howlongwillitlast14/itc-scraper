<?php
  $ch = false;
  $last_http_code = 200;
  $last_http_headers = false;
  $need_log_requests = false;

  function report_exists($files, $rep_date) {
    $pat = "_".date("Ymd", $rep_date).".txt";
    global $scrape_options;
    if ($scrape_options["debug"])
      echo("looking for repdate pattern $pat\n");
    foreach($files as $f) 
      if (substr($f, -strlen($pat))==$pat) return true;
    return false;
  }


  function urllib_urlencode($arr) {
    $res = "";
    $cnt=0;
    foreach($arr as $key=>$val) {
      $res .= ($cnt++>0?"&":"").urlencode($key)."=".urlencode($val);
    }
    return "?$res";
  }

  function getDatesArray($apple_dates) {
    $res = array();
    if (!is_array($apple_dates)) return $res;
    foreach($apple_dates as $dt) {
      $d = explode("/", $dt);
      if (count($d)!=3) continue;
      $res[] = mktime(0,0,0,$d[0], $d[1], $d[2]);
    }
    return $res;
  }


  function readHeaderBulk($resURL, $strHeader) { 
    return strlen($strHeader);
  }

  function readHeader($resURL, $strHeader) { 
    global $last_http_headers;
    $ar = explode(":", $strHeader);
    $last_http_headers[strtolower(trim($ar[0]))] = count($ar)>1?trim($ar[1]):"";
    return strlen($strHeader);
  }

  function getFullUrl($url) {
    if (substr($url, 0, 7)=="http://" ||substr($url, 0, 8)=="https://")
      return $url;
    if ($url[0]!='/')
      $url="/$url";
    return BASE_URL.$url;
  }

  function getUrlContent($url, $postvars = false, $gethdr=false) {
    $url = getFullUrl($url);
    global $ch, $need_log_requests, $scrape_options;
    if (!$ch) {
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_STDERR, $fp);
    } else
      curl_setopt($ch, CURLOPT_URL,$url);
    global $last_http_code;
    global $last_http_headers;
    $last_http_headers = array();
    if ($scrape_options["debug"])
      echo('>>>  getting url '.$url.($postvars?" with postvars=".json_encode($postvars):""));
    if ($postvars){ 
     curl_setopt($ch, CURLOPT_POST, count($postvars));
     curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postvars));		
    }

    if ($gethdr) {
      curl_setopt($ch, CURLOPT_HEADER, 0);
      curl_setopt($ch, CURLOPT_HEADERFUNCTION, 'readHeader');
      curl_setopt($ch, CURLOPT_HTTPHEADER, array("Expect:"));
    } else {
      curl_setopt($ch, CURLOPT_HEADERFUNCTION, 'readHeaderBulk');
      curl_setopt($ch, CURLOPT_HTTPHEADER, array("Expect:"));
    }
    $header[0] = "Accept: text/xml,application/xml,application/xhtml+xml,application/a-gzip,"; 
    $header[0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5"; 
    $header[] = "Cache-Control: max-age=0"; 
    $header[] = "Connection: keep-alive"; 
    $header[] = "Keep-Alive: 300"; 
    $header[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7"; 
    $header[] = "Accept-Language: en-us,en;q=0.5"; 
    $header[] = "Pragma: "; // browsers keep this blank. 

    curl_setopt($ch, CURLOPT_URL, $url); 
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/535.1 (KHTML, like Gecko) Chrome/13.0.782.220 Safari/535.1'); 
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header); 
    curl_setopt($ch, CURLOPT_REFERER, 'https://itunesconnect.apple.com'); 
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate'); 
    curl_setopt($ch, CURLOPT_AUTOREFERER, true); 
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    global $cookiefile;
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookiefile);  
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookiefile);  
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); 

    $cont = curl_exec($ch);

    $last_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($cont&&$last_http_code==200) {
      if ($scrape_options["debug"])
        echo("  url got\n"); 
      $last_http_headers = array_merge($last_http_headers, curl_getinfo($ch));
    } else {
      if ($scrape_options["debug"])
        echo("  url failed. http_status=$last_http_code\n");
      $cont=false;
    }
    return $cont;
  }

  function getUrlContent2($url, $postvars = false, $gethdr=false) {
    $attcnt = 1;
    global $last_http_code, $scrape_options;
    while(true) {
      if ($attcnt>=MAXATTEMPT)
        return error("aborting getUrlContent2 after ".MAXATTEMPT." attempts getting content");
      $cont = getUrlContent($url, $postvars, $gethdr);
      if ($last_http_code == 404) {
        return error("page not found. strange");
        break;
      };
      if ($cont) break;
      if ($scrape_options["debug"])
        echo ("sleeping for $attcnt sec\n");
      sleep($attcnt);
      $attcnt++;
      if ($scrape_options["debug"])
        echo("  attempt $attcnt\n");
    }
    return $cont;
  }

