<?php
set_time_limit(0);
$redownload = function ($id)
{
    // create a new cURL resource
    $ch = curl_init();

    // set URL and other appropriate options
    curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1/download/xiami/api.php');
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    // time out 
    curl_setopt($ch, CURLOPT_TIMEOUT, 1200);
    // post
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, ['action'=>'download', 'songId' => $id]); 

    // grab URL and pass it to the browser
    $response = curl_exec($ch);

    // close cURL resource, and free up system resources
    curl_close($ch);
    echo $response;
}
$dir = dirname(__FILE__). '/mp3/';
$d = dir($dir);
$songIds = [];
while ($entry = $d->read()) {
     if ($entry{0} === '.') {
        continue;
    }
    preg_match("/^\d+/", $entry, $match);
    if (empty($match) or empty($match[0])) {
        continue;
    }
    $songIds[] = $match[0];
}
$d->close();
foreach ($songIds as $id) {
    $redownload($id);
}
