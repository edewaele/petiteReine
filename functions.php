<?php

function getLocale($LOCALES)
{
	foreach(explode(",",$_SERVER['HTTP_ACCEPT_LANGUAGE']) as $lang)
	{
		foreach($LOCALES as $supportedLangs => $localeID)
		{
			if(strpos($lang,$supportedLangs) == 0)
			{
				return $localeID;
			}
		}
	}
	return 'default';
}

/**
Class for exporting parkings into a GeoJSON file
*/
class GeoJSON{
	private $points = array(
		'type' => 'FeatureCollection',
		'features' => array()
		);
	private $file;
	function __construct($pFile)
	{
		$this->file = $pFile;
	}
	/**
		Add a geometry with its properties
	*/
	public function addPoint($geom,$attr)
	{
		array_push($this->points['features'], array(
			'type' => 'Feature',
			'geometry' => json_decode($geom, true),
			'properties' => $attr
			));
	}
	/**
		Save the GeoJSON file
	*/
	public function export()
	{
		file_put_contents($this->file,json_encode($this->points, JSON_NUMERIC_CHECK));
	}
}

?>