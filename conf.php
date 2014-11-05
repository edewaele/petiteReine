<?php
require_once 'lib/mobileDetect/Mobile_Detect.php';

// Database connection parameters
define('DB_HOST','localhost');		// host name
define('DB_USER','emmanuel');		// DB user
define('DB_PASSWORD','emmanuel');	// user password
define('DB_NAME','emmanuel');		// database name

// URL of the Overpass API instance (see http://wiki.openstreetmap.org/wiki/Overpass_API)
define('OAPI_URL',"http://overpass-api.de/api/interpreter");

// Here are set the labels for each type of parking (OSM key : bicycle_parking), as displayed is popups
$PARKING_LABEL = array(
	'stands'=> 'Arceaux',
	'shed' => 'Abri à vélos',
	'wall_loops'=>'Pince-roues',
	'empty' => 'Type inconnu',
	'other' => 'Autre type'
);

// In the popup, the label saying whether the parking is covered or not (OSM key : covered)
$COVERED_LABEL = array(
	'yes'=> 'couvert',
	'no' => 'non couvert'
);

// In the popup, the label saying whether the parking is covered or not (OSM key : covered)
$ACCESS_LABEL = array(
	'private'=> '(privé)',
	'customers'=> '(réservé à la clientèle)',
	'other' => '',
	'empty' => ''
);

// decimal separator, must be set according to the locale
$DEC_POINT = ",";
// thousands separator, must be set according to the locale
$THOUSAND_SEP = " ";

// the data is extracted from a given zone, which is defined by an OpenStreetMap relation
// you have to the relation id in OSM
//define('OSM_ZONE',76306);
define('OSM_ZONE',1663056);

/* Distances and areas are calculated by transforming lon/lat coordinates into a projection 
	so that 1 unit in the projection is roughly equivalent to 1 meter.
 Of course, the projection should be fitting for the zone.
 For instance, in the area near Tours, France the projection is Lambert CC47 (EPSG 27572)
 (see https://en.wikipedia.org/wiki/Lambert_conformal_conic_projection)
 */
define('DIST_PROJ',27572);

// 
$DISTANCE_LEVELS = array(
	array('dist'=>100,'colour'=>'green'),
	array('dist'=>200,'colour'=>'yellow'),
	array('dist'=>400,'colour'=>'orange'),
	array('dist'=>'beyond','colour'=>'red')
);


$detect = new Mobile_Detect;
//
$CLIENT_CONF = array(
	'labels'=>array(
		'parkingsLayer'=>'Parkings',
		'badObjLayer'=>'Données à corriger',
		'surroundingAreaLayer'=>'Couverture en parking vélo',
		'privateLayer'=>'Parkings privés',
		'boundariesLayer'=>"Limites communales"
	),
	'popupTimeout'=>2000,
	'maxClusterRadius'=>70,
	'disableClusteringAtZoom'=>18,
	'reservedHeight'=>150, // max height for an accordion block = map height - reservedHeight
	'reservedHeightMobile'=>100, // max height for an accordion block = map height - reservedHeight (on a mobile device)
	'isMobile'=>$detect->isMobile()
);

?>