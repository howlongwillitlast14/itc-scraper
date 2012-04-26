<?php 
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

	<title>iTunesConnect info</title>

	<!-- style START -->
	<!-- default style -->
        <link href="css/style.css" rel="stylesheet" type="text/css" media="screen" />
        <link href="css/itc.css" rel="stylesheet" type="text/css" media="screen" />
</head>


<body>
<div class="caption">
 <h1>iTunesConnect stats</h1>
 <span>Last updated: <strong><?php
   $date = file_get_contents("meta/last_update_date");
   echo($date?date("d-M-Y h:i", $date):"n/a");
 ?></strong></span>
</div>
<div class='apps'>
<?php
  $dir = scandir("meta");
  if (!$dir)
    echo("No apss were found.");
  else {
    $cnt = 0;
    foreach($dir as $f) {	
      if (substr($f,0,4) != "app_") continue;
      $app = file_get_contents("meta/$f/appmeta.dat");
      if (!$app) continue;
      $app = unserialize($app);
      if (!$app) continue;
      //echo($app["app_name"]."<br/>");
      $rowcl = ($cnt++%2==0)?"even":"odd";
      showApp($app, $rowcl);
    }
  }
?>
</div>
</body>
</html>