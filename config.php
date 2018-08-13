<?php

// Name and path of the CSV file (manifest file) which contains the list of image files to upload
$csvFileName = 'E:\SAMSupload\00516-v2.csv';

// If your manifest file has a 'header' row containing the names of the fields,
// set the following to 'true'
$skipFirstRow = true;

// Path to folder which contains the image files to upload.
$pathToFiles = 'D:\JP2s\516-complete';

// Name of Fedora repository to up-load to (either content.prov.vic.gov.au
// or dev-content.prov.vic.gov.au for testing)
$serverUrl = 'https://dev-content.prov.vic.gov.au/rest';

$rootUrl = '/records';
$firstPrefix = '/images';
$firstPrefixValue = '/';
$secondPrefix = '/files';

?>
