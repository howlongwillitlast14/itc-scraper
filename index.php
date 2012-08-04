<?php 
  date_default_timezone_set('Europe/Moscow');
  function showApp ($app, $rowcl) {
	$appid = $app["appid"];
?>
<div class='app_frame <?php echo($rowcl); ?>'>
  <img class='app_icon' src="meta/app_<?php echo($app["apple_id"]); ?>/curr_icon.png" />
  <div class='app_meta'>
   <div class='app_name'><?php echo($app["app_name"]); ?>&nbsp<span>(<a target='_blank' href="<?php echo($app["ituneslink"]); ?>">iTunes link</a>)</span></div>
   <div class='ver_frame status_<?php echo($app["current_version"]["status_color"]); ?>'>
     current version: <span class='ver_status'><?php echo($app["current_version"]["version"]); ?></span>
      status: <span class='ver_status'><?php echo($app["current_version"]["status"]); ?></span><br/>
     date created: <span class='date_span'><?php echo(date("d-M-Y",$app["current_version"]["date_created"])); ?></span>
     <?php if ($app["current_version"]["date_released"]) {
	?>
     date released: <span class='date_span'><?php echo(date("d-M-Y",$app["current_version"]["date_released"])); ?></span>
	<?php
           } ?>
   </div>
     <?php
     if ($app["new_version"]&&$app["new_version"]["version"]) {
	?>
   <div class='ver_frame status_<?php echo($app["new_version"]["status_color"]); ?>'>
     new version: <span class='ver_status'><?php echo($app["new_version"]["version"]); ?></span>
     status: <span class='ver_status'><?php echo($app["new_version"]["status"]); ?></span><br/>
     date created: <span class='date_span'><?php echo(date("d-M-Y",$app["new_version"]["date_created"])); ?></span>
     <?php if ($app["new_version"]["date_released"] && $app["new_version"]["date_released"]!=0) {
	?>
     date released: <span class='date_span'><?php echo(date("d-M-Y",$app["new_version"]["date_released"])); ?></span>
	<?php
           } ?>
   </div>
	<?php
     }
     ?>
     <div class="download_stat">download stat</div>
     <div class='stat_table'>
       <table cellpadding='0' cellspacing='0' border='0'>
         <tr>
          <td class='period'>For last report day (<?php echo(isset($app["stat_max_report_date"])?date("d-M-Y", $app["stat_max_report_date"]):"n/a"); ?>): </td>
          <td class='units_col'><?php echo(isset($app["stat_last_day"])?$app["stat_last_day"]:"n/a"); ?> <span style='font-weight:normal'>unit(s)</span></td>
         </tr>
         <tr>
          <td class='period'>For last month: </td>
          <td class='units_col'><?php echo(isset($app["stat_last_month"])?$app["stat_last_month"]:"n/a"); ?> <span style='font-weight:normal'>unit(s)</span></td>
         </tr>
         <tr>
          <td class='period'>For whole period (since <?php echo(isset($app["stat_min_report_date"])?date("d-M-Y", $app["stat_min_report_date"]):"n/a"); ?>): </td>
          <td class='units_col'><?php echo(isset($app["stat_whole_period"])?$app["stat_whole_period"]:"n/a"); ?> <span style='font-weight:normal'>unit(s)</span></td>
         </tr>
       </table>
     </div>
  </div>
  <div class="fixed"></div>

</div>
<?php	
  }
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">
<head profile="http://gmpg.org/xfn/11">
 <meta http-equiv="Content-Type" content="text/html; charset=Windows-1251" />
 <meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />
 <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"/> 
 <title>iTunesConnect info</title>
 <link href="css/itc.css?r=3" rel="stylesheet" type="text/css" media="screen" />
</head>


<body>
<div class="caption">
 <h1>iTunesConnect stats</h1>
 <table cellpadding='0' cellspacing='0' border='0'>
  <tr>
    <td>Last updated:</td>
    <td class='dt_val'><?php
   $date = file_get_contents("meta/last_update_date");
   echo($date?date("d-M-Y H:i", $date):"n/a");
 ?></td>
  </tr>
   <td>Sales last updated:</td>
   <td class='dt_val'><?php
   $date = file_get_contents("meta/sales_last_update_date");
   echo($date?date("d-M-Y H:i", $date):"n/a");
 ?></td>
  </tr>
 </table>
 <div id='sort_frame'>
 <form method='post'>
<?php
  $options = array("default","name","release date", "downloads");
  $sort = isset($_POST["sort"])?$_POST["sort"]:"default";
?>
   Sort by:
   <select name='sort' onchange='this.form.submit();'>
<?php
   foreach($options as $o) 
    echo("<option ".($o==$sort?"selected":"")." value='$o'>$o</option>");
?>
   </select>
  </form>
 </div>
</div>
<div class='apps'>
<?php
  $dir = scandir("meta");
  if (!$dir)
    echo("No apss were found.");
  else {
    $cnt = 0;
    $apps = array();
    $sort_ar = array();
    foreach($dir as $f) {	
      if (substr($f,0,4) != "app_") continue;
      $app = file_get_contents("meta/$f/appmeta.dat");
      if (!$app) continue;
      $app = unserialize($app);
      if (!$app) continue;
      switch($sort) {
        case "name": $sort_ar[$app["apple_id"]] = $app["app_name"];break;
        case "downloads": $sort_ar[$app["apple_id"]] = $app["stat_whole_period"];break;
        case "release date": $sort_ar[$app["apple_id"]] = $app["current_version"]["date_released"];break;
        case "default": $sort_ar[$app["apple_id"]] = $app["apple_id"];break;
      }
      $apps[$app["apple_id"]] = $app;
    }
    if ($sort!='default') 
      $r=asort($sort_ar);
    foreach($sort_ar as $app_id=>$val) {
      $rowcl = ($cnt++%2==0)?"even":"odd";
      showApp($apps[$app_id], $rowcl);
    }
  }
?>
</div>
</body>
</html>