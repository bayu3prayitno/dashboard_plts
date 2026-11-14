<?php

return [
    // Broker MQTT yang menerima data dari Gateway (PRD 3 & 5.2)
    'host' => env('MQTT_HOST', '127.0.0.1'),
    'port' => (int) env('MQTT_PORT', 1883),
    'username' => env('MQTT_USERNAME'),
    'password' => env('MQTT_PASSWORD'),
    'tls' => (bool) env('MQTT_TLS', false),
    'client_id' => env('MQTT_CLIENT_ID', 'omahiot-subscriber'),

    // Topik: omahiot/gateway/{kode_gateway}/data, mis. omahiot/gateway/GW-001/data
    // Kode gateway diambil dari segmen ke-3 topik.
    'topic' => env('MQTT_TOPIC', 'omahiot/gateway/+/data'),
];
