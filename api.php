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
$get_file_name = function ($response)
{
    return $response->song->song_id. '.' . str_replace('/', '-', $response->song->song_name);
};
if (isset($_GET['debug'])) {
    $action = 'download';
    $_POST['songId'] = '1770221910';
}
header('Content-Type: application/json');
switch ($action) {
    case 'api': {
        $response = $get_song_info();
        if ($response) {
            $response = json_decode($response);
            if (empty($response->song->song_location)) {
                break;
            }
            $mp3file = "./mp3/".$get_file_name($response).".mp3";
            if (file_exists($mp3file)) {
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
        $imgFileExt = pathinfo($response->song->song_logo, PATHINFO_EXTENSION);
        $mp3file = "./mp3/".$get_file_name($response).".mp3";
        
        if (!file_exists($mp3file)) {
            // 先下文件
            $mp3data = $curl_download($response->song->song_location);
            file_put_contents($mp3file, $mp3data);
        }
        // 处理文件，亲
        /*
         * 返回 的 $response 信息
        {
          "status": "ok",
          "song": {
            "song_id": "3341658",
            "song_name": "\u7f8e\u3057\u304d\u3082\u306e",
            "song_location": "http:\/\/m5.file.xiami.com\/976\/54976\/301986\/3341658_10853734_l.mp3?auth_key=2b681780455fee58d0f2a0652b61354f-1403568000-0-null",
            "song_lrc": "http:\/\/img.xiami.net\/lyric\/58\/3341658_13995474539483.lrc",
            "song_logo": "http:\/\/img.xiami.net\/images\/album\/img76\/54976\/3019861370588827_2.jpg",
            "song_level": "-1",
            "album_id": "301986",
            "album_name": "Roman",
            "album_logo": "http:\/\/img.xiami.net\/images\/album\/img76\/54976\/3019861370588827_2.jpg",
            "artist_id": "54976",
            "artist_name": "Sound Horizon",
            "artist_logo": "http:\/\/img.xiami.net\/images\/artistlogo\/88\/13542539337988_1.jpg",
            "hasDown": true
          }
        }
        */
        // 处理MP3
        // v2
        require_once 'Zend/Media/Id3v2.php'; // or using autoload
        require_once 'Zend/Media/Id3/Frame/Tit2.php';
        require_once 'Zend/Media/Id3/Frame/Talb.php';
        require_once 'Zend/Media/Id3/Frame/Apic.php';
        require_once 'Zend/Media/Id3/Frame/Tcmp.php';
        require_once 'Zend/Media/Id3/Frame/Tso2.php';
        require_once 'Zend/Media/Id3/Frame/Tmcl.php';
        require_once 'Zend/Media/Id3/Frame/Tope.php';
        require_once 'Zend/Media/Id3/Frame/Tpe1.php';

        $id3 = new Zend_Media_Id3v2();
        // tit2
        $tit2 = new Zend_Media_Id3_Frame_Tit2();
        $tit2->setText($response->song->song_name);
        $id3->addFrame($tit2);
        // talb
        $talb = new Zend_Media_Id3_Frame_Talb();
        $talb->setText($response->song->album_name);
        $id3->addFrame($talb);
        // tcmp
        $tcmp = new Zend_Media_Id3_Frame_Tcmp();
        $tcmp->setText($response->song->artist_name);
        $id3->addFrame($tcmp);
        // Tso2
        $tso2 = new Zend_Media_Id3_Frame_Tso2();
        $tso2->setText($response->song->artist_name);
        $id3->addFrame($tso2);
        // Tmcl
        $tmcl = new Zend_Media_Id3_Frame_Tmcl();
        $tmcl->setText($response->song->artist_name);
        $id3->addFrame($tmcl);
        // Tope
        $tope = new Zend_Media_Id3_Frame_Tope();
        $tope->setText($response->song->artist_name);
        $id3->addFrame($tope);
        // Tpe1
        $tpe1 = new Zend_Media_Id3_Frame_Tpe1();
        $tpe1->setText($response->song->artist_name);
        $id3->addFrame($tpe1);
        // pic
        $apic = new Zend_Media_Id3_Frame_Apic();
        if ($imgFileExt === 'jpg') {
            $apic->setMimeType('image/jpeg');
        } else {
            $apic->setMimeType('image/'. $imgFileExt);
        }
        // 远程获取图片
        $imgdata = $curl_download($response->song->song_logo);
        $apic->setImageData($imgdata); 
        $apic->setImageType(3); 
        $id3->addFrame($apic); 
        // add
        $id3->write($mp3file);
        // 移除图片
        $imgFile = "./mp3/".$get_file_name($response).".{$imgFileExt}";
        if (file_exists($imgFile)) {
            unlink($imgFile);
        }
        // out
        echo '{"status":"1"}';
        break;
    }
    default:
        # code...
        break;
}