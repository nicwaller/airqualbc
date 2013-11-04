<!DOCTYPE html>
<html>
<head>
<script src="http://code.jquery.com/jquery-1.10.1.min.js"></script>
<script src="http://maps.googleapis.com/maps/api/js?key=AIzaSyB9vnyKt02oej-h9VgkI-NPfDy1IfF9cwI&sensor=false&libraries=visualization"></script>
<script src="/lib/rainbowvis.js"></script>
</script>

<script>
var map;

var all_markers = [];
var all_overlay = [];

// [date] the point where we start calculating offsets
// [int]  steps
// [int]  step_size (in minutes, positive or negative)
function time_steps(origin, steps, step_size) {
	var results = [];
	var time = new Date(origin);
	for (var i=0; i<steps; i++) {
		results.push(time);
		time = new Date(time.getTime() + step_size*60000);
	}
	return results;
}

function initialize()
{
	// Set up the Google Map
	var ptPrinceGeorge = new google.maps.LatLng(53.9000, -122.7667);

	map = new google.maps.Map(document.getElementById("googleMap"),{
		center: ptPrinceGeorge,
		zoom:11,
		minZoom: 3,
		mapTypeId:google.maps.MapTypeId.TERRAIN
	});

	// Set up the time menu with hours in the previous day
	var time_control = $('<select />').attr('id', 'time');
	tmo = new Date();
	tmo.setHours(0,0,0,0);
	$.each( time_steps(tmo, 24, -60), function(i,v) {
		time_control.append( $('<option />').val(v.getTime()/1000).text(v.toLocaleString()) );
	});
	time_control.change(getUpdate).keypress(getUpdate);
	$('body').append( time_control );
	map.controls[google.maps.ControlPosition.TOP_CENTER].push( time_control[0] );

	var monitor_control = $('<select />').attr('id', 'monitor');
	$.ajax( "api/monitor" ).done(function( msg ) {
		$.each(msg, function(index, value) {
			var name = value.monitor_name;
			if ($.inArray(name, ['PM10', 'PM25', 'O3', 'NO2', 'SO2', 'TEMP_MEAN']) != -1) {
				monitor_control.append( $("<option />").val(name).text(name) );
			}
		});
		monitor_control.change(getUpdate).keypress(getUpdate);
	});
	map.controls[google.maps.ControlPosition.TOP_CENTER].push( monitor_control[0] );

	// TODO build the legend programatically based on settings for selected monitor type
	map.controls[google.maps.ControlPosition.RIGHT_CENTER].push(document.getElementById("legend"));

	// It's unclear to me why I have to wait a while (1s) before doing the update.
	// Trying to run getUpdate now returns undefined for selection in monitor_control.
	// It was working before, when <select> was in the HTML DOM.
	setTimeout(getUpdate, 500);

	// TODO: a little sun/moon spinner would be super neat.
}

function showMonitor( monitor, time ) {

	function goMarkers( data ) {
		while (all_markers[0]) {
			all_markers.pop().setMap(null);
		}
		$.each(data, function(index, v) {
			all_markers.push(new google.maps.Marker({
				position: new google.maps.LatLng(v.latitude, v.longitude),
				map: map,
				title: "Station# " + v.station_id + " at time " + v.time + ". "+ monitor +" reading= " + v.value,
			}));
		});	
	}

	var gradient = new Rainbow();
	gradient.setSpectrum('#FFFFFF', '#FFFF00', '#FFA500', '#FF0000');
	// Safety values taken from World Heath Organization
	// http://www.who.int/mediacentre/factsheets/fs313/en/
	switch (monitor) {
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
		case 'TEMP_MEAN': // temperature, we all know and love
			gradient.setNumberRange(-40, 40);
			gradient.setSpectrum('#0000FF', '#FFFFFF', '#00FF00');
			break;
		default:
			gradient.setNumberRange(0, 100);
			break;
	}
			
	function goCircles( data ) {
		while (all_overlay[0]) {
			all_overlay.pop().setMap(null);
		}
		$.each(data, function(i, v) {
			all_overlay.push(new google.maps.Circle({
				strokeColor: '#000000',
				strokeOpacity: 1.0,
				strokeWeight: 0.0,
				fillColor: gradient.colourAt(v.value),
				fillOpacity: 1.0, // overlapping transparency is counterintuitive
				map: map,
				center: new google.maps.LatLng(v.latitude, v.longitude),
				radius: 2000
			}));
		});
	}

	// Download data for this monitor, then add data into the map
	$.ajax( "api/sample/" + monitor + "/" + time)
		.done( goCircles )
		.done( goMarkers );
}

$( document ).ready(initialize);

function getUpdate() {
	var monitor = $( "#monitor option:selected").val();
	var thetime = $( "#time option:selected").val();
	showMonitor( monitor, thetime );
}

</script>
</head>
<body>

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

<div id="googleMap" style="position:absolute;left:0;top:0%;width:100%;height:100%;"></div>

</body>
</html>
