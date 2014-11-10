<?php 

$LABELS = array(
	'pageTitle'=>'Bicycle parkings',
	// left panel labels
	'panel.stats'=>'Statistics',
	'panel.stats.byZone'=>'Zone analysis',
	'panel.stats.drawRectangle'=>'Rectangle',
	'panel.stats.drawPolygon'=>'Polygon',
	'panel.stats.clear'=>'Clear',
	'panel.zones'=>'Districts',
	'panel.help'=>'Caption / Help',
	// "About" button and dialog header
	'aboutDialog.button'=>'About',
	'aboutDialog.title'=>'About this map',
	// map layers
	'map.layerLabels'=>array(
		'parkingsLayer'=>'Parkings',
		'badObjLayer'=>'Missing information',
		'surroundingAreaLayer'=>'Parking coverage',
		'privateLayer'=>'Privates parkings',
		'boundariesLayer'=>"Local boundaries"
	),
	// When the user clicks the map background, the distance to the nearest parkign is displayed
	'map.distanceToolLabel'=>'%s metres from the nearest parking (flight distance)',
	// Parking popup, information about the capacity
	'map.parking.noCapacity'=>'unknown capacity',
	'map.parking.capacity'=>'%d spaces',
	'map.parking.type'=>array(
		'stands'=> 'Stands',
		'shed' => 'Shed',
		'wall_loops'=>'Wall loops',
		'empty' => 'Unknown type',
		'other' => 'Other type'
	),
	'map.parking.coveredLabel'=>array(
		'yes'=> 'covered',
		'no' => 'not covered'
	),
	'map.parking.accessLabel'=>array(
		'private'=> '(private)',
		'customers'=> '(customers or users only)',
		'other' => '',
		'empty' => ''
	),
	// Bad objects layer, error reasons
	'map.bad.noType'=>"Unknown parking type. ", // no bicycle_parking key
	'map.bad.noCapacity'=>"Unknown capacity. ",// capacity is unknown
	'map.bad.doubloon'=>"Another parking exists at the same place. ",// geographical doubloon
	'map.bad.edit'=>"Edit with JOSM",
	// statistics tool
	'stats.noData'=> "No data",
	'stats.area'=>"Area : %s km²<br>",
	'stats.total'=>"%s spaces (%s parkings) including :",
	'stats.byType'=>array(
		"stands"=>"%s spaces on stands (%s)",
		"wall_loops"=>"%s spaces on wall loops (%s)",
		"shed"=>"%s stapces in sheds (%s)",
		"other"=>"%s spaces in other parking typess (%s)",
		"unknown"=>"%s spaces in parkings of unknown type (%s)"
	)
);

?>