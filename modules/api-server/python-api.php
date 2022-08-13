<?php

session_start();

// api user
$apiUser = $_POST['apiuser'];
// api auth nya si user
$apiAuth = "123456";

// function untuk membuat file temp kode program
function createFileTemp($filename){

	// file temp kode program digunakan 
	// untuk membuat file duplikat yg bisa mengakomodasi input stdin
	// NB: khusus untuk Python

	$fp1 = fopen($filename, "r");

	$filenameTemp = str_replace(".py", "_temp.py", $filename);

	$fp2 = fopen($filenameTemp, "w");
	while (!feof($fp1)){
	    $str = fgets($fp1);
	    if (substr_count($str, "input(") > 0){
	        $tabCount = substr_count($str, "    ");
	        $pecah = explode("=", $str);
	        $var = str_replace("    ", "", $pecah[0]);
	        $var = str_replace(" ", "", $var);
	        fwrite($fp2, $str);
	        $x = "";
	        for($i=0; $i<$tabCount; $i++){
	            $x .= "    ";
	        }
	        fwrite($fp2, $x."print(".$var.")\n");
	    } else {
	        fwrite($fp2, $str);
	    }
	}

	fclose($fp1);
	fclose($fp2);
}

// jika api auth nya si user sama 
if ($_POST['apiauth'] == $apiAuth){


	// setting direktori temp file kode program
	$dir = "temp_files/python-files/";

	// baca kode program dari POST
	$codes = rawurldecode($_POST['code']);

	// mengenerate nama file unik kode program asli dan temp
	// berdasarkan session ID

	$fileName = session_id();
	$fullFileName = $dir.$fileName.".py";
	$fullFileNameTemp = str_replace(".py", "_temp.py", $fullFileName);

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
		
	$process = proc_open("python ".$fullFileNameTemp." 2>&1", $descriptorspec, $pipes);

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
					$response = array("output" => $output, "errorStatus" => $errorStatus, "outputStatus" => 1);	
				} else {
					// jika output yg diharapkan != output program
					// maka outputStatus -> 0 (program salah)
					$response = array("output" => $output, "errorStatus" => $errorStatus, "outputStatus" => 0);
				}
			} else {
				// response jika tidak dibandingkan dengan output yg diharapkan
				$response = array("output" => $output, "errorStatus" => $errorStatus);	
			}
			
		} else {
			// response jika tidak dibandingkan dengan output yg diharapkan
			$response = array("output" => $output, "errorStatus" => $errorStatus);
		}
	} else {
		// response jika terjadi syntax error
		$response = array("output" => $output, "errorStatus" => $errorStatus, "errorType" => $errorType, "errorDetail" => $errorDetail);
	}

	// hapus file source code
	
	unlink($fullFileNameTemp);
	unlink($fullFileName);
} else {
	$response = array("output" => "API Auth Error");
}	

// generate JSON dari response
$x = json_encode($response);
echo($x);
?>