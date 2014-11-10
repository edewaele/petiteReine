<?php 

$LABELS = array(
	'pageTitle'=>'Parkings vélo',
	// left panel labels
	'panel.stats'=>'Statistiques',
	'panel.stats.byZone'=>'Analyse par zone',
	'panel.stats.drawRectangle'=>'Rectangle',
	'panel.stats.drawPolygon'=>'Polygone',
	'panel.stats.clear'=>'Effacer',
	'panel.zones'=>'Communes',
	'panel.help'=>'Légende / Aide',
	// "About" button and dialog header
	'aboutDialog.button'=>'À propos',
	'aboutDialog.title'=>'À propos de cette carte',
	// map layers
	'map.layerLabels'=>array(
		'parkingsLayer'=>'Parkings',
		'badObjLayer'=>'Données à corriger',
		'surroundingAreaLayer'=>'Couverture en parking vélo',
		'privateLayer'=>'Parkings privés',
		'boundariesLayer'=>"Limites communales"
	),
	// When the user clicks the map background, the distance to the nearest parkign is displayed
	'map.distanceToolLabel'=>'%s mètres du parking le plus proche (à vol d\'oiseau)',
	// Parking popup, information about the capacity
	'map.parking.noCapacity'=>'nombre de places inconnu',
	'map.parking.capacity'=>'%d places',
	'map.parking.type'=>array(
		'stands'=> 'Arceaux',
		'shed' => 'Abri à vélos',
		'wall_loops'=>'Pince-roues',
		'empty' => 'Type inconnu',
		'other' => 'Autre type'
	),
	'map.parking.coveredLabel'=>array(
		'yes'=> 'couvert',
		'no' => 'non couvert'
	),
	'map.parking.accessLabel'=>array(
		'private'=> '(privé)',
		'customers'=> '(réservé à la clientèle ou usagers)',
		'other' => '',
		'empty' => ''
	),
	// Bad objects layer, error reasons
	'map.bad.noType'=>"Type de parking inconnu. ", // no bicycle_parking key
	'map.bad.noCapacity'=>"Nombre de places inconnu. ",// capacity is unknown
	'map.bad.doubloon'=>"Autre parking au même endroit. ",// geographical doubloon
	'map.bad.edit'=>"Modifier avec JOSM",
	// statistics tool
	'stats.noData'=> "Aucune donnée",
	'stats.area'=>"Surface : %s km²<br>",
	'stats.total'=>"%s places (%s parkings) dont :",
	'stats.byType'=>array(
		"stands"=>"%s places en arceaux (%s)",
		"wall_loops"=>"%s places en pince-roue (%s)",
		"shed"=>"%s places en abri (%s)",
		"other"=>"%s places sur autres types de parking (%s)",
		"unknown"=>"%s places sur parkings de type inconnu (%s)"
	)
);

?>