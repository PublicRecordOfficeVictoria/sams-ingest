<?php

include 'config.php';

$csvFile = fopen($csvFileName, 'r');
$hasSkippedFirstRow = false;

//Curl object for creating models
$ch = getMainCurlInstance();

//Curl object for testing url existences
$chTest = getTestCurlInstance();

//Create root container
createRootBasicContainer();

//LOOP FROM ROW TO ROW 
while(! feof($csvFile)){

  //Skip the first row for column names	
  if(!$hasSkippedFirstRow && $skipFirstRow){
  	$hasSkippedFirstRow = true;
  	continue;
  }	
  insertIntoRepo(fgetcsv($csvFile));
}


//CLEAN UP
curl_close($ch);
curl_close($chTest);
fclose($csvFile);


///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function createRootBasicContainer() {
	global $ch, $serverUrl, $rootUrl;
	$url = $serverUrl . $rootUrl;
	if(testUrlExistence($url) != 200) {
		$ttlFile = prepareRootPcdmObject(); 
		setRequestUrlAndFile($url, $ttlFile);
		$responseHttpcode = sendRequest();
		echo $responseHttpcode;
		if($responseHttpcode == 201){
			echo str_replace('/','', $rootUrl) . ' basic container is successfully created' . "\n";
			return true;
		}else{
			echo str_replace('/','', $rootUrl) . ' basic container is failed to create' . "\n";
			return false;
		}
	} else {
		echo str_replace('/','', $rootUrl) . ' basic container is already exist' . "\n";
		return true;
	}
}

//Frist layer of heirachical url e.g. /objects/#barcode
function createFirstLayerBasicContainer($firstLayer) {
	global $ch, $serverUrl, $rootUrl;
	$url = $serverUrl . $rootUrl;
	if(testUrlExistence($url) != 200){

	}
}


function insertIntoRepo($row) { 
	$fileName = $row[0];
	$barcode = $row[1];
	$imageOrder = $row[2];
	$type = $row[3];

	// $url = getUrl();
	// $contentType = getContentType();
	// $method = getMethod();
	// $pcdmObject = getPcdmObject();

	// curl_setopt($ch, CURLOPT_URL,            "http://url/url/url" );
	// curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
	// curl_setopt($ch, CURLOPT_POST,           1 );
	// curl_setopt($ch, CURLOPT_POSTFIELDS,     "body goes here" ); 
	// curl_setopt($ch, CURLOPT_HTTPHEADER,     array('Content-Type: text/plain')); 

	// $result=curl_exec ($ch);
}

function getMainCurlInstance() {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_PUT, 1);  
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);   
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: text/turtle')); 
	return $ch;
}

function getTestCurlInstance() {
	$chTest = curl_init();
	curl_setopt($chTest, CURLOPT_NOBODY  , 1); 
	return $chTest;
}

function testUrlExistence($url) {
	global $chTest;
	curl_setopt($chTest, CURLOPT_URL, $url);
	curl_exec($chTest);
	$httpcode = curl_getinfo($chTest, CURLINFO_HTTP_CODE);
	return $httpcode;
}

function prepareRootPcdmObject() {
	$temp = 'temp.ttl';
	$content = "
		@prefix pcdm: <http://pcdm.org/models#>
		 
		<> a pcdm:Object .
	";
	file_put_contents($temp, $content);
	$file = fopen($temp,'r'); 
	return  $file;
}

function setRequestUrlAndFile($url, $ttlFile){
	global $ch;
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_INFILE, $ttlFile);  
}

function sendRequest(){
	global $ch;
	curl_exec($ch);
	return curl_getinfo($ch, CURLINFO_HTTP_CODE);
}


?>