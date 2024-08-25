<?php

$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://online.anyway.com.ec/sendwpush.php',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS =>'{
   "metodo":"SmsEnvio",
   "id_cbm":1094,
   "id_transaccion":223423423423,
   "telefono":"8801705933999",
   "id_mensaje":29636,
   "dt_variable":1,
   "datos":{
      "valor":[
         "6589878",
         "2 minutos",
         "5/Jul a las 11:16"
      ]
   }
}',
  CURLOPT_HTTPHEADER => array(
    'Content-Type: application/json',
    'Authorization: Basic YmM1ODNiOGY2ZTI5Yjk3NjI1NGJiM2NiNWNmNDQyZjU6ZjUyYjU4YzM4OTFmYjM5ZDQyZmNiZTlmYjY1NzE1YzA='
  ),
));

$response = curl_exec($curl);

curl_close($curl);
echo "<pre>";
echo $response;
echo "<pre>";
die("the end");