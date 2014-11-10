-- This script inserts the table we need
-- Prerequisite : PostGIS 9.3 or newer (may work with older version)
DROP TABLE IF EXISTS pv_parkings;
DROP TABLE IF EXISTS pv_parking_dist_zones;
DROP TABLE IF EXISTS pv_zones;
DROP TABLE IF EXISTS pv_import_trace;

-- The zone represent local authorities, with their boundaries
-- You have to add data in the table before launching the import script
CREATE TABLE public.pv_zones
(
  zone_id character varying(20) NOT NULL,
  label character varying(32),		-- displayed name
  osm_id numeric,					-- relation Id in OpenStreetMap
  active integer,					-- whether the parkings within this zone may be displayed in the map
  visible_default integer,			-- whether the zone and its parkings are displayed in the map on startup
  geom geometry(MultiPolygon,4326), -- administrative boundary
  CONSTRAINT pv_zones_pkey PRIMARY KEY (zone_id)
);

-- in the basic setup, the zone mechanism is not activated
-- to bypass it the data-retrieving queries, we create a single zone that covers the whole earth
insert into pv_zones values ('default','Default',0,1,1,st_multi(st_expand(ST_GeomFromText('POINT(0 0)',4326),90)));

-- The list of bicycle parkings
-- This table is filled by the import script
CREATE TABLE pv_parkings
(
  obj_id character varying(30) NOT NULL,-- w{way_id} if the parking in a way in OSM, n{node_id} if the parking in a node in OSM 
  capacity integer,						-- capacity key in OpenStreetMap
  covered character varying(10),		-- covered key in OpenStreetMap
  parking_type character varying(20),	--	bicycle_parking key in OpenStreetMap
  the_geom geometry(Point,4326),
  access character varying(20),			-- access key in OpenStreetMap
  "timestamp" character(30),
  zone_id character varying(20),		-- the zone that a parking belong to (computed by the import batch)
  CONSTRAINT pv_parkings_pkey PRIMARY KEY (obj_id)
);



-- Given the zones, parkings and $DISTANCE_LEVELS (in conf.php), each geometry (geom) is a the area within a zone (zone), which a distance to the closest parking is inferior to a given threshold (distance column)
-- This table is filled by the import script
CREATE TABLE pv_parking_dist_zones
(
  zone_id character varying(20) NOT NULL,
  distance integer NOT NULL,
  geom geometry(MultiPolygon,4326),
  CONSTRAINT pv_parking_dist_zones_pkey PRIMARY KEY (zone_id, distance)
);

-- Log table for the import batch
CREATE TABLE pv_import_trace
(
  trace_id integer NOT NULL DEFAULT nextval('pv_import_trace_trace_id_seq'::regclass),
  status boolean,
  startdt timestamp with time zone,
  duration numeric,
  area integer,
  number_parking integer,
  total_capacity integer,
  number_nocapacity integer,
  number_notype integer,
  number_problem integer,
  CONSTRAINT pv_import_trace_pkey PRIMARY KEY (trace_id)
);