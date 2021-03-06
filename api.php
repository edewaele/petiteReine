<?php 
require 'conf.php';


try {
	$PDO = new PDO( 'pgsql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASSWORD );
} catch ( Exception $e ) {
	die("Unable to connect database");
}


function getZoneFilter($zoneParam = "")
{
	if(isset($zoneParam) and $zoneParam != "")
	{
		$result = " AND zone_id IN (";
		foreach(explode(",",$zoneParam) as $numParam => $zoneId)
		{
			if($numParam == 0)
			{
				$result .= "'$zoneId'";
			}
			else
			{
				$result .= ",'$zoneId'";
			}
		}
		return $result . ") ";
	}
	else
	{
		return " AND zone_id IN (SELECT zone_id FROM pv_zones WHERE visible_default = 1) ";
	}
}


function calculateStats($PDO,$LABELS,$filterClause,$geom = "")
{
	global $PARKING_LABEL;
	$stats = "";
	$parkingCats = array();
	foreach($LABELS['stats.byType'] as $key=>$value)
	{
		$parkingCats[$key] = 0;
	}
	$numberParkings = $parkingCats;
	$capacityParkings = $parkingCats;
	$numberParkingsTotal = 0;
	$capacityParkingsTotal = 0;
		
	if($geom != "")
	{
		// if a geometry is provided, the area within the polygon
		$sqlArea = "select st_area(st_transform(st_geomfromgeojson(:geojson),:proj)) as area";
		$queryArea = $PDO->prepare($sqlArea);
		$queryArea->execute(array(":proj"=>DIST_PROJ,":geojson"=>json_encode($_REQUEST["geom"]["geometry"])));
		if($rs = $queryArea->fetch())
		{
			$areaInSqrKm = $rs["area"] / 1000 / 1000;
			$stats .= sprintf($LABELS['stats.area'],number_format($areaInSqrKm,2,DEC_POINT,THOUSAND_SEP));
		}
	
		$sqlStats = "select parking_type,count(*) as n,sum(capacity) as c from pv_parkings where st_contains(st_geomfromgeojson(:geojson),the_geom) ".$filterClause.getZoneFilter($_REQUEST["zones"])." group by parking_type";
		$queryStats = $PDO->prepare($sqlStats);
		$queryStats->execute(array(":geojson"=>json_encode($_REQUEST["geom"]["geometry"])));
	}
	else
	{
		// otherwise, the global statistics are calculated
		$queryStats = $PDO->query("select parking_type,count(*) as n,sum(capacity) as c from pv_parkings where 1=1 ".$filterClause.getZoneFilter(isset($_REQUEST["zones"])?$_REQUEST["zones"]:"")." group by parking_type");
	}
	// counting parkings and total capacity by type
	while($rs = $queryStats->fetch())
	{
		if($rs["parking_type"] == "")
		{
			$numberParkings["unknown"] += $rs["n"];
			$capacityParkings["unknown"] += $rs["c"];
		}
		else if(isset($PARKING_LABEL[$rs["parking_type"]]))
		{
			$numberParkings[$rs["parking_type"]] += $rs["n"];
			$capacityParkings[$rs["parking_type"]] += $rs["c"];
		}
		else
		{
			$numberParkings["other"] += $rs["n"];
			$capacityParkings["other"] += $rs["c"];
		}	
		$numberParkingsTotal += $rs["n"];
		$capacityParkingsTotal += $rs["c"];
	}		
	
	if($numberParkingsTotal > 0)
	{
		// total number of parkings
		$stats .= sprintf($LABELS['stats.total'],$capacityParkingsTotal,$numberParkingsTotal);
		$stats .= "<ul>";
		// number of parkings (and capacity) by type
		foreach($LABELS['stats.byType'] as $type => $label)
		{
			if($numberParkings[$type])
			{
				$stats .= "<li>".sprintf($label,$capacityParkings[$type],$numberParkings[$type])."</li>";
			}
		}
		$stats .= "</ul>";
	}
	else
		$stats = $LABELS["stats.noData"];
		
	return $stats;
}

// Get a list of bicycle parkings, that is suitable for display
//	get = list => display all parking except private ones
//	get = private => display only private parkings
if(isset($_REQUEST["get"]))
{
	// List of all bicycle parkings
	// Generates a GeoJSON document with labels for in-app display
	if($_REQUEST["get"] == "list" or $_REQUEST["get"] == "private")
	{
		$geojson = array(
		'type' => 'FeatureCollection',
		'features' => array()
		);
		
		$filterClause = "";
		if($_REQUEST["get"] == "private")
			$filterClause = " AND access='private'";
		else if($_REQUEST["get"] == "list")
			$filterClause = " AND access <> 'private'";
		if(isset($_REQUEST["zones"]))
			$filterClause .= getZoneFilter($_REQUEST["zones"]);
		else
			$filterClause .= getZoneFilter();
			
		$sqlParkings = "SELECT obj_id as id,capacity,covered,parking_type as type,access,ST_AsGeoJSON(public.ST_Transform((the_geom),4326)) AS geojson,operator FROM pv_parkings where 1=1 ".$filterClause;
		$queryParkings = $PDO->query($sqlParkings);
		while($rs = $queryParkings->fetch())
		{
			$properties = array("capacity"=>$rs["capacity"]);
			$obj_label = "";
			
			// Parking type label
			if($rs["type"] == "")
				$obj_label .= $PARKING_LABEL["empty"];
			else if(isset($PARKING_LABEL[$rs["type"]]))
				$obj_label .= $PARKING_LABEL[$rs["type"]];
			else
				$obj_label .= $PARKING_LABEL["other"];
				
			$obj_label .= ", ";	
			
			// Capacity
			if($rs["capacity"] > 0)
				$obj_label .= sprintf($LABELS["map.parking.capacity"],$rs["capacity"]);
			else 
				$obj_label .= $LABELS["map.parking.noCapacity"];
			
			// is the parking covered
			if(isset($COVERED_LABEL[$rs["covered"]]))
				$obj_label .= ", ".$COVERED_LABEL[$rs["covered"]];	
			
			// access informations
			if(isset($rs["operator"]) && $rs["operator"] != "")
			{
				$accessLabel = $LABELS['map.parking.accessLabelWithOpr'];
				if($rs["access"] == "")
				$obj_label .= " ".$accessLabel["empty"];
				else if(isset($accessLabel[$rs["access"]]))
					$obj_label .= " ".sprintf($accessLabel[$rs["access"]],$rs["operator"]);
				else
					$obj_label .= " ".$accessLabel["other"];
			}
			else
			{
				$accessLabel = $LABELS['map.parking.accessLabel'];
				if($rs["access"] == "")
				$obj_label .= " ".$accessLabel["empty"];
				else if(isset($accessLabel[$rs["access"]]))
					$obj_label .= " ".$accessLabel[$rs["access"]];
				else
					$obj_label .= " ".$accessLabel["other"];
			}
			
			
			$properties["popup"] = $obj_label;
			$feature = array(
			'type' => 'Feature',
			'geometry' => json_decode($rs['geojson'], true),
			'properties' => $properties
			);
			array_push($geojson['features'], $feature);
		}
		
		header('Content-type: application/json');
		echo json_encode($geojson, JSON_NUMERIC_CHECK);
	}
	
	/*
		Ask the the server for the distance beetween a point and the closest parking
	*/
	else if($_REQUEST["get"] == "distance" && isset($_REQUEST["lon"]) && isset($_REQUEST["lat"]))
	{
		$result = array("distance"=>0);

		$geomString = "POINT(".$_REQUEST["lon"]." ".$_REQUEST["lat"].")";
		$sqlDistance = "select min(st_distance(st_transform(the_geom,:proj), st_transform(st_geomfromtext(:point,4326),:proj2))) as distance from pv_parkings where access <> 'private'";
		$queryDistance = $PDO->prepare($sqlDistance);
		$queryDistance->execute(array(":proj"=>DIST_PROJ,":proj2"=>DIST_PROJ,":point"=>$geomString));
		if($rs = $queryDistance->fetch())
		{
			$result = array("distance"=>sprintf($LABELS["map.distanceToolLabel"],floor($rs["distance"])));
		}
		
		header('Content-type: application/json');
		echo json_encode($result, JSON_NUMERIC_CHECK);
	}
	/* Calculate the numbers of parkings (with capacity) per bicycle_parking key
		- by default, in the whole area
		- restricted to a given area, with a "geom" parameter (must be a GeoJSON feature)
	*/
	else if($_REQUEST["get"] == "stats")
	{
		$result = array();
		$result["content"] = '<div class="private">'.calculateStats($PDO,$LABELS,"",$_REQUEST["geom"]).'</div>';
		$result["content"] .= '<div class="noPrivate">'.calculateStats($PDO,$LABELS," and access <> 'private' ",$_REQUEST["geom"]).'</div>';
		
		header('Content-type: application/json');
		echo json_encode($result, JSON_NUMERIC_CHECK);
	}
	
	else if($_REQUEST["get"] == "badObj")
	{
		
		// put every answer in the KML response
		$geojson = array(
		'type' => 'FeatureCollection',
		'features' => array()
		);
		
		/* There is warning about data quality when :
		 - there is no capacity (and it is not in private access, it is often impossible to determine the capacity in such a case)
		 - the type is unknown
		 - there is another bicycle parking with equal coordinates */
		$sqlBadParkings = "SELECT obj_id,capacity,parking_type as type,ST_AsGeoJSON(public.ST_Transform((the_geom),4326),6) AS geojson,(select count(*) from pv_parkings SR where P.the_geom = SR.the_geom and P.obj_id <> SR.obj_id) as doubloons FROM pv_parkings P where (capacity = 0  and  access<>'private' or parking_type = '' or  exists(select * from pv_parkings SR where P.the_geom = SR.the_geom and P.obj_id <> SR.obj_id)) ".getZoneFilter(isset($_REQUEST["zones"])?$_REQUEST["zones"]:"");
		$queryBadParkings = $PDO->query($sqlBadParkings);
		while($rs = $queryBadParkings->fetch())
		{
			$label = "";
			if($rs["type"] == "")
			{
				$label .= $LABELS["map.bad.noType"];
			}
			if($rs["capacity"] == 0)
			{
				$label .= $LABELS["map.bad.noCapacity"];
			}
			if($rs["doubloons"] > 0)
			{
				$label .= $LABELS["map.bad.doubloon"];
			}
			if(EDIT_JOSM)
			{
				$label .= '<br><a href="http://localhost:8111/load_object?objects='.$rs["obj_id"].'" target="hiddenIframe">'.$LABELS["map.bad.edit"].'</a>';
			}
			
			$feature = array(
			'type' => 'Feature',
			'geometry' => json_decode($rs["geojson"], true),
			'properties' => array("label"=>$label)
			);
			array_push($geojson['features'], $feature);
		}
		
		header('Content-type: application/json');
		echo json_encode($geojson, JSON_NUMERIC_CHECK);
	}
	/*
		This request for the area surrouning the bicycle parkings
	*/
	else if($_REQUEST["get"] == "surroundingArea")
	{
		$geojson = array(
		'type' => 'FeatureCollection',
		'features' => array()
		);
		
		$sqlBadParkings = "SELECT distance,ST_AsGeoJSON(public.ST_Transform(geom,4326),5) AS geojson FROM pv_parking_dist_zones where 1=1 ".getZoneFilter(isset($_REQUEST["zones"])?$_REQUEST["zones"]:"")." order by distance DESC";
		$queryBadParkings = $PDO->query($sqlBadParkings);
		while($rs = $queryBadParkings->fetch())
		{
			// Retrieve the fill colour associated with the threshold
			$colour = "";
			foreach($DISTANCE_LEVELS as $distElt)
			{
				if($distElt["dist"] == $rs["distance"])
				{
					$colour = $distElt["colour"];
				}
			}
			
			$feature = array(
			'type' => 'Feature',
			'geometry' => json_decode($rs["geojson"], true),
			'properties' => array("style"=>array(
				"color"=>$colour,
				"stroke"=>false,
				"fillOpacity"=>0.3))
			);
			array_push($geojson['features'], $feature);
		}
		
		header('Content-type: application/json');
		echo json_encode($geojson, JSON_NUMERIC_CHECK);
	}
	
	
	else if($_REQUEST["get"] == "boundaries")
	{
		$geojson = array(
		'type' => 'FeatureCollection',
		'features' => array()
		);
		
		$sqlBadParkings = "SELECT ST_AsGeoJSON(public.ST_Transform(geom,4326),5) AS geojson FROM pv_zones where active = 1 ".getZoneFilter(isset($_REQUEST["zones"])?$_REQUEST["zones"]:"");
		$queryBadParkings = $PDO->query($sqlBadParkings);
		while($rs = $queryBadParkings->fetch())
		{			
			$feature = array(
			'type' => 'Feature',
			'geometry' => json_decode($rs["geojson"], true),
			'properties' => array("style"=>array(
				"color"=>'#555',
				"stroke"=>true,
				"weight"=>2,
				"fill"=>false,
				"opacity"=>1))
			);
			array_push($geojson['features'], $feature);
		}
		
		header('Content-type: application/json');
		echo json_encode($geojson, JSON_NUMERIC_CHECK);
	}
	
	
	else if($_REQUEST["get"] == "zones")
	{
		
		// put every answer in the KML response
		$result = array();
		
		$sqlZones = "SELECT P.zone_id,label,visible_default,sum(capacity) as spaces FROM pv_zones Z, pv_parkings P WHERE active = 1 and access <> 'private' AND P.zone_id = Z.zone_id GROUP BY P.zone_id,label,visible_default ORDER BY label";
		$queryZones = $PDO->query($sqlZones);
		while($rs = $queryZones->fetch(PDO::FETCH_ASSOC))
		{
			array_push($result, $rs);
		}
		
		header('Content-type: application/json');
		echo json_encode($result, JSON_NUMERIC_CHECK);
	}
}
?>