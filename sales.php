<?php
//this php unit contains main routines for scraping iTunesConnect sales reports

  function process_sales($sales_url, $appleid, $options, $reptype) {
    if (!is_dir(BASE_META_DIR."/salesreports") && !mkdir(BASE_META_DIR."/salesreports"))
      return error("couldn't access salrereports directory");
    if (!is_dir(BASE_META_DIR."/salesreports/$appleid") && !mkdir(BASE_META_DIR."/salesreports/$appleid"))
      return error("couldn't access salrereports directory for appleid=$appleid");
    if (!is_dir(BASE_META_DIR."/salesreports/$appleid/$reptype") && !mkdir(BASE_META_DIR."/salesreports/$appleid/$reptype"))
      return error("couldn't access salrereports $reptype directory for appleid=$appleid");

    $res = getUrlContent2("https://reportingitc.apple.com/");
    if (!$res)
      return error("Couldn't get Sales&Trends page");

    $pat = '/"javax.faces.ViewState" value="(.*?)"/si';
    $matches = array();
    preg_match($pat, $res, $matches);
    if (count($matches)==0)
      return error("javax.faces.ViewState pattern not found\n");

    $viewState = $matches[1];

    $pat = '/script id="defaultVendorPage:(.*?)"/si';
    $matches = array();
    preg_match($pat, $res, $matches);
    if (count($matches)==0)
      return error("defaultVendorPage pattern not found");
    $defaultVendorPage = $matches[1];
    $ajaxName = str_replace('_2', '_0', $defaultVendorPage);
    if ($options["debug"]) {
      echo("viewState:  $viewState\n");
      echo("defaultVendorPage: $defaultVendorPage\n");
      echo("ajaxName: $ajaxName\n");
    }


    # This may seem confusing because we just accessed the vendor default page in the 
    # code above. However, the vendor default page as a piece of javascript that runs
    # once the page is loaded in the browser. The javascript does a resubmit. My guess
    # is this action is needed to set the default vendor on the server-side. Regardless
    # we must call the page again but no parsing of the HTML is needed this time around.
    $urlDefaultVendorPage = 'https://reportingitc.apple.com/vendor_default.faces';
    $webFormSalesReportData = urllib_urlencode(
      array(
        'AJAXREQUEST'=>$ajaxName, 
        'javax.faces.ViewState'=>$viewState, 
        'defaultVendorPage'=>$defaultVendorPage, 
        'defaultVendorPage:'.$defaultVendorPage=>'defaultVendorPage:'.$defaultVendorPage));
    $res = getUrlContent2($urlDefaultVendorPage.$webFormSalesReportData);
    if (!$res)
      return error("failed to get default vendor page");

   
    # Check for notification messages.
    $urlDashboard = 'https://reportingitc.apple.com/subdashboard.faces';
    $res = getUrlContent2($urlDashboard);
    if (!$res)
      return error("failed to get Dashboard page");

    $pat='/(?s)<div class="notification">(.*?)<\/span>/si';
    $matches = array();
    preg_match($pat, $res, $matches);
    if (count($matches)==0)
      warn("dashboard notification pattern not found");
	else {
  	  $notificationDiv = $matches[1];

      $pat='/(?s)<td>(.*?)<\/td>/si';
      $matches = array();
      preg_match($pat, $notificationDiv, $matches);
      if (count($matches)==0)
        warn("dashboard notificationMsg pattern not found");
      $notificationMessage = $matches[1];
      if ($options["debug"])
        echo("$notificationMessage\n");
    }

    # Access the sales report page.
    if ($options["debug"])
      echo("Accessing sales report web page.\n");
    $urlSalesReport = 'https://reportingitc.apple.com/sales.faces';
    $urlJsonHolder = 'https://reportingitc.apple.com/jsp/json_holder_sales.faces';
    $res = getUrlContent2($urlSalesReport);

    if (!$res)
      return error("failed to get sales report page");

    $pat='/"javax.faces.ViewState" value="(.*?)"/si';
    $matches = array();
    preg_match($pat, $res, $matches);
    if (count($matches)==0)
      return error("javax.faces.ViewState pattern not found\n");
	$viewState = $matches[1];

    $pat='/"theForm:j_id_jsp_[0-9]*_51"/si';
    $matches = array();
    preg_match($pat, $res, $matches);
    if (count($matches)==0)
      return error("theForm pattern not found");
	$dailyName = str_replace('"', '', $matches[0]);
    $ajaxName = str_replace('_51', '_2', $dailyName);
    $dateName = str_replace('_51', '_8', $dailyName);
    $selectName = str_replace('_51', ($reptype=='daily')?'29':'_10', $dailyName);
    # Get the form field names needed to download the report.
    if ($options["debug"]) {
      echo("viewState: $viewState\n");
      echo("dailyName: $dailyName\n");
      echo("ajaxName: $ajaxName\n");
      echo("dateName: $dateName\n");
      echo("selectName: $selectName\n");
    }

    # Get the list of available dates.
    $pat='/(?s)<div class="pickList">(.*?)<\/div>/si';
    $matches = array();
    preg_match_all($pat, $res, $matches);
    if (count($matches[1])==0)
      return error("dates list pattern not found");
    preg_match_all('/<option value="(.*?)"/si', $matches[1][0], $matches2);
    if (count($matches2[1])==0)
      return error("dateListAvailableDays available list pattern not found");
    $dateListAvailableDays = $matches2[1];

    $matches2 = array();
    preg_match_all('/<option value="(.*?)"/si', $matches[1][1], $matches2);
    if (count($matches2[1])==0)
      return error("dateListAvailableWeeks available list pattern not found");

    $dateListAvailableWeeks = $matches2[1];
    if ($options["debug"]) {
      echo("dateListAvailableDays: ".implode(", ", $dateListAvailableDays)."\n");
      echo("dateListAvailableWeeks: ". implode(", ", $dateListAvailableWeeks)."\n");
    }

    # Click through from the dashboard to the sales page.
    $webFormSalesReportData = urllib_urlencode(array('AJAXREQUEST'=>$ajaxName, 'theForm'=>'theForm', 'theForm:xyz'=>'notnormal', 'theForm:vendorType'=>'Y', 'theForm:datePickerSourceSelectElementSales'=>$dateListAvailableDays[0], 'theForm:weekPickerSourceSelectElement'=>$dateListAvailableWeeks[0], 'javax.faces.ViewState'=>$viewState, $dailyName=>$dailyName, 'theForm:optInVar'=>'A',  'theForm:dateType'=>'D', 'theForm:optInVarRender'=>'false', 'theForm:wklyBool'=>'false'));
    $res = getUrlContent2($urlSalesReport.$webFormSalesReportData);
    if (!$res)
      return error("couldn not perform click through dashboard sales page");
    $matches = array();
    preg_match('/"javax.faces.ViewState" value="(.*?)"/si', $res, $matches);
    if (count($matches)==0)
      return error("javax.faces.ViewState pattern not found\n");
    $viewState = $matches[1];
    if ($options["debug"])
      echo("viewState=$viewState\n");    

    # Set the list of report dates.
    # A better approach is to grab the list of available dates
    # from the web site instead of generating the dates. Will
    # consider doing this in the future.
    if ($reptype=='daily')
      $reportDates = getDatesArray($dateListAvailableDays);
    else
      $reportDates = getDatesArray($dateListAvailableWeeks);
    if ($options["debug"]) {
      echo("reportDates: ". implode(", ", $reportDates)."\n");
      echo("Downloading daily sales reports. overWriteFiles:".$options["overWriteFiles"]."\n");
    }
    
    $unavailableCount = 0;
    $filenames = array();
    global $last_http_headers;

    $rep_filenames = scandir(BASE_META_DIR."/salesreports/$appleid/$reptype/");
    $ccnt=0;
    foreach($reportDates as $downloadReportDate) {
      $ccnt++;
//        if ($ccnt++>2) break;
        # Set the date within the web page.
        $dateString = date('m/d/Y', $downloadReportDate);
        
        if (in_array($dateString, ($reptype=='daily'?$dateListAvailableDays:$dateListAvailableWeeks))) {
            # If told not to overwrite files, check before downloading
            if (!$options["overWriteFiles"]) {
                # Only if custom formatting was enabled, we can check if file exists before downloading
                if ($options["outputFormat"]) {
                    $filename = BASE_META_DIR."/salesreports/$appleid/$reptype/".date($options["outputFormat"], $downloadReportDate);
                    if ($options["unzipFile"] && substr($filename, -3) == '.gz') #Chop off .gz extension if not needed
                        $filename = substr($filename, 0, strlen($filename)-3);
                    if (file_exists($filename)) {
                        if ($options["debug"])
                            echo("Report file, $filename, exists, skipping.\n");
                        continue;
                    }
                } else 
                  if (report_exists($rep_filenames, $downloadReportDate)) {
                     if ($options["debug"])
                       echo("Report file with specified date pattern exists, skipping.\n");
                     continue;
                  }
	    }
            if ($options["debug"])
                echo("Downloading report for:  $dateString ccnt=$ccnt\n");

/*            if ($options["debug"])
              echo("trying to get json_holder_sales.faces\n");
            $webFormSalesReportData = urllib_urlencode(array(
              'dtValue'=>$dateString ,
              'dateType'=>($reptype=='daily'?"D":"W")
            )); */

            $res = getUrlContent2($urlJsonHolder.$webFormSalesReportData);
            if (!$res) {
              warn("problems with getting json holder sales for $dateString");
              continue;
            }
//            file_put_contents("meta/jsonholder_$ccnt.html", $res);

            if ($reptype=='daily')
              $webFormSalesReportData = urllib_urlencode(array(
                'AJAXREQUEST'=>$ajaxName, 
                'theForm' => 'theFormc',
                'theForm:vendorLogin' => '',
                'theForm:arrayLength' => '1',
                'theForm:userType' => 'notnormal',
                'theForm:vendorType' => 'Y',
                'theForm:optInVar' => 'A',
                'theForm:dateType' => 'D',
                'theForm:prodtypesel' => 'iOS',
                'theForm:subprodsel' => 'Free Apps',
                'theForm:subprodlabel' => 'freeAppLabel',
                'theForm:contentType' => 'iOS',
                'theForm:contentSubType' => 'Free Apps',
                'theForm:optInVarRender' => 'false',
                'theForm:wklyBool' => 'false',
                '' => '',
                'theForm:listVendorHideId' => '',
                'theForm:defaultVendorSelected' => '',
                'theForm:datePickerSourceSelectElementSales'=>$dateString, 
                'theForm:weekPickerSourceSelectElement'=>$dateListAvailableWeeks[0], 
                'javax.faces.ViewState'=>$viewState, $selectName=>$selectName)
              );
            else
              $webFormSalesReportData = urllib_urlencode(array(
                'AJAXREQUEST' => $ajaxName,
                'theForm' => 'theForm',
                'theForm:vendorLogin' => '',
                'theForm:arrayLength' => '1',
                'theForm:userType' => 'notnormal',
                'theForm:vendorType' => 'Y',
                'theForm:optInVar' => 'A',
                'theForm:dateType' => 'D',
                'theForm:prodtypesel' => 'iOS',
                'theForm:subprodsel' => 'Free Apps',
                'theForm:subprodlabel' => 'freeAppLabel',
                'theForm:contentType' => 'iOS',
                'theForm:contentSubType' => 'Free Apps',
                'theForm:optInVarRender' => 'false',
                'theForm:wklyBool' => 'false',
                '' => '',
                'theForm:datePickerSourceSelectElementSales' => $dateListAvailableWeeks[0],
                'theForm:weekPickerSourceSelectElement' => $dateString,
                'theForm:listVendorHideId' => '',
                'theForm:defaultVendorSelected' => '',
                'javax.faces.ViewState' => $viewState,
                $selectName=>$selectName,

                )
              );

            $res = getUrlContent2($urlSalesReport.$webFormSalesReportData);
            if (!$res) {
              warn("problems with getting sales report for $dateString");
              continue;
            }
//            file_put_contents("meta/ajaxresonse_$ccnt.html", $res);
            $matches = array();
            preg_match('/"javax.faces.ViewState" value="(.*?)"/si', $res, $matches);
            if (count($matches)==0) {
              if ($options["debug"])
                echo("javax.faces.ViewState pattern not found\n");
              continue;
            }
		    $viewState = $matches[1];
            
            # And finally...we're ready to download yesterday's sales report.

            if ($reptype=='daily')
              $webFormSalesReportData = urllib_urlencode(array(
                'theForm' => 'theForm',
                'theForm:vendorLogin' => '',
                'theForm:arrayLength' => '1',
                'theForm:userType' => 'notnormal',
                'theForm:vendorType' => 'Y',
                'theForm:optInVar' => 'A',
                'theForm:dateType' => 'D',
                'theForm:prodtypesel' => 'iOS',
                'theForm:subprodsel' => 'Free Apps',
                'theForm:subprodlabel' => 'freeAppLabel',
                'theForm:contentType' => 'iOS',
                'theForm:contentSubType' => 'Free Apps',
                'theForm:optInVarRender' => 'false',
                'theForm:wklyBool' => 'false',
                'theForm:datePickerSourceSelectElementSales'=>$dateString, 
                'theForm:weekPickerSourceSelectElement'=>$dateListAvailableWeeks[0], 
                'theForm:listVendorHideId' => '',
                'theForm:defaultVendorSelected' => '',
                'javax.faces.ViewState'=>$viewState, 
                'theForm:downloadLabel2'=>'theForm:downloadLabel2')
              );
            else
              $webFormSalesReportData = urllib_urlencode(array(
                'theForm' => 'theForm',
                'theForm:vendorLogin' => '',
                'theForm:arrayLength' => '1',
                'theForm:userType' => 'notnormal',
                'theForm:vendorType' => 'Y',
                'theForm:optInVar' => 'A',
                'theForm:dateType' => 'W',
                'theForm:prodtypesel' => 'iOS',
                'theForm:subprodsel' => 'Free Apps',
                'theForm:subprodlabel' => 'freeAppLabel',
                'theForm:contentType' => 'iOS',
                'theForm:contentSubType' => 'Free Apps',
                'theForm:optInVarRender' => 'false',
                'theForm:wklyBool' => 'false',
                'theForm:datePickerSourceSelectElementSales' => $dateListAvailableWeeks[0],
                'theForm:weekPickerSourceSelectElement' => $dateString,
                'theForm:listVendorHideId' => '',
                'theForm:defaultVendorSelected' => '',
                'javax.faces.ViewState' => $viewState,
                'theForm:downloadLabel2' => 'theForm:downloadLabel2'
                )
              );

            if ($options["debug"])
 	      echo("urlSalesReport: $urlSalesReport\nwebFormSalesReportData: $webFormSalesReportData\n");
            $attcnt =0;
            while (true) {
            $res = getUrlContent2($urlSalesReport.$webFormSalesReportData, false, true);
            if (!$res) {
                if ($options["debug"])
                  echo("Unable to download this report. skipping\n");
            	continue;
            }
//            file_put_contents("meta/".time().".gz", $res);        
                # Check for the content-disposition. If present then we know we have a 
                # file to download. If not present then an AttributeError exception is
                # thrown and we assume the file is not available for download.
                $filename = $last_http_headers["content-disposition"];
                if (!$filename && $attcnt == 5) {
                  if ($options["debug"])
                    echo("content-disposition http header not found. skipping this report\n");
                  continue;
                } else break;
                $attcnt++;
                if ($options["debug"])
                  echo("try again. attempt $attcnt\n");
            }
             if (!$filename) continue;
                $ar = explode("=", $filename);
                $filename = $ar[1];
                # Check for an override of the file name. If found then change the file
                # name to match the outputFormat.
                if ($options["outputFormat"])
                    $filename = date($options["outputFormat"], $downloadReportDate);

                $filebuffer = $res;
                $filename = BASE_META_DIR."salesreports/$appleid/$reptype/".$filename;
                
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
        } else {
            if ($options["debug"])
              echo("$dateString report is not available - try again later. \n");
            $unavailableCount++;
        }
    }
    # End for downloadReportDate in reportDates:
    ####

    if ($unavailableCount > 0 && $options["verbose"])
        echo( "$unavailableCount report(s) not available - try again later\n");

    return $filenames;
  }

  function process_sales_daily($sales_url, $appleid, $options) {
    return process_sales($sales_url, $appleid, $options, 'daily');
  }
  function process_sales_weekly($sales_url, $appleid, $options) {
    return process_sales($sales_url, $appleid, $options, 'weekly');
  }

