<!DOCTYPE html>
<html>
<head>
<script src="http://d3js.org/d3.v3.min.js" charset="utf-8"></script>
<script src="http://code.jquery.com/jquery-1.10.1.min.js"></script>
<script src="http://maps.googleapis.com/maps/api/js?key=AIzaSyB9vnyKt02oej-h9VgkI-NPfDy1IfF9cwI&sensor=false&libraries=visualization">
</script>

<script>
var svg, overlay;

function initialize( sensor )
{
	var mapProp = {
		center:new google.maps.LatLng(53.9000, -122.7667),
		zoom:6,
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
				title: "Station# " + v.station_id + " at time " + v.time,
			});
		});	
	}

	// Download data for this sensor, then add data into the map
	$.ajax( "api/sensor/" + sensor )
		.done( goHeatmap )
		.done( goMarkers );
}

$.ajax( "api/sensor" )
	.done(function( msg ) {
		var options = $("#sensor");
		$.each(msg, function(index, value) {
			options.append( $("<option />").val(this).text(this) );
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
