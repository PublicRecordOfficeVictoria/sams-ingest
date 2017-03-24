<?php

include 'config.php';
include 'pcdm_generator.php';

//Curl object for testing url existences
$chTest = getTestCurlInstance();

if(isset($argv[1])) {
	if($argv[1] == 'prune') {
		prune();
		echo 'All data is deleted' . "\n";
		return;
	}
}

$csvFile = fopen($csvFileName, 'r');
$hasSkippedFirstRow = false;

//Curl object for creating models
$ch = getMainCurlInstance();

//Curl object for update file
$chUpdate = getUpdateCurlInstance();

//Pcdm object generator 
$pcdm = new pcdm_generator();

//Create root container
createBasicContainer($serverUrl . $rootUrl);

//LOOP FROM ROW TO ROW 
while(! feof($csvFile)){

  //Skip the first row for column names	
  if(!$hasSkippedFirstRow && $skipFirstRow){
  	$hasSkippedFirstRow = true;
  	fgetcsv($csvFile, 1000);
  	continue;
  }	
  insertIntoRepo(fgetcsv($csvFile));
}


//CLEAN UP
curl_close($ch);
curl_close($chTest);
fclose($csvFile);


///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function createBasicContainer($url) {  
	if(testUrlExistence($url) != 200) {
		$ttlFile = prepareRdfObject('basic'); 
		setRequestUrlAndFile($url, $ttlFile);
		$responseHttpcode = sendRequest();
		if($responseHttpcode == 201){
			echo $url . ' is successfully created' . "\n";
			return 1;
		}else{
			echo $url . ' is failed to create' . "\n";
			return 0;
		}
	} else {
		//echo $name . ' basic container is already exist' . "\n";
		return -1;
	}
}

function createDirectContainer($url, $ldpUrl, $type){
	if(testUrlExistence($url) != 200) {
		$ttlFile = prepareRdfObject($type, $ldpUrl); 
		setRequestUrlAndFile($url, $ttlFile);
		$responseHttpcode = sendRequest();
		if($responseHttpcode == 201){
			echo $url . ' is successfully created' . "\n";
			return 1;
		}else{
			echo $url . ' is failed to create' . "\n";
			return 0;
		}
	} else {
		//echo $name . ' direct container is already exist' . "\n";
		return -1;
	}
}

function createFile($url, $name, $mimeType) {
	if(testUrlExistence($url) != 200) {
		$file = prepareFile($name); 
		setRequestUrlAndFile($url, $file, $mimeType);
		$responseHttpcode = sendRequest();
		if($responseHttpcode == 201){
			echo $url . ' is successfully created' . "\n";
			return 1;
		}else{
			echo $url . ' is failed to create' . "\n";
			return 0;
		}
	} else {
		//echo $name . ' file is already exist' . "\n";
		return -1;
	}
}

function insertIntoRepo($row) { 
	global $serverUrl, $rootUrl, $firstPrefix, $firstPrefixValue, $secondPrefix;
	$fileName = $row[0];
	$barcode = $row[1];
	$imageOrder = $row[2];
	$type = $row[3];

	//Create FirstlayerBasicContainer
	$url = $serverUrl . $rootUrl . '/' . $barcode; //e.g.  /records/100
	createBasicContainer($url);

	//Create SecondLayerDirectorContainer
	$ldpUrl = $url;
	$url .= $firstPrefix;  //e.g.  /records/100/images 
	createDirectContainer($url, $ldpUrl, 'direct');

	//Create SecondLayerBasicContainer 
	//e.g.  /records/100/images/image{{image_order}} or /records/100/images/image1*
	if($imageOrder != ''){
		$suburl = $firstPrefixValue . $imageOrder;  
		createBasicContainer($url . $suburl);
	}else{
		$count = 0;
		$suburl = NULL;
		do {
			$count += 1;
			$suburl = $firstPrefixValue . $count;
			$result = createBasicContainer($url . $suburl);
		}while($result == -1); //If the name is taken count plue one
	}

	//Create SecondLayerDirectContainer
	$url = $url . $suburl;
	$ldpUrl = $url;
	$url .= $secondPrefix; //e.g.  /records/100/images/image1/files
	createDirectContainer($url, $ldpUrl, 'direct_file');

	//Upload the actual file 
	$url .= '/' . $fileName; //e.g.  /records/100/images/image1/files/9210288-21398102-3112.jpg
	createFile($url, $fileName, $type);
	updateFile($url);

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

function getUpdateCurlInstance() {
	$chUpdate = curl_init();
	curl_setopt($chUpdate, CURLOPT_PUT, 1);  
	curl_setopt($chUpdate, CURLOPT_RETURNTRANSFER, 1);   
	curl_setopt($chUpdate, CURLOPT_CUSTOMREQUEST, 'PATCH'); 
	curl_setopt($chUpdate, CURLOPT_HTTPHEADER, array('Content-type: application/sparql-update')); 
	return $chUpdate;
}

function testUrlExistence($url) {
	global $chTest;
	curl_setopt($chTest, CURLOPT_URL, $url);
	curl_exec($chTest);
	$httpcode = curl_getinfo($chTest, CURLINFO_HTTP_CODE);
	return $httpcode;
}

function prepareFile($name){
	global $pathToFiles;
	$pathToFile = $pathToFiles . '/' . $name;
	$file = fopen($pathToFile,'r'); 
	return  $file;
}

function prepareRdfObject($type, $url = null) {
	global $pcdm;
	$temp = 'temp.ttl';
	switch ($type) {
	    case 'basic':
	        $content = $pcdm->pcdmObject();
	        break;
	    case 'direct':
	        $content = $pcdm->ldpDirect($url);
	        break;
	    case 'direct_file':
	        $content = $pcdm->ldpDirectFiles($url);
	        break;    
	} 
	file_put_contents($temp, $content);
	$file = fopen($temp,'r'); 
	return  $file;
}

function prepareSparql() {
	global $pcdm;
	$temp = 'temp.ru';
	$content = $pcdm->pcdmFile();
	file_put_contents($temp, $content);
	$file = fopen($temp, 'r');
	return $file;
}

function setRequestUrlAndFile($url, $file, $mimeType = 'text/turtle'){
	global $ch;
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: ' . $mimeType)); 
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_INFILE, $file);  
}

function sendRequest($action = NULL){
	global $ch;
	curl_exec($ch);
	return curl_getinfo($ch, CURLINFO_HTTP_CODE);
}

function updateFile($url) {
	global $chUpdate;
	$ruFile = prepareSparql(); 
    $url .= '/fcr:metadata';  
    curl_setopt($chUpdate, CURLOPT_URL, $url);
	curl_setopt($chUpdate, CURLOPT_INFILE, $ruFile);  
	curl_exec($chUpdate);
	$responseHttpcode = curl_getinfo($chUpdate, CURLINFO_HTTP_CODE);
}

//Clean up all created data
function prune() {
	global $serverUrl, $rootUrl;
	$url = $serverUrl . $rootUrl;
	$url_tuombstone = $url . '/fcr:tombstone';
	if(testUrlExistence($url) == 200){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_exec($ch);
		curl_setopt($ch, CURLOPT_URL, $url_tuombstone);
		curl_exec($ch);
	}
}

?>