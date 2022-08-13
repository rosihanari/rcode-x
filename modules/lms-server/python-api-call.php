<?php

// baca data dari test.html
$input  = $_POST['input'];
$output = $_POST['output'];
$code   = $_POST['code'];
$callFunction = $_POST['callFunction'];

// set API user and passwd
$apiUser = "rosihanari";
$apiAuth = "123456";

// set web service URL 
$url = "http://rosihanari.net/api/python-api.php";

// request POST ke web service
$curlHandle = curl_init();
curl_setopt($curlHandle, CURLOPT_URL, $url);
curl_setopt($curlHandle, CURLOPT_POSTFIELDS, "apiuser=".$apiUser."&apiauth=".$apiAuth."&input=".$input."&output=".$output."&callFunction=".$callFunction."&code=".rawurlencode($code));
curl_setopt($curlHandle, CURLOPT_HEADER, 0);
curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($curlHandle, CURLOPT_TIMEOUT,30);
curl_setopt($curlHandle, CURLOPT_POST, 1);
$response = curl_exec($curlHandle);
curl_close($curlHandle);

// mengubah JSON response ke dalam array asosiatif
$output = json_decode($response);
// menampilkan output
print_r($output);
?>