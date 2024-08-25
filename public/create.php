<?php

// require_once './vendor/autoload.php';
require_once './secrets.php';

$HYPER_SWITCH_API_KEY = $hyperswitch_secret_key;
$HYPER_SWITCH_API_BASE_URL = "";

function calculateOrderAmount(array $items): int
{
    // Replace this constant with a calculation of the order's amount
    // Calculate the order total on the server to prevent
    // people from directly manipulating the amount on the client
    return 1400;
}




try {
    $jsonStr = file_get_contents('php://input');
    $jsonObj = json_decode($jsonStr);

    $payload = json_encode(array(
        "amount" => calculateOrderAmount((array)$jsonObj->items),
        "currency" => "USD",
        "customer_id" => "hyperswitch_customer"
    ));

    $ch = curl_init($HYPER_SWITCH_API_BASE_URL);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Accept: application/json',
        'api-key: ' . $HYPER_SWITCH_API_KEY
    ));

    $responseFromAPI = curl_exec($ch);
    if ($responseFromAPI === false) {
        $output = json_encode(array("error" => curl_error($ch)), JSON_THROW_ON_ERROR);
        http_response_code(403);
    } else {
        $decoded_response = json_decode($responseFromAPI, true);
        $output = array("client_secret" => $decoded_response['client_secret']);
        echo json_encode($output, JSON_THROW_ON_ERROR);
    }

    curl_close($ch);
} catch (Exception $e) {
    echo json_encode(array("error" => $e->getMessage()), JSON_THROW_ON_ERROR);
    http_response_code(403);
}
