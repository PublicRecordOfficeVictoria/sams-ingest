<?php

include 'config.php';
include 'pcdm_generator.php';

//Curl object for testing url existences
$chTest = getTestCurlInstance();

/*  disable pruning option
if(isset($argv[1])) {
	if($argv[1] == 'prune') {
		prune();
		echo 'All data is deleted' . "\n";
		return;
	}
}
*/

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

//Initialise log counter
$counter = 0;

//LOOP FROM ROW TO ROW 
while(! feof($csvFile)){

  //Skip the first row for column names	
  if(!$hasSkippedFirstRow && $skipFirstRow){
  	$hasSkippedFirstRow = true;
  	fgetcsv($csvFile, 1000);
  	continue;
  }
  // insert a counter into the log
  $counter++;
  echo PHP_EOL . $counter . PHP_EOL;
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
			echo $url . ' is successfully created' . PHP_EOL;
			return 1;
		}else{
			echo $url . ' is failed to create' . PHP_EOL;
			return 0;
		}
	} else {
		// echo $url . ' basic container already exists' . PHP_EOL;
		return -1;
	}
}

function createDirectContainer($url, $ldpUrl, $type){
	if(testUrlExistence($url) != 200) {
		$ttlFile = prepareRdfObject($type, $ldpUrl); 
		setRequestUrlAndFile($url, $ttlFile);
		$responseHttpcode = sendRequest();
		if($responseHttpcode == 201){
			echo $url . ' successfully created' . PHP_EOL;
			return 1;
		}else{
			echo $url . ' creation failed' . PHP_EOL;
			return 0;
		}
	} else {
		// echo $url . ' direct container already exists' . PHP_EOL;
		return -1;
	}
}

function createFile($url, $name, $mimeType) {
	if(testUrlExistence($url) != 200) {
		$file = prepareFile($name); 
		setRequestUrlAndFile($url, $file, $mimeType);
		$responseHttpcode = sendRequest();
		if($responseHttpcode == 201){
			echo $url . ' is successfully created at ' . date('Y-m-d H:i:s') . ' (upload PC timestamp)'. PHP_EOL;
			return 1;
		}else{
			echo $url . ' was not created at ' . date('Y-m-d H:i:s') . ' (upload PC timestamP).'. PHP_EOL . 'HTTP response code: ' . $responseHttpcode . PHP_EOL;
			return 0;
		}
	} else {
		echo $name . ' file already exists' . PHP_EOL;
		return -1;
	}
}

function insertIntoRepo($row) { 
	global $serverUrl, $rootUrl, $firstPrefix, $firstPrefixValue, $secondPrefix, $pathToFiles;
  if ($row != NULL) {
	$fileName = $row[0];
	$barcode = $row[1];
	$imageOrder = $row[2];
	$type = $row[3];
	$unit_item = $row[4];
	$full_partial = $row[5];
	
	// Create Basic container representing the record using pair tree from PID/barcode
    //  each level of the pair tree structure is a basic container
	$url = $serverUrl . $rootUrl;
	$pt_barcode = str_split($barcode, 4);
	foreach ($pt_barcode as $fragment) {
		$url = $url . '/' . $fragment;
		createBasicContainer($url);
	}

	//Link container to the ACM entity it 'documents'
	$Object_text = "http://access.prov.vic.gov.au/public/component/daPublicBaseContainer?component=daView" . ucfirst(strtolower($unit_item)) . "&entityId=" . $barcode;
	$meta_flag = 1;
	updateFile($url, $Object_text, $meta_flag);

	// Add a property indicating whether the images are a full or partial digitisation of the original
	$Object_text = ucfirst(strtolower($full_partial)) . " copy";
	$meta_flag = 2;
	updateFile($url, $Object_text, $meta_flag);
	
	$meta_flag = 0;

	//Create SecondLayerDirectContainer
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

	// get the height and width of the image/file about to be uploaded
	$pathToFile = $pathToFiles . '/' . $fileName;
	unset($dimensions);
	exec('magick identify -ping -format "%w %h" ' . $pathToFile,$dimensions);
    if (isset($dimensions)) {
		$Object_text = $dimensions[0];
	} else {
		echo PHP_EOL . 'Can\'t find that filename in the location you have specified.  Check that the CSV file and config file are correct!';
	}
	
	//Upload the actual file 
	$url .= '/' . $fileName; //e.g.  /records/100/images/image1/files/9210288-21398102-3112.jpg
	createFile($url, $fileName, $type);
	updateFile($url, $Object_text, $meta_flag);

	//and update the created resource with the height and width properties
	// SUFFIX MAY ALREADY HAVE BEEN ADDED TO $url IN THE PREPARE SPARQL FUNCTION
	$url .= '/fcr:metadata';
	$meta_flag = 3;
	updateFile($url, $Object_text, $meta_flag);
  }
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
	$curl_error_message = curl_error($chTest);
	if ($httpcode == 0){
		echo PHP_EOL . "Error message is: " . $curl_error_message . PHP_EOL;
	}
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

function prepareSparql($meta_flag, $Object_text) {
	global $pcdm;
	$temp = 'temp.ru';

	switch ($meta_flag) {
	    case 0:
	        $content = $pcdm->pcdmFile();
	        break;
	    case 1:
	        $content = $pcdm->cidocDocument($Object_text);
	        break;
	    case 2:
	        $content = $pcdm->cidocNote($Object_text);
	        break;
		case 3:
		    $wh = explode(' ',$Object_text);
            $width = $wh[0];
            $height= $wh[1];
		    $content = $pcdm->exifWidthHeight($width,$height);
			break;
	}

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

function updateFile($url, $Object_text, $meta_flag) {
	global $chUpdate;
	$ruFile = prepareSparql($meta_flag, $Object_text);
	if($meta_flag == 0) {
	    $url .= '/fcr:metadata';
	}
	curl_setopt($chUpdate, CURLOPT_URL, $url);
	curl_setopt($chUpdate, CURLOPT_INFILE, $ruFile);
	curl_exec($chUpdate);
	$responseHttpcode = curl_getinfo($chUpdate, CURLINFO_HTTP_CODE);
	echo PHP_EOL . "updateFile HTTP response code is: " . $responseHttpcode . PHP_EOL;
}

/* Disable pruning option
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
*/

?>
