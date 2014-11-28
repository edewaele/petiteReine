<?php
require 'conf.php';
/**
	PARKINGS IMPORT BATCH
	
	 * Import bicycle parking within an area 
	 * Calculate pseudo-isochrones
*/

set_time_limit(300);

try {
	$PDO = new PDO( 'pgsql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASSWORD );
} catch ( Exception $e ) {
	die("Unable to connect database");
}

// The moment when te process starts
$startTime = time();
// Whether the batch retrieves data
$dataExists = false;

// The following Overpass API query gets all nodes and ways within an area, with tag "amenity=bicycle_parking"
$areaCriterion = "";
if(IMPORT_FROM_RELATION)
{
	$areaCriterion = '<area-query ref="'.(3600000000+OSM_ZONE).'"/>';
}
else
{
	$areaCriterion = '<bbox-query s="'.BBOX_SOUTH.'" n="'.BBOX_NORTH.'" w="'.BBOX_WEST.'" e="'.BBOX_EAST.'"/>';
}
$postdata = http_build_query(array('data' => '<osm-script >
  <union>
	<query type="node">
		<has-kv k="amenity" v="bicycle_parking" />
		'.$areaCriterion.'
	</query>
	<query type="way">
		<has-kv k="amenity" v="bicycle_parking" />
		'.$areaCriterion.'
	</query>
   </union>
  <union>
    <item/>
    <recurse type="down"/>
  </union>
  <print mode="meta"/>
</osm-script>
'));
echo '<osm-script >
  <union>
	<query type="node">
		<has-kv k="amenity" v="bicycle_parking" />
		$areaCriterion
	</query>
	<query type="way">
		<has-kv k="amenity" v="bicycle_parking" />
		$areaCriterion
	</query>
   </union>
  <union>
    <item/>
    <recurse type="down"/>
  </union>
  <print mode="meta"/>
</osm-script>';
$opts = array('http' =>
array(
'method' => 'POST',
'header' => 'Content-type: application/x-www-form-urlencoded',
'content' => $postdata
)
);
$context = stream_context_create($opts);
$result = file_get_contents(OAPI_URL, false, $context);


if($result)
	{

	/**
		STEP 1 : IMPORT PARKINS FROM OVERPASS API
	*/
	echo $PDO->beginTransaction();	
	$PDO->exec("DELETE FROM pv_parkings");	

	$dom = new DomDocument();
	$dom->loadXML($result);
	
	// The nodes are iterated first
	$resultsList = $dom->getElementsByTagName('node');

	$pointCoord = array();
	for($numNode = 0; $numNode < $resultsList->length; $numNode++)
	{
		$place = $resultsList->item($numNode);
		$isAParking = false;
		
		$pointCoord[$place->getAttribute('id')] = array('x'=> $place->getAttribute('lon'),'y'=> $place->getAttribute('lat'));
		
		$parkingAttr = array('obj_id'=>'n'.$place->getAttribute('id'),'capacity'=>0,'covered'=>'','bicycle_parking'=>'','timestamp'=>$place->getAttribute('timestamp'),'access'=>'','operator'=>'');
		
		// retrieving the attributes of the object
		$tagList = $place->getElementsByTagName('tag');
		for($numTag = 0; $numTag < $tagList->length; $numTag++)
		{
			// the tags are copied only they were defined previously in the array
			if(isset($parkingAttr[$tagList->item($numTag)->getAttribute("k")]))
			{
				$parkingAttr[$tagList->item($numTag)->getAttribute("k")] = $tagList->item($numTag)->getAttribute("v");
			}
			// a node is bicycle parking it contains tag amenity = bicycle_parking
			if($tagList->item($numTag)->getAttribute("k") == "amenity" and $tagList->item($numTag)->getAttribute("v") == "bicycle_parking")
			{
				$isAParking = true;
			}
		}
		
		$parkingAttr["geom"] = "POINT (".$place->getAttribute('lon')." ".$place->getAttribute('lat').")";
		
		if($isAParking)
		{
			$insertNodeParking = $PDO->prepare("INSERT INTO pv_parkings (obj_id,capacity,covered,parking_type,access,the_geom,timestamp,operator) VALUES (:obj_id,:capacity,:covered,:bicycle_parking,:access,st_geomfromtext(:geom,4326),:timestamp,:operator)");
			if( ! $insertNodeParking->execute($parkingAttr)){echo print_r($PDO->errorInfo())."<br>";};
			$dataExists = true;
		}
	}

	// then, we fetch the ways, bicycle parkings may be described as polygons
	$resultsList = $dom->getElementsByTagName('way');
	for($numWay = 0; $numWay < $resultsList->length; $numWay++)
	{
		$place = $resultsList->item($numWay);
		
		
		$parkingAttr = array('obj_id'=>'w'.$place->getAttribute('id'),'timestamp'=>$place->getAttribute('timestamp'),'capacity'=>0,'covered'=>'','bicycle_parking'=>'','access'=>'','operator'=>'');
		
		$tagList = $place->getElementsByTagName('tag');
		// retrieving the attributes of the object
		for($numTag = 0; $numTag < $tagList->length; $numTag++)
		{
			// the tags are copied only they were defined previously in the array
			if(isset($parkingAttr[$tagList->item($numTag)->getAttribute("k")]))
			{
				$parkingAttr[$tagList->item($numTag)->getAttribute("k")] = $tagList->item($numTag)->getAttribute("v");
			}
			// a node is bicycle parking it contains tag amenity = bicycle_parking
			if($tagList->item($numTag)->getAttribute("k") == "amenity" and $tagList->item($numTag)->getAttribute("v") == "bicycle_parking")
			{
				$isAParking = true;
			}
		}
		
		// bounding box calculation, based on the nodes that make up the polygon
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
		
		// the parking in is located on the centre of the bounding box
		$parkingAttr["geom"] = "POINT (".(($xMin+$xMax)/2)." ".(($yMin+$yMax)/2).")";
		
		
		if($isAParking)
		{
			$insertWayParking = $PDO->prepare("INSERT INTO pv_parkings (obj_id,capacity,covered,parking_type,access,the_geom,timestamp,operator) VALUES (:obj_id,:capacity,:covered,:bicycle_parking,:access,st_geomfromtext(:geom,4326),:timestamp,:operator)");
			if( ! $insertNodeParking->execute($parkingAttr)){echo print_r($PDO->errorInfo())."<br>";};
			$dataExists = true;
		}
	}
	
	// If data was inserted, the changes are committed
	if($dataExists)
	{
		// If the zone filter is deactivated the pv_zones table should contain one geometry that contains the whole world
		if(! MODE_ZONE_FILTER)
		{
			$PDO->exec("TRUNCATE TABLE pv_zones");	
			$PDO->exec("insert into pv_zones values ('default','Default',0,1,1,st_multi(st_expand(ST_GeomFromText('POINT(0 0)',4326),90)))");	
			$PDO->exec("update pv_parkings set zone_id = 'default'");
		}
		else
		{
			// The zone_id of each parking(table pv_parkings) is calculated, according to the geometries in pv_zones
			$PDO->exec("update pv_parkings set zone_id = rel.zone_id
			from (select Z.zone_id,obj_id from pv_zones Z,pv_parkings where st_contains(geom,the_geom)) as rel
			where pv_parkings.obj_id = rel.obj_id");
		}
		
		// All informal parking are deleted from the database
		$PDO->exec("DELETE FROM pv_parkings WHERE parking_type = 'informal'");
		
		$PDO->commit();
	}
	else
	{
		$PDO->rollback();
	}
		

	/**
		STEP 2 : ISOCHRONE AREAS GENERATION
	*/	
	// This step is run if data was retrieved
	if($dataExists)
	{
		$PDO->beginTransaction();	
		$PDO->exec("TRUNCATE TABLE pv_parking_dist_zones");	
		
		$firstTime = true;
		// Polygons are computed given several distance thresholds
		foreach($DISTANCE_LEVELS as $distElt)
		{
			if($firstTime)
				// on the first time, we calculate the area around the parking, with the first threshold
				$areaSelector = "the_geom";
			else
				// then, the geometries must not overlap with the existing geometries (lesser threshold)
				$areaSelector = "st_difference(the_geom,(select st_union(geom) from pv_parking_dist_zones))";
			
			// Polygons are generated given the parkings, a distance threshold, and the local boundaires (pv_zones.geom)
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
		
		$PDO->commit();
	}
	
	// Insert a new row in the trace table
	$traceAttr = array(":status"=>'false',
		":startdt"=>date('Y-m-d h:i:s'),
		":duration"=>(time()-$startTime),
		":area"=>OSM_ZONE,
		":number_parking"=>0,":total_capacity"=>0,":number_nocapacity"=>0,":number_notype"=>0,":number_problem"=>0);
	if($dataExists)
	{
		$traceAttr[":status"] = 'true';
		// number of parking and sum of capacities
		$queryNumber = $PDO->query("select count(*) as n,sum(capacity) as s from pv_parkings");
		if($rs = $queryNumber->fetch(PDO::FETCH_ASSOC))
		{
			$traceAttr[":number_parking"] = $rs["n"];
			$traceAttr[":total_capacity"] = $rs["s"];
		}
		// number of parking without capacity
		$queryNoCapacity = $PDO->query("select count(*) as n from pv_parkings where capacity = 0");
		if($rs = $queryNoCapacity->fetch(PDO::FETCH_ASSOC))
		{
			$traceAttr[":number_nocapacity"] = $rs["n"];
		}
		// number of parking without parking type
		$queryNoType = $PDO->query("select count(*) as n from pv_parkings where parking_type = ''");
		if($rs = $queryNoType->fetch(PDO::FETCH_ASSOC))
		{
			$traceAttr[":number_notype"] = $rs["n"];
		}
		// number of parking without parking type or capacity
		$queryProblem = $PDO->query("select count(*) as n from pv_parkings where parking_type = '' or capacity = 0");
		if($rs = $queryProblem->fetch(PDO::FETCH_ASSOC))
		{
			$traceAttr[":number_problem"] = $rs["n"];
		}
	}
	$sqlInsertTrace = "insert into pv_import_trace 
	(status,startdt,duration,area,number_parking,total_capacity,number_nocapacity,number_notype,number_problem) values
	(:status,:startdt,:duration,:area,:number_parking,:total_capacity,:number_nocapacity,:number_notype,:number_problem)";
	$queryInsertTrace = $PDO->prepare($sqlInsertTrace);
	$queryInsertTrace->execute($traceAttr);
}


?>