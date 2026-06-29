<?php

require_once "../weather/weather_engine.php";

$lat = -0.3476;
$lon = 32.5825;
$crop = "maize";

$data = weatherEngine($lat,$lon,$crop);

foreach($data['alerts'] as $alert){

    echo "ALERT: ".$alert.PHP_EOL;

}