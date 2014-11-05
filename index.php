<?php 
require 'conf.php';
?>
<html>
<head>
<title>Parkings vélo</title>
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

<input type="button" value="À propos" id="aboutButton">

<div id="leftPanel">
	<h3>Statistiques</h3>
	<div>
		<!--<h1>Statistiques</h1>-->
		<div id="stats_global"></div>
		<div id="zone_analysis">
			<h4>Analyse par zone</h4>
			<div id="stats_zone"></div>
			<p><input type="button" id="drawRect" value="Tracer rectangle">
			<input type="button" id="drawPolygon" value="Polygone">
			<input type="button" id="eraseZone" value="Effacer"></P>
		</div>
	</div>
	<h3>Communes</h3>
	<div>
		<input type="button" id="zonesAll" value="Tout" />
		<input type="button" id="zonesNone" value="Rien" />
		<input type="button" id="zonesApply" value="Appliquer" />
		<div id="zoneList"></div>
	</div>
	<h3>Légende / aide</h3>
	<div id="help">
	<?php include("locale/fr-fr/help.html"); ?>
	</div>
</div>

<div id="aboutDialog" title="À propos de cette carte">
<?php include("locale/fr-fr/about.html"); ?>
</div>
</body>
</html>