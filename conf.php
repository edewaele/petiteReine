<?php
require_once 'lib/mobileDetect/Mobile_Detect.php';
require_once 'functions.php';



// In this file, we are going to configure the app step by step

/**
	STEP 1 : A POSTGIS DATABASE
*/
// Database connection parameters
define('DB_HOST','localhost');		// host name
define('DB_USER','emmanuel');		// DB user
define('DB_PASSWORD','emmanuel');	// user password
define('DB_NAME','emmanuel');		// database name

/**
	STEP 2 : THE AREA TO IMPORT
*/
// the data is extracted from a given zone, which is defined by an OpenStreetMap relation
// you have to set the relation id in OSM
//define('OSM_ZONE',76306);
define('OSM_ZONE',1663056);

/* Distances and areas are calculated by transforming lon/lat coordinates into a projection 
	so that 1 unit in the projection is roughly equivalent to 1 meter.
 Of course, the projection should be fitting for the zone.
 For instance, in the area near Tours, France the projection is Lambert CC47 (EPSG 27572)
 (see https://en.wikipedia.org/wiki/Lambert_conformal_conic_projection)
 */
define('DIST_PROJ',27572);

/*
	STEP 3 : LOCALE SETTINGS
*/
$LOCALES = array(
	'fr'=>'fr-fr',
	'default'=>'default'// the defaut locale is used of the language codes above is recognised (english labels).
);
$CURRENT_LOCALE = getLocale($LOCALES);
require_once 'locale/'.$CURRENT_LOCALE.'/labels.php';

/**
	STEP 4 : ADVANCED SETTINGS
*/
// Activate the zone filter
// true => you must fill the pv_zones tables, and the zone selector will display its contents
// false => the feature is disabled, and the pv_zones tables will be flushed the next time import.php is launched
define('MODE_ZONE_FILTER',false);

// URL of the Overpass API instance (see http://wiki.openstreetmap.org/wiki/Overpass_API)
// Overpass API provides the list of bicycle parkings
define('OAPI_URL',"http://overpass-api.de/api/interpreter");

// Here are set the labels for each type of parking (OSM key : bicycle_parking), as displayed is popups
$PARKING_LABEL = $LABELS['map.parking.type'];

// In the popup, the label saying whether the parking is covered or not (OSM key : covered)
$COVERED_LABEL = $LABELS['map.parking.coveredLabel'];

// In the popup, the label saying whether the parking is open to everyone or categories of people (OSM key : access)
$ACCESS_LABEL = $LABELS['map.parking.accessLabel'];

// decimal separator, must be set according to the locale
$DEC_POINT = ",";
// thousands separator, must be set according to the locale
$THOUSAND_SEP = " ";


// Isochrone generation parameters
// each element gives the fill colour of the area within a given distance to the closest parking
// the list must be ordered in ascending order
$DISTANCE_LEVELS = array(
	array('dist'=>100,'colour'=>'green'),
	array('dist'=>200,'colour'=>'yellow'),
	array('dist'=>400,'colour'=>'orange')
);

$detect = new Mobile_Detect;
//
$CLIENT_CONF = array(
	'labels'=>$LABELS["map.layerLabels"],
	'popupTimeout'=>2000,
	'maxClusterRadius'=>70,
	'disableClusteringAtZoom'=>18,
	'reservedHeight'=>150, // max height for an accordion block = map height - reservedHeight
	'reservedHeightMobile'=>100, // max height for an accordion block = map height - reservedHeight (on a mobile device)
	'isMobile'=>$detect->isMobile(),
	'zoneFilter'=>MODE_ZONE_FILTER
);

?>