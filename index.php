<?php 
require 'conf.php';
?>
<html>
<head>
<title><?php echo $LABELS["pageTitle"];?></title>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />


<link rel="stylesheet" href="http://cdn.leafletjs.com/leaflet-0.7.3/leaflet.css" />
<script src="http://cdn.leafletjs.com/leaflet-0.7.3/leaflet.js"></script>
<script src="http://code.jquery.com/jquery-1.11.0.min.js"></script>

<link href='css/style.css' rel='stylesheet' type='text/css'>

<?php
if( $detect->isMobile()){?>
<link href='css/mobile.css' rel='stylesheet' type='text/css'>
<?php }?>

<link rel="stylesheet" href="lib/leaflet/MarkerCluster.css" />
<link rel="stylesheet" href="lib/leaflet/MarkerCluster.Default.css" />
<script src="lib/leaflet/leaflet.markercluster.js"></script>
<script src="lib/leaflet/leaflet-providers.js"></script>
<script src="lib/leaflet/leaflet.draw.js"></script>
<link rel="stylesheet" href="lib/leaflet/leaflet.draw.css" />
<script src="lib/jquery-ui/jquery-ui.min.js"></script>
<link rel="stylesheet" href="lib/jquery-ui/jquery-ui.min.css" />
<link rel="stylesheet" href="lib/jquery-ui/jquery-ui.structure.min.css" />
<link rel="stylesheet" href="lib/jquery-ui/jquery-ui.theme.min.css" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
<script>
// The client-side configuration is defined in conf.php
// Its contents are printed below

var CLIENT_CONF = <?php echo json_encode($CLIENT_CONF, JSON_NUMERIC_CHECK);?>;


</script>


<script src="js/ui.js"></script>
</head>
<body>
<div id="map"></div>

<input type="button" value="<?php echo $LABELS["aboutDialog.button"];?>" id="aboutButton">

<div id="leftPanel">
	<h3><?php echo $LABELS["panel.stats"];?></h3>
	<div>
		<!--<h1>Statistiques</h1>-->
		<div id="stats_global"></div>
		<div id="zone_analysis">
			<h4><?php echo $LABELS["panel.stats.byZone"];?></h4>
			<div id="stats_zone"></div>
			<p><input type="button" id="drawRect" value="<?php echo $LABELS["panel.stats.drawRectangle"];?>">
			<input type="button" id="drawPolygon" value="<?php echo $LABELS["panel.stats.drawPolygon"];?>">
			<input type="button" id="eraseZone" value="<?php echo $LABELS["panel.stats.clear"];?>"></P>
		</div>
	</div>
	<?php if(MODE_ZONE_FILTER){?>
	<h3><?php echo $LABELS["panel.zones"];?></h3>
	<div>
		<!--<input type="button" id="zonesAll" value="Tout" />
		<input type="button" id="zonesNone" value="Rien" />-->
		<input type="button" id="zonesApply" value="Appliquer" />
		<div id="zoneList"></div>
	</div>
	<?php }?>
	<h3><?php echo $LABELS["panel.help"];?></h3>
	<div id="help">
	<?php include("locale/".$CURRENT_LOCALE."/help.html"); ?>
	</div>
</div>

<div id="aboutDialog" title="<?php echo $LABELS["aboutDialog.title"];?>">
<?php include("locale/".$CURRENT_LOCALE."/about.html"); ?>
</div>

<iframe id="hiddenIframe" name="hiddenIframe" style="display:none"></iframe>
</body>
</html>