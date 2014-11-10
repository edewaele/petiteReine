var map;
var parkingsLayer;
var parkingIco;
var isDrawing = false;



function mapInit(){
	map = L.map('map',{zoomControl:false}).setView([47.38925, 0.68841], 13);

	var baseLayers = {
	'OpenStreetMap Default': L.tileLayer.provider('OpenStreetMap.Mapnik'),
	'OpenStreetMap German Style': L.tileLayer.provider('OpenStreetMap.DE'),
	'OpenStreetMap Black and White': L.tileLayer.provider('OpenStreetMap.BlackAndWhite'),
	'OpenStreetMap H.O.T.': L.tileLayer.provider('OpenStreetMap.HOT'),
	'Thunderforest OpenCycleMap': L.tileLayer.provider('Thunderforest.OpenCycleMap'),
	'Thunderforest Transport': L.tileLayer.provider('Thunderforest.Transport'),
	'Thunderforest Landscape': L.tileLayer.provider('Thunderforest.Landscape'),
	'Hydda Full': L.tileLayer.provider('Hydda.Full').addTo(map),
	'MapQuest OSM': L.tileLayer.provider('MapQuestOpen.OSM')
	};
	
	parkingsLayer = L.markerClusterGroup({
			maxClusterRadius: CLIENT_CONF.maxClusterRadius,
			disableClusteringAtZoom :CLIENT_CONF.disableClusteringAtZoom,
			iconCreateFunction: function (cluster) {
				// the cluster symboliser is determined below, according to the total capacity
				var markers = cluster.getAllChildMarkers();
				var n = 0;
				for (var i = 0; i < markers.length; i++) {
					n += markers[i].feature.properties.capacity;
				}
				var clusterClass = 'marker-cluster-small';
				if(n > 100)clusterClass = 'marker-cluster-medium';
				if(n > 500)clusterClass = 'marker-cluster-large';
				var htmlCode = "<div><span>"+n+"</div></span>";
				
				return L.divIcon({ html: htmlCode, className: clusterClass+' marker-cluster', iconSize: L.point(40, 40) });
			}	
			}).addTo(map);
	
	var parkingLoad = function(data)
		{
			var geojson = L.geoJson(data, {
				pointToLayer: function (feature, latlng) {
					var myIcon = L.divIcon({className: 'my-div-icon',
						'html':'<img src="img/parking_bicycle.png" class="parking_icon"><span class="capacite">'+feature.properties.capacity+'</span>',
						iconAnchor:  [9, 23]
						});
					return L.marker(latlng, {icon: myIcon,zIndexOffset:0});
				},
				onEachFeature :function (feature, layer) {
					layer.bindPopup(feature.properties.popup,{closeButton:false,offset:[0,-20]});
				}
			});
			
			parkingsLayer.addLayer(geojson);
			
			map.fitBounds(parkingsLayer.getBounds());
			
			$.ajax({
				dataType:'json',
				url:'api.php',
				data:{get:'stats',zones:getSelectedZones()},
				success:function(data)
				{
					$("#stats_global").html(data.content);
				}
			});
			parkingsLayer.layerLoaderLoaded =  true;
	};
	layerLoader.addLayer(parkingsLayer,'list',parkingLoad);
	map.addLayer(parkingsLayer);
	$.ajax({
				dataType:'json',
				url:'api.php',
				data:{get:'list'},
				success:parkingLoad
	});
	popupManager.addLayer(parkingsLayer);	
	
	// this layers display the private parking
	privateLayer = L.featureGroup().addTo(map);
	popupManager.addLayer(privateLayer);		
	map.removeLayer(privateLayer);
	
	// this layer will be loaded on demand
	layerLoader.addLayer(privateLayer,'private',function(data){
		var geojson = L.geoJson(data, {
			pointToLayer: function (feature, latlng) {
				return L.marker(latlng, {icon: privateParkingIco});
			},
			onEachFeature :function (feature, layer) {
				layer.bindPopup(feature.properties.popup,{closeButton:false,offset:[0,-20]});
			}
		});
		privateLayer.addLayer(geojson);
	});

	// this layers displays the parking with missing information
	badObjLayer = L.markerClusterGroup({	// the layer is clustered just like the parking layer
			maxClusterRadius: CLIENT_CONF.maxClusterRadius,
			disableClusteringAtZoom :CLIENT_CONF.disableClusteringAtZoom,
			iconCreateFunction: function (cluster) {
				var markers = cluster.getAllChildMarkers();
				var htmlCode = "<div><span>"+markers.length+"</div></span>";// The circle display the number of parkings that lack information				
				return L.divIcon({ html: htmlCode, className: 'marker-cluster-bad marker-cluster', iconSize: L.point(40, 40) });
			}	
			}).addTo(map);
	popupManager.addLayer(badObjLayer);		
	map.removeLayer(badObjLayer);
	
	// this layer will be loaded on demand
	layerLoader.addLayer(badObjLayer,'badObj',function(data){
		var geojson = L.geoJson(data, {
			pointToLayer: function (feature, latlng) {
				return L.marker(latlng, {icon: badParkingIco,zIndexOffset:100});// zIndexOffset makes this layer appear above the parking layer
			},
			onEachFeature :function (feature, layer) {
				layer.bindPopup(feature.properties.label,{closeButton:false,offset:[0,-20]});
			}
		});
		badObjLayer.addLayer(geojson);
	});
	
	// Pseudo-isochrone layer
	surroundingAreaLayer = L.featureGroup();//.addTo(map);
	map.removeLayer(surroundingAreaLayer);
	// all click are redirected to the map object
	surroundingAreaLayer.on('click', function(e) {map.trigger(e)});
	// this layer will be loaded on demand
	layerLoader.addLayer(surroundingAreaLayer,'surroundingArea',function(data){
		var geojson = L.geoJson(data,{
			style: function (feature) {
			return feature.properties && feature.properties.style;
			}
		});
		surroundingAreaLayer.addLayer(geojson);
		map.addLayer(surroundingAreaLayer);
	});
	
	// This layer displays the local boundaries, as defined in pv_zones table
	if(CLIENT_CONF.zoneFilter)
	{
		boundariesLayer = L.featureGroup();//.addTo(map);
		map.removeLayer(boundariesLayer);
		// this layer will be loaded on demand
		layerLoader.addLayer(boundariesLayer,'boundaries',function(data){
			var geojson = L.geoJson(data,{
				style: function (feature) {
				return feature.properties && feature.properties.style;
				}
			});
			boundariesLayer.addLayer(geojson);
			map.addLayer(boundariesLayer);
		});
	}
	
	// Control layer
	var overlays = {};
	overlays[CLIENT_CONF.labels.parkingsLayer] = parkingsLayer;
	overlays[CLIENT_CONF.labels.badObjLayer] = badObjLayer;
	overlays[CLIENT_CONF.labels.surroundingAreaLayer] = surroundingAreaLayer;
	overlays[CLIENT_CONF.labels.privateLayer] = privateLayer;
	if(CLIENT_CONF.zoneFilter)
	{
		overlays[CLIENT_CONF.labels.boundariesLayer] = boundariesLayer;
	}
	
	L.control.zoom({position:'topright'}).addTo(map);
	L.control.layers(baseLayers, overlays).addTo(map);
	L.control.scale({imperial:false,maxWidth:200}).addTo(map);
	

	
	// The draw allow the user to draw rectangles and polygons
	var drawnItems = new L.FeatureGroup();
		map.addLayer(drawnItems);
	var drawControl = new L.Control.Draw({
			draw: {
				position: 'topleft',
				polyline : false,
				circle : false,
				marker : false
			},
			edit: false
		});
		map.addControl(drawControl);
	
	var LeafIcon = L.Icon.extend({
			options: {
				iconSize:     [20, 20],
				iconAnchor:   [10, 20]
			}
		});
	badParkingIco = new LeafIcon({iconUrl:'img/parking_bicycle_red.png'});
	privateParkingIco = new LeafIcon({iconUrl:'img/parking_private.png'});
	
	// When the user finishes drawing a shape
	map.on('draw:created', function (e) {
			isDrawing = false;
		
			var type = e.layerType,
				layer = e.layer;
			drawnItems.clearLayers();// remove the previously drawn shapes
			drawnItems.addLayer(layer);// the new one is displayed
			
			// the server is queried to display the stats per zone
			var geometryRect = layer.toGeoJSON()
			geometryRect.geometry.crs = {"type":"name","properties":{"name":"EPSG:4326"}};
			$.ajax({
				dataType:'json',
				url:'api.php',
				data:{get:'stats',geom:geometryRect,zones:getSelectedZones()},
				success:function(data)
				{
					$("#stats_zone").html(data.content);
				}
			});
		});

	L.icon = function (options) {
    return new L.Icon(options);
	};
	
	map.on('click', onMapClick);
	
	// the layerLoader manages vector layer loading
	map.on('overlayadd', function(e){
		layerLoader.loadLayer(e.layer);
	});
	
	
	
	// Push button initialisation
	// When the button is clicked, I simulate a click on the rectangle draw tool in Leaflet.draw
	$("#drawRect").click(function(){
		$("#eraseZone").click();
		$("a.leaflet-draw-draw-rectangle").each(function(){
			isDrawing = true;
			this.click();
		});
	});
	
	// When the button is clicked, I simulate a click on the polygon draw tool in Leaflet.draw
	$("#drawPolygon").click(function(){
		$("#eraseZone").click();
		$("a.leaflet-draw-draw-polygon").each(function(){
			isDrawing = true;
			this.click();
		});
	});
	
	$("#eraseZone").click(function(){
		$("#stats_zone").html("");
		drawnItems.clearLayers();
	});
	
	// jQuery UI declarations
	//$("#leftPanel").accordion({ fillSpace: true });
	$("#leftPanel").accordion({heightStyle: "content",
                collapsible: true,
				active  :false,
				animate : false});
	$("input[type=button]").button();
	$("#aboutButton").click(function(){
		$("#aboutDialog").dialog('open');
	});
	$("#aboutDialog").dialog({
		autoOpen:false,
		modal:true,
		minWidth:500
	});
	
	// resize
	$(".ui-accordion-content").css("max-height",($(document).height()-CLIENT_CONF.reservedHeight)+"px");
	map.on('resize', function(event){
		if(CLIENT_CONF.isMobile)
			$(".ui-accordion-content").css("max-height",(event.newSize.y-CLIENT_CONF.reservedHeightMobile)+"px");
		else
			$(".ui-accordion-content").css("max-height",(event.newSize.y-CLIENT_CONF.reservedHeight)+"px");
	});
	
	if(CLIENT_CONF.zoneFilter)
	{
		$.ajax({
			dataType:'json',
			url:'api.php',
			data:{get:'zones'},
			success:function(data)
			{
				for(var numZone = 0; numZone < data.length; numZone++)
				{
					$("#zoneList").append('<input type="checkbox" '+(data[numZone].visible_default==1?'checked="checked"':"")+' class="zoneSelector" id="zone'+data[numZone].zone_id+'">'+data[numZone].label+'<br>');
				}
			}
		});
		
		$("#zonesApply").click(function(){
			layerLoader.reloadAll();
		});
		$("#zonesAll").click(function(){
			$(".zoneSelector").attr("checked",true);
		});
		$("#zonesNone").click(function(){
			$(".zoneSelector").attr("checked",false);
		});
	}
}

/**
	Evertime the user clicks the maps (except markers), the client queries the BD for the distance to the closest parking
*/
function onMapClick(e) {
	if( !isDrawing )
	{
		$.ajax({
		dataType:'json',
		url:'api.php',
		data:{get:'distance',lon:e.latlng.lng,lat:e.latlng.lat},
		success:function(data)
		{
			var popup = L.popup().setLatLng(e.latlng)
				.setContent(data.distance)
				.openOn(map);
		}
		});
	}
}

// This object load layers on demand, that is to say, when the user requires it to be displayed
var layerLoader = {
	layers:[],
	loadFunctions:[],
	requests:[],
	data:[],
	//  a new layer to be displayed on demand, with a request parameter and a function to be called to process the data
	addLayer:function(layer,request,loadFunction){
		layer.layerLoaderID = this.layers.length;
		layer.layerLoaderLoaded =  false;
		this.layers.push(layer);
		this.loadFunctions.push(loadFunction);
		this.requests.push(request);
		this.data.push({});
	},
	// called when the a layer is displayed
	loadLayer:function(layer)
	{
		// if the data was not displayed yet
		if(! layer.layerLoaderLoaded)
		{
			var layerNum = layer.layerLoaderID;
			var parentObject = this;
			$.ajax({	// AJAX request
				dataType:'json',
				url:'api.php',
				data:{get:this.requests[layerNum],zones:getSelectedZones()},
				success:function(data)
				{	// call to the layer's function
					parentObject.loadFunctions[layerNum](data);
				}
			});
			layer.layerLoaderLoaded =  true;
		}
	},
	reloadAll:function(){
		for(var numLayer = 0; numLayer <  this.layers.length; numLayer++)
		{
			var currentLayer = this.layers[numLayer];
			currentLayer.layerLoaderLoaded =  false;
			currentLayer.clearLayers();
			if(map.hasLayer(currentLayer))
			{
				this.loadLayer(currentLayer);
			}
		}
	},
	getSelectedZones : function(numLayer)
	{
		var zoneString = "";
		if(CLIENT_CONF.zoneFilter)
		{
			$(".zoneSelector").each(function(chkbox){
				if($(this).is(':checked')){
					var zoneId = $(this).attr("id").replace("zone","");
					if(! zoneId in this.data[numLayer])
					{
						if(zoneString == "")
							zoneString = zoneId;
						else
							zoneString += ","+zoneId;
					}
				}
			});
		}
		return zoneString;
	}
};

/*
	This object manages the display of popups by vector layers
*/
var popupManager = {
	hoverTimeout:null,
	addLayer: function(newLayer){
		newLayer.on('click',function (e){
			if(!isDrawing)
			{
				e.layer.openPopup();
				clearTimeout(this.hoverTimeout);
			}
		});
		newLayer.on('mouseover', function(e) {
			if(!isDrawing)
			{
				e.layer.openPopup();
				clearTimeout(this.hoverTimeout);
			}
		});
		newLayer.on('mouseout', function(e) {
			this.hoverTimeout = setTimeout(function(){
				e.layer.closePopup();
				},CLIENT_CONF.popupTimeout);
		});
	}
};


function getSelectedZones()
{
	var zoneString = "";
	if(CLIENT_CONF.zoneFilter)
	{
		$(".zoneSelector").each(function(chkbox){
			if($(this).is(':checked')){
				var zoneId = $(this).attr("id").replace("zone","");
				if(zoneString == "")
					zoneString = zoneId;
				else
					zoneString += ","+zoneId;
			}
		});
	}
	return zoneString;
}

window.onload = mapInit;