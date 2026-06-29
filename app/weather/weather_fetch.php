<?php

function fetchWeather($lat, $lon){

    $apiKey = "21480b77eb33ed197e62b8e6a1422a57";

    $url = "https://api.openweathermap.org/data/2.5/forecast?lat={$lat}&lon={$lon}&appid={$apiKey}&units=metric";

    $response = file_get_contents($url);

    if(!$response){
        return false;
    }

    return json_decode($response, true);
}