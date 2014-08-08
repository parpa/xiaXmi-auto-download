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
};
$dir = dirname(__FILE__). '/mp3/';
$d = dir($dir);
$songIds = [];
while (false !== ($entry = $d->read())) {
     if ($entry{0} === '.') {
        continue;
    }
    preg_match("/^\d+/", $entry, $match);
    if (empty($match) or empty($match[0])) {
        continue;
    }
    $songIds[$match[0]] = $match[0];
}
$d->close();
foreach ($songIds as $id) {
    $redownload($id);
}
// 移除冗余文件
$dir = dirname(__FILE__). '/mp3/';
$d = dir($dir);
$songIds = [];
while (false !== ($entry = $d->read())) {
     if ($entry{0} === '.') {
        continue;
    }
    preg_match("/^\d+/", $entry, $match);
    if (empty($match) or empty($match[0])) {
        continue;
    }
    if (isset($songIds[$match[0]])) {
        $f1 = $dir. $songIds[$match[0]];
        $f2 = $dir. $entry;
        if (filemtime($f1) > filemtime($f2)) {
            unlink($f2);
        } else {
            unlink($f1);
        }
    }
    $songIds[$match[0]] = $entry;
}
$d->close();