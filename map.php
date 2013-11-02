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

function initialize( sensor )
{
	var mapProp = {
		center:new google.maps.LatLng(53.9000, -122.7667),
		zoom:11,
		minZoom: 3,
		overviewMapControl: true,
		dissipating: false,
		mapTypeId:google.maps.MapTypeId.TERRAIN
	};
	var map=new google.maps.Map(document.getElementById("googleMap"),mapProp);
	
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
	}

	function goMarkers( data ) {
		$.each(data, function(index, v) {
			new google.maps.Marker({
				position: new google.maps.LatLng(v.latitude, v.longitude),
				map: map,
				title: "Station# " + v.station_id + " at time " + v.time + ". reading= " + v.value,
			});
		});	
	}

	var gradient = new Rainbow();
	gradient.setSpectrum('#FFFFFF', '#FF0000');
	// Safety values taken from World Heath Organization
	// http://www.who.int/mediacentre/factsheets/fs313/en/
	switch (sensor) {
		case 'PM10': // large particulate
			gradient.setNumberRange(0, 50); // max for 24-hour period
			break;
		case 'PM25': // small particulate
			gradient.setNumberRange(0, 25); // max for 24-hour period
			break;
		case 'O3': // ozone
			gradient.setNumberRange(0, 100); // 8 hour mean
			break;
		case 'NO2': // nitrogen dioxide 
			gradient.setNumberRange(0, 40); // 8 hour mean
			break;
		case 'SO2': // sulfur dioxide 
			gradient.setNumberRange(0, 20); // 24 hour mean
			break;
		default:
			gradient.setNumberRange(0, 100);
			break;
	}
			

	function goCircles( data ) {
		$.each(data, function(i, v) {
			new google.maps.Circle({
				strokeColor: gradient.colourAt(v.value),
				strokeOpacity: 0.9,
				strokeWeight: 2,
				fillColor: gradient.colourAt(v.value),
				fillOpacity: 1.0, // overlapping transparency is counterintuitive
				map: map,
				center: new google.maps.LatLng(v.latitude, v.longitude),
				radius: 2000
			});
		});
	}


	// Download data for this sensor, then add data into the map
	$.ajax( "api/sensor/" + sensor )
		.done( goCircles )
//		.done( goHeatmap )
		.done( goMarkers );
}

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
			initialize( sensor );
		};
		options.change(handler).keypress(handler).ready(handler);
	});
</script>
</head>


<body>

<div id="test"></div>

<select name="sensor" id="sensor">
</select>

<div id="googleMap" style="position:absolute;left:0;top:10%;width:100%;height:90%;"></div>

</body>
</html>
