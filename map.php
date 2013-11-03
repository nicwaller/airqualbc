<!DOCTYPE html>
<html>
<head>
<script src="http://d3js.org/d3.v3.min.js" charset="utf-8"></script>
<script src="http://code.jquery.com/jquery-1.10.1.min.js"></script>
<script src="http://maps.googleapis.com/maps/api/js?key=AIzaSyB9vnyKt02oej-h9VgkI-NPfDy1IfF9cwI&sensor=false&libraries=visualization"></script>
<script src="http://10.0.0.13/pghacks/air_quality/lib/rainbowvis.js"></script>
</script>

<script>
var svg, overlay;

var map;

var all_markers = [];
var all_overlay = [];

function initialize()
{
	var mapProp = {
		center:new google.maps.LatLng(53.9000, -122.7667),
		zoom:11,
		minZoom: 3,
		overviewMapControl: true,
		dissipating: false,
		mapTypeId:google.maps.MapTypeId.TERRAIN
	};
	map=new google.maps.Map(document.getElementById("googleMap"),mapProp);

	map.controls[google.maps.ControlPosition.RIGHT_CENTER].push(document.getElementById("legend"));
	map.controls[google.maps.ControlPosition.TOP_CENTER].push(document.getElementById("sensor_control"));
}

function showSensor( sensor ) {
	while (all_overlay[0]) {
		all_overlay.pop().setMap(null);
	}
	while (all_markers[0]) {
		all_markers.pop().setMap(null);
	}

	function goHeatmap( data ) {
		var heatmapData = [];
		$.each(data, function(index, v) {
			heatmapData.push({
				location: new google.maps.LatLng(v.latitude, v.longitude),
				weight: 1.0,
			});
		});
		var heatmap = new google.maps.visualization.HeatmapLayer({
			data: heatmapData,
			radius: 100,
			//maxIntensity: 50.0
		});
		heatmap.setMap(map);
		all_overlay.push(heatmap);
	}

	function goMarkers( data ) {
		$.each(data, function(index, v) {
			all_markers.push(new google.maps.Marker({
				position: new google.maps.LatLng(v.latitude, v.longitude),
				map: map,
				title: "Station# " + v.station_id + " at time " + v.time + ". "+ sensor +" reading= " + v.value,
			}));
		});	
	}

	var gradient = new Rainbow();
	gradient.setSpectrum('#FFFFFF', '#FFFF00', '#FFA500', '#FF0000');
	// Safety values taken from World Heath Organization
	// http://www.who.int/mediacentre/factsheets/fs313/en/
	switch (sensor) {
		case 'PM10': // large particulate
			// Same units for Envistaweb and WHO safety threshold
			gradient.setNumberRange(0, 50); // max for 24-hour period
			break;
		case 'PM25': // small particulate
			// Same units for Envistaweb and WHO safety threshold
			gradient.setNumberRange(0, 25); // max for 24-hour period
			break;
		case 'O3': // ozone
			// WHO threshold 100 ug/m^3
			// = 0.051 ppm = 51 ppb
			gradient.setNumberRange(0, 51); // 8 hour mean
			break;
		case 'NO2': // nitrogen dioxide 
			// WHO threshold 40ug/m^3
			// = 0.040 mg/m^3
			// = 0.021 ppm
			// = 21 ppb (Envistaweb reports ppb)
			gradient.setNumberRange(0, 21); // 8 hour mean
			break;
		case 'SO2': // sulfur dioxide 
			// WHO threshold 20ug/m^3 = 0.007 ppm = 7.0 ppb
			gradient.setNumberRange(0, 7); // 24 hour mean
			break;
		default:
			gradient.setNumberRange(0, 100);
			break;
	}
			

	function goCircles( data ) {
		$.each(data, function(i, v) {
			all_overlay.push(new google.maps.Circle({
				strokeColor: gradient.colourAt(v.value),
				strokeOpacity: 0.9,
				strokeWeight: 2,
				fillColor: gradient.colourAt(v.value),
				fillOpacity: 1.0, // overlapping transparency is counterintuitive
				map: map,
				center: new google.maps.LatLng(v.latitude, v.longitude),
				radius: 2000
			}));
		});
	}


	// Download data for this sensor, then add data into the map
	$.ajax( "api/sensor/" + sensor )
		.done( goCircles )
//		.done( goHeatmap )
		.done( goMarkers );
}

$( document ).ready(initialize);

$.ajax( "api/sensor" )
	.done(function( msg ) {
		var options = $("#sensor");
		$.each(msg, function(index, value) {
			if ($.inArray(value, ['PM10', 'PM25', 'O3', 'NO2', 'SO2']) != -1) {
				options.append( $("<option />").val(this).text(this) );
			}
		});
		var handler = function() {
			var sensor = $( "#sensor option:selected").text();
			showSensor( sensor );
		};
		options.change(handler).keypress(handler).ready(handler);
	});
</script>
</head>


<body>

<div id="test"></div>

<div id="legend" width="50" height="400">
	<svg xmlns="http://www.w3.org/2000/svg" version="1.1" height="400" width="50">
		<linearGradient id="G1" x1="0%" y1="100%" x2="0%" y2="0%">
			<stop offset="0%" style="stop-color:rgb(255,255,255); stop-opacity:1" />
			<stop offset="33%" style="stop-color:rgb(255,255,0); stop-opacity:1" />
			<stop offset="66%" style="stop-color:rgb(255,165,0); stop-opacity:1" />
			<stop offset="100%" style="stop-color:rgb(255,0,0); stop-opacity:1" />
		</linearGradient>
		<rect x="0" y="0" width="50" height="400" fill="url(#G1)" />
	</svg>
</div>

<div id="sensor_control" width="50" height="50">
	<select name="sensor" id="sensor" style="height: 40px;"></select>
</div>

<div id="googleMap" style="position:absolute;left:0;top:0%;width:100%;height:100%;"></div>

</body>
</html>
