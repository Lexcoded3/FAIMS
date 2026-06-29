<?php

require_once "weather_fetch.php";
require_once "alerts.php";
require_once "irrigation.php";
require_once "planting.php";
require_once "risk_analyzer.php";
require_once "disease_predictor.php";
require_once "forecast_analyzer.php";

function weatherEngine($lat, $lon, $crop){

    $weather = fetchWeather($lat, $lon);

    if(!$weather){
        return false;
    }

    $alerts = generateAlerts($weather);

    $irrigation = irrigationAdvice($weather);

    $planting = plantingAdvice($weather, $crop);

    $risks = analyzeRisk($weather, $crop);

    $disease = predictDisease($weather, $crop);

    $forecast = farmForecast($weather,$crop);

    return [
    "weather"=>$weather,
    "alerts"=>$alerts,
    "irrigation"=>$irrigation,
    "planting"=>$planting,
    "risks"=>$risks,
    "disease"=>$disease,
    "forecast"=>$forecast
];
}