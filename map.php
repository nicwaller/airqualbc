<!DOCTYPE html>
<html>
<head>
<script src="http://code.jquery.com/jquery-1.10.1.min.js"></script>
<script src="http://maps.googleapis.com/maps/api/js?key=AIzaSyB9vnyKt02oej-h9VgkI-NPfDy1IfF9cwI&sensor=false&libraries=visualization">
</script>

<script>
function initialize( sensor )
{
var mapProp = {
  center:new google.maps.LatLng(53.9000, -122.7667),
  zoom:6,
  overviewMapControl: true,
  dissipating: false,
  mapTypeId:google.maps.MapTypeId.TERRAIN
  };
var map=new google.maps.Map(document.getElementById("googleMap"),mapProp);

var qu = "api/sensor/" + sensor;
$.ajax( qu )
	.done(function( msg ) {
		var heatmapData = [];
		$.each(msg, function(index, value) {
			var lat = value.latitude;
			var lng = value.longitude;
			heatmapData.push({
				location: new google.maps.LatLng(lat, lng),
				weight: 1.0,
			});
			new google.maps.Marker({
				position: new google.maps.LatLng(lat, lng),
				map: map,
				title: "Station# " + value.station_id + " at time " + value.time,
			});
		});
		var heatmap = new google.maps.visualization.HeatmapLayer({
			data: heatmapData,
			radius: 100,
//			maxIntensity: 50.0
		});
		heatmap.setMap(map);
	});

}


$.ajax( "api/sensor/FP10" )
	.done(function( msg ) {
		//console.log( msg );
	});

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

<select name="sensor" id="sensor">
</select>

<div id="googleMap" style="position:absolute;left:0;top:10%;width:100%;height:90%;"></div>

</body>
</html>
