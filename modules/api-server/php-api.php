<?php

session_start();

// api user
$apiUser = $_POST['apiuser'];
// api auth nya si user
$apiAuth = "123456";


function mem_usage(){

    $free = shell_exec('free');
    $free = (string)trim($free);
    $free_arr = explode("\n", $free);
    $mem = explode(" ", $free_arr[1]);
    $mem = array_filter($mem);
    $mem = array_merge($mem);
    $memory_usage = $mem[2]/$mem[1]*100;

    return $memory_usage;
}


function onRequestStart() {
	$dat = getrusage();
	define('PHP_TUSAGE', microtime(true));
	define('PHP_RUSAGE', $dat["ru_utime.tv_sec"]*1e6+$dat["ru_utime.tv_usec"]);
}
 
function getCpuUsage() {
    $dat = getrusage();
    $dat["ru_utime.tv_usec"] = ($dat["ru_utime.tv_sec"]*1e6 + $dat["ru_utime.tv_usec"]) - PHP_RUSAGE;
    $time = (microtime(true) - PHP_TUSAGE) * 1000000;
 
    // cpu per request
    if($time > 0) {
        $cpu = sprintf("%01.2f", ($dat["ru_utime.tv_usec"] / $time) * 100);
    } else {
        $cpu = '0.00';
    }
 
    return $cpu;
}


// function untuk membuat file temp kode program
function createFileTemp($filename){

	// file temp kode program digunakan 
	// untuk membuat file duplikat yg bisa mengakomodasi input stdin
	// NB: khusus untuk Python

	$fp1 = fopen($filename, "r");

	$filenameTemp = str_replace(".php", "_temp.php", $filename);

	$fp2 = fopen($filenameTemp, "w");
	while (!feof($fp1)){
	    $str = fgets($fp1);
	    fwrite($fp2, $str);
	}

	fclose($fp1);
	fclose($fp2);
}

// jika api auth nya si user sama 
if ($_POST['apiauth'] == $apiAuth){
	onRequestStart();

	// setting direktori temp file kode program
	$dir = "temp_files/php-files/";

	// baca kode program dari POST
	$codes = rawurldecode($_POST['code']);

	// mengenerate nama file unik kode program asli dan temp
	// berdasarkan session ID

	$fileName = session_id();
	$fullFileName = $dir.$fileName.".php";
	$fullFileNameTemp = str_replace(".php", "_temp.php", $fullFileName);

	// mengcreate file kode program asli
	$fp = fopen($fullFileName, 'w');

	fwrite($fp, $codes);

	// jika program akan diuji call functionnya maka tambahkan 
	// statement call function
	if (isset($_POST['callFunction'])){
		if ($_POST['callFunction'] != ""){
			fwrite($fp, "\n".$_POST['callFunction']);
		}
	}
	fclose($fp);

	// membuat file kode program temp
	createFileTemp($fullFileName);

	// proses menjalankan kode program temp via CLI dengan PIPE
	$descriptorspec = array(   
		   0 => array("pipe", "r"),   
		   1 => array("pipe", "w"),   
		);
		
	$process = proc_open("php ".$fullFileNameTemp." 2>&1", $descriptorspec, $pipes);

	if (is_resource($process)) {

		// membaca stdin input program
		if (isset($_POST['input'])){  	    
			$input = $_POST['input'];
			fwrite($pipes[0], $input);  
		    fclose($pipes[0]);
		} 
		
		// membaca stdout output program    
		$output = stream_get_contents($pipes[1]);

		fclose($pipes[1]);  
	  	proc_close($process);    
	}	

	// inisialisasi status syntax error 
	// mula-mula dianggap tidak ada error (nilai -> 0)

	$errorStatus = 0;

	// proses membaca output perbaris 
	$splitOutput = explode("\r\n", $output);

	foreach ($splitOutput as $str) {
		// jika ditemukan ada baris Error di output
		// maka status syntax error -> 1
		if (substr_count($str, "Error") > 0) {
			$errorStatus = 1;
			// baca error lengkap
			$errorDetail = $str;
			// baca tipe error
			$split = explode(":", $str);
			$errorType = $split[0];				
		}
	}

	// response jika tidak ada error syntax
	if ($errorStatus == 0) {
		if (isset($_POST['output'])) {
			if ($_POST['output'] != ''){
				if ($_POST['output'] == $output) {
					// jika output yg diharapkan = output program
					// maka outputStatus -> 1 (program benar)
					$response = array("output" => $output, "errorStatus" => $errorStatus, "outputStatus" => 1, "cpuusage" => getCpuUsage(), "memusage" => mem_usage());	
				} else {
					// jika output yg diharapkan != output program
					// maka outputStatus -> 0 (program salah)
					$response = array("output" => $output, "errorStatus" => $errorStatus, "outputStatus" => 0, "cpuusage" => getCpuUsage(), "memusage" => mem_usage());
				}
			} else {
				// response jika tidak dibandingkan dengan output yg diharapkan
				$response = array("output" => $output, "errorStatus" => $errorStatus, "cpuusage" => getCpuUsage(), "memusage" => mem_usage());	
			}
			
		} else {
			// response jika tidak dibandingkan dengan output yg diharapkan
			$response = array("output" => $output, "errorStatus" => $errorStatus, "cpuusage" => getCpuUsage(), "memusage" => mem_usage());
		}
	} else {
		// response jika terjadi syntax error
		$response = array("output" => $output, "errorStatus" => $errorStatus, "errorType" => $errorType, "errorDetail" => $errorDetail, "cpuusage" => getCpuUsage(), "memusage" => mem_usage());
	}

	// hapus file source code
	
	//unlink($fullFileNameTemp);
	unlink($fullFileName);
} else {
	$response = array("output" => "API Auth Error");
}	

// generate JSON dari response
$x = json_encode($response);
echo($x);
?>