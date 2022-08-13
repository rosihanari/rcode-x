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
$url = "http://rosihanari.net/api/php-api.php";

$time1 = microtime(true);
$reqtime = date("Y-m-d H:i:s");

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

$time2 = microtime(true);

$cpuusage = $output->cpuusage;
$memusage = $output->memusage;
$out = $output->output;
$clientID = 1;
$exectime = $time2 - $time1;
$trial = $_GET['trial'];

echo $out;

$db = mysqli_connect("localhost", "root", "", "apitest");
$query = mysqli_query($db, "INSERT INTO dataset (ClientID, ReqTime, CPUUsage, MemUsage, ExecTime, TrialNum, Output) VALUES ('$clientID', '$reqtime', '$cpuusage', '$memusage', '$exectime', '$trial', '$out')");
?>