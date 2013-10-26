<!DOCTYPE html>
<html>
<head>
<script src="http://maps.googleapis.com/maps/api/js?key=AIzaSyB9vnyKt02oej-h9VgkI-NPfDy1IfF9cwI&sensor=false&libraries=visualization">
</script>

<script>
function initialize()
{
var mapProp = {
  center:new google.maps.LatLng(53.9000, -122.7667),
  zoom:7,
  overviewMapControl: true,
  dissipating: false,
  mapTypeId:google.maps.MapTypeId.TERRAIN
  };
var map=new google.maps.Map(document.getElementById("googleMap")
  ,mapProp);

var heatmapData = [
<?php
	require('db.php');
	$sql = "select station.latitude, station.longitude, sample.value
	        FROM sample
		inner join station on sample.station_id=station.station_id
		where sensor_name='".$_GET['sensor']."'
		and time like '2013-10-24 18:00%';";
	$stmt = $db->prepare($sql);
	$stmt->execute();
	while ($row = $stmt->fetch()) {
	        //print_r($row);
		$lat = $row['latitude'];
		$lng = $row['longitude'];
		$val = $row['value'];
		echo "{location: new google.maps.LatLng($lat, $lng), weight: $val},";
	}
?>
];

  var heatmap = new google.maps.visualization.HeatmapLayer({
    data: heatmapData,
    radius: 100,
//    maxIntensity: 50.0
  });
  heatmap.setMap(map);


/*
google.maps.event.addListener(map, 'zoom_changed', function () {
			var foo = map.getZoom() - 6;
			foo = foo * foo;
			foo = foo;
          heatmap.setOptions({  radius: foo  });
      });
*/

}


google.maps.event.addDomListener(window, 'load', initialize);

</script>
</head>


<body>
<div id="googleMap" style="position:absolute;left:0;top:0;width:90%;height:100%;"></div>

</body>
</html>
