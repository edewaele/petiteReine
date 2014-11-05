<?php
require 'conf.php';

try {
	$PDO = new PDO( 'pgsql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASSWORD );
} catch ( Exception $e ) {
	die("Unable to connect database");
}

// The following Overpass API query gets all nodes and ways within a zone, with tag "amenity=bicycle_parking"
$postdata = http_build_query(array('data' => '<osm-script >
  <union>
	<query type="node">
		<has-kv k="amenity" v="bicycle_parking" />
		<area-query ref="'.(3600000000+OSM_ZONE).'"/>
	</query>
	<query type="way">
		<has-kv k="amenity" v="bicycle_parking" />
		<area-query ref="'.(3600000000+OSM_ZONE).'"/>
	</query>
   </union>
  <union>
    <item/>
    <recurse type="down"/>
  </union>
  <print mode="meta"/>
</osm-script>
'));
$opts = array('http' =>
array(
'method' => 'POST',
'header' => 'Content-type: application/x-www-form-urlencoded',
'content' => $postdata
)
);
$context = stream_context_create($opts);
//$result = file_get_contents(OAPI_URL, false, $context);
$result = file_get_contents("http://overpass.osm.rambler.ru/cgi/interpreter", false, $context);


if($result)
	{

	//Database::getConn()->Execute("TRUNCATE TABLE pv_parkings");	
	$PDO->exec("TRUNCATE TABLE pv_parkings");	

	$dom = new DomDocument();
	$dom->loadXML($result);
	
	// The node are iterated first
	$resultsList = $dom->getElementsByTagName('node');

	$pointCoord = array();
	for($numNode = 0; $numNode < $resultsList->length; $numNode++)
	{
		$place = $resultsList->item($numNode);
		$isAParking = false;
		
		$pointCoord[$place->getAttribute('id')] = array('x'=> $place->getAttribute('lon'),'y'=> $place->getAttribute('lat'));
		
		$parkingAttr = array('obj_id'=>'n'.$place->getAttribute('id'),'capacity'=>0,'covered'=>'','bicycle_parking'=>'','timestamp'=>$place->getAttribute('timestamp'),'access'=>'');
		
		// retrieving the attributes of the object
		$tagList = $place->getElementsByTagName('tag');
		for($numTag = 0; $numTag < $tagList->length; $numTag++)
		{
			// the tags are copied only they were defined previously in the array
			if(isset($parkingAttr[$tagList->item($numTag)->getAttribute("k")]))
			{
				$parkingAttr[$tagList->item($numTag)->getAttribute("k")] = $tagList->item($numTag)->getAttribute("v");
			}
			if($tagList->item($numTag)->getAttribute("k") == "amenity" and $tagList->item($numTag)->getAttribute("v") == "bicycle_parking")
			{
				$isAParking = true;
			}
		}
		
		$parkingAttr["geom"] = "POINT (".$place->getAttribute('lon')." ".$place->getAttribute('lat').")";
		
		//if(isset($parkingAttr["amenity"]) && $parkingAttr["amenity"] == "bicycle_parking")
		if($isAParking)
		{
			//$rs = Database::getConn()->Execute("INSERT INTO pv_parkings (obj_id,capacity,covered,parking_type,access,the_geom) VALUES ('".$parkingAttr["obj_id"]."',".$parkingAttr["capacity"].",'".$parkingAttr["covered"]."','".$parkingAttr["bicycle_parking"]."','".$parkingAttr["access"]."',st_geomfromtext('POINT (".$parkingAttr["x"]." ".$parkingAttr["y"].")',4326))");
			//$rs = Database::getConn()->Execute("INSERT INTO pv_parkings (obj_id,capacity,covered,parking_type,access,the_geom) VALUES (:obj_id,:capacity,:covered,:bicycle_parking,:access,st_geomfromtext('POINT (:x :y)',4326))",$parkingAttr);
			//$geomString = "POINT ({$parkingAttr["x"]} {$parkingAttr["y"]})";
			//$rs = Database::getConn()->Execute("INSERT INTO pv_parkings (obj_id,capacity,covered,parking_type,access,the_geom) VALUES (:,?,?,?,?,st_geomfromtext(?,4326))",array($parkingAttr["obj_id"],$parkingAttr["capacity"],$parkingAttr["covered"],$parkingAttr["bicycle_parking"],$parkingAttr["access"],$geomString));
			$insertNodeParking = $PDO->prepare("INSERT INTO pv_parkings (obj_id,capacity,covered,parking_type,access,the_geom,timestamp) VALUES (:obj_id,:capacity,:covered,:bicycle_parking,:access,st_geomfromtext(:geom,4326),:timestamp)");
			$insertNodeParking->execute($parkingAttr);
		}
	}


	$resultsList = $dom->getElementsByTagName('way');
	for($numWay = 0; $numWay < $resultsList->length; $numWay++)
	{
		$place = $resultsList->item($numWay);
		
		
		$parkingAttr = array('obj_id'=>'w'.$place->getAttribute('id'),'timestamp'=>$place->getAttribute('timestamp'),'capacity'=>0,'covered'=>'','bicycle_parking'=>'','access'=>'');
		
		$tagList = $place->getElementsByTagName('tag');
		// retrieving the attributes of the object
		for($numTag = 0; $numTag < $tagList->length; $numTag++)
		{
			// the tags are copied only they were defined previously in the array
			if(isset($parkingAttr[$tagList->item($numTag)->getAttribute("k")]))
			{
				$parkingAttr[$tagList->item($numTag)->getAttribute("k")] = $tagList->item($numTag)->getAttribute("v");
			}
			if($tagList->item($numTag)->getAttribute("k") == "amenity" and $tagList->item($numTag)->getAttribute("v") == "bicycle_parking")
			{
				$isAParking = true;
			}
		}
		
		
		$xMin = 0;$xMax = 0; $yMin = 0;$yMax = 0; 
		$nodeRefList = $place->getElementsByTagName('nd');
		for($numNode = 0; $numNode < $nodeRefList->length; $numNode++)
		{
			$nodeID = $nodeRefList->item($numNode)->getAttribute("ref");
			if($numNode == 0)
			{
				$xMin = $pointCoord[$nodeID]["x"];
				$xMax = $pointCoord[$nodeID]["x"];
				$yMin = $pointCoord[$nodeID]["y"];
				$yMax = $pointCoord[$nodeID]["y"];
			}
			else
			{
				$xMin = min($xMin,$pointCoord[$nodeID]["x"]);
				$xMax = max($xMax,$pointCoord[$nodeID]["x"]);
				$yMin = min($yMin,$pointCoord[$nodeID]["y"]);
				$yMax = max($yMax,$pointCoord[$nodeID]["y"]);
			}
		}
		
		
		//$parkingAttr["x"] = ($xMin+$xMax)/2;
		//$parkingAttr["y"] = ($yMin+$yMax)/2;
		
		
		$parkingAttr["geom"] = "POINT (".(($xMin+$xMax)/2)." ".(($yMin+$yMax)/2).")";
		//$parkingAttr["geom"] = "POINT ({$parkingAttr["x"]} {$parkingAttr["y"]})";
		
		
		//if(isset($parkingAttr["amenity"]) && $parkingAttr["amenity"] == "bicycle_parking")
		if($isAParking)
		{
			//$rs = Database::getConn()->Execute("INSERT INTO pv_parkings (obj_id,capacity,covered,parking_type,access,the_geom) VALUES ('".$parkingAttr["obj_id"]."',".$parkingAttr["capacity"].",'".$parkingAttr["covered"]."','".$parkingAttr["bicycle_parking"]."','".$parkingAttr["access"]."',st_geomfromtext('POINT (".$parkingAttr["x"]." ".$parkingAttr["y"].")',4326))");
			//$geomString = "POINT ({$parkingAttr["x"]} {$parkingAttr["y"]})";
			//$rs = Database::getConn()->Execute("INSERT INTO pv_parkings (obj_id,capacity,covered,parking_type,access,the_geom) VALUES (?,?,?,?,?,st_geomfromtext(?,4326))",array($parkingAttr["obj_id"],$parkingAttr["capacity"],$parkingAttr["covered"],$parkingAttr["bicycle_parking"],$parkingAttr["access"],$geomString));
			$insertWayParking = $PDO->prepare("INSERT INTO pv_parkings (obj_id,capacity,covered,parking_type,access,the_geom,timestamp) VALUES (:obj_id,:capacity,:covered,:bicycle_parking,:access,st_geomfromtext(:geom,4326),:timestamp)");
			$insertWayParking->execute($parkingAttr);
		}
		//echo print_r($parkingAttr)."<br>";
	}
	
	$PDO->exec("update pv_parkings set zone_id = rel.zone_id
	from (select Z.zone_id,obj_id from pv_zones Z,pv_parkings where st_contains(geom,the_geom)) as rel
	where pv_parkings.obj_id = rel.obj_id");

	// All informal parking are deleted from the database
	$PDO->exec("DELETE FROM pv_parkings WHERE parking_type = 'informal'");

		
	$PDO->exec("TRUNCATE TABLE pv_parking_dist_zones");	

	
	$sqlDistZones = "insert into pv_parking_dist_zones
		select zone_id,:dist,st_difference(the_geom,(select st_union(geom) from pv_parking_dist_zones))  from (
		select zone_id,ST_Multi(st_intersection((Z.geom),(select st_transform(st_union(st_buffer(st_transform(the_geom,:proj),:dist2)),4326) from pv_parkings))) as the_geom
		from pv_zones Z)
		sr where st_geometrytype(the_geom) = 'ST_MultiPolygon'";


	$firstTime = true;
	foreach($DISTANCE_LEVELS as $distElt)
	{
		echo $distElt['dist']."<br>";
		if($distElt['dist'] != 'beyond')
		{
			
			if($firstTime)
				$areaSelector = "the_geom";
			else
				$areaSelector = "st_difference(the_geom,(select st_union(geom) from pv_parking_dist_zones))";
			$sqlDistZones = "insert into pv_parking_dist_zones
				select zone_id,:dist,".$areaSelector."  from (
				select zone_id,ST_Multi(st_intersection((Z.geom),(select st_transform(st_union(st_buffer(st_transform(the_geom,:proj),:dist2)),4326) from pv_parkings where access <> 'private'))) as the_geom
				from pv_zones Z	)
				sr where st_geometrytype(the_geom) = 'ST_MultiPolygon'";
		
			$queryDistZones = $PDO->prepare($sqlDistZones);
			$queryDistZones->bindValue(':proj', (int)DIST_PROJ);
			$queryDistZones->bindValue(':dist', $distElt['dist']);
			$queryDistZones->bindValue(':dist2', $distElt['dist']);
			$queryDistZones->execute();
			$firstTime = true;
		}
	}


}


?>