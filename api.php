<?php
$action = isset($_POST['action']) ?$_POST['action'] : '';
$curl_download = function ($url) {
    // create a new cURL resource
    $ch = curl_init();

    // set URL and other appropriate options
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    // grab URL and pass it to the browser
    $response = curl_exec($ch);

    // close cURL resource, and free up system resources
    curl_close($ch);
    return $response;
};
$get_song_info = function () use($curl_download)
{
    $songId = $_POST['songId'];
    $songId = intval($songId);
    if ($songId < 1) {
        return '';
    }
    return $curl_download("http://api.xiami.com/app/android/song?id={$songId}");
};
switch ($action) {
    case 'api': {
        $response = $get_song_info();
        if ($response) {
            $response = json_decode($response);
            if (empty($response->song->song_location)) {
                break;
            }
            $ext = pathinfo($response->song->song_logo, PATHINFO_EXTENSION);
            $imgFile = "./mp3/{$response->song->song_id}.{$response->song->song_name}.{$ext}";
            header('Content-Type: application/json');
            if (file_exists($imgFile)) {
                $response->song->hasDown = true;
            } else {
                $response->song->hasDown = false;
            }
            $response = json_encode($response);
        }
        header('Content-Type: application/json');
        echo $response;
        break;
    }
    case 'download': {
        $response = $get_song_info();
        if (!$response) {
            break;
        }
        $response = json_decode($response);
        if (empty($response->song->song_location)) {
            break;
        }
        $ext = pathinfo($response->song->song_logo, PATHINFO_EXTENSION);
        $imgFile = "./mp3/{$response->song->song_id}.{$response->song->song_name}.{$ext}";
        header('Content-Type: application/json');
        if (file_exists($imgFile)) {
            echo '{"status":"1"}';
            break;
        }
        // 先下文件
        $mp3data = $curl_download($response->song->song_location);
        file_put_contents("./mp3/{$response->song->song_id}.{$response->song->song_name}.mp3", $mp3data);
        // 再下图片
        $imgdata = $curl_download($response->song->song_logo);
        file_put_contents($imgFile, $imgdata);
        // out
        echo '{"status":"1"}';
        break;
    }
    default:
        # code...
        break;
}