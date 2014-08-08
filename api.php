<?php
$action = isset($_POST['action']) ?$_POST['action'] : '';
$rand_agent = function ()
{
    $agents = ["Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_2) AppleWebKit/537.75.14 (KHTML, like Gecko) Version/7.0.3 Safari/537.75.14","Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_0) AppleWebKit/537.75.14 (KHTML, like Gecko) Version/7.0 Safari/537.75.14","Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_4) AppleWebKit/537.75.14 (KHTML, like Gecko) Version/6.1 Safari/537.75.14","Mozilla/5.0 (iPhone; CPU iPhone OS 7_0 like Mac OS X) AppleWebKit/537.51.1 (KHTML, like Gecko) Version/7.0 Mobile/11A465 Safari/9537.53","Mozilla/5.0 (iPod; CPU iPhone OS 7_0 like Mac OS X) AppleWebKit/537.51.1 (KHTML, like Gecko) Version/7.0 Mobile/11A465 Safari/9537.53","Mozilla/5.0 (iPad; CPU OS 7_0 like Mac OS X) AppleWebKit/537.51.1 (KHTML, like Gecko) Version/7.0 Mobile/11A465 Safari/9537.53","Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.2; Win64; x64; Trident/6.0)","Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0)","Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.0; Trident/4.0)","Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0)","Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/29.0.1547.57 Safari/537.36","Mozilla/5.0 (Windows NT 6.2; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/29.0.1547.57 Safari/537.36","Mozilla/5.0 (Macintosh; Intel Mac OS X 10.9; rv:23.0) Gecko/20100101 Firefox/23.0","Mozilla/5.0 (Windows NT 6.2; WOW64; rv:23.0) Gecko/20100101 Firefox/23.0"];
    return $agents[array_rand($agents)];
};
$rand_ip = function ()
{
    return '123.125.'. rand(1,254). '.'.  rand(2,250);
};
$curl_download = function ($url) use($rand_agent, $rand_ip) {
    // create a new cURL resource
    $ch = curl_init();

    // set URL and other appropriate options
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    // CURLOPT_HTTPHEADER
    $ip = $rand_ip();
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'User-Agent: ' . $rand_agent(),
        'X-Forwarded-For: ' . $ip,
        'Client-IP: ' . $ip,
    ));
    
    // time out 
    curl_setopt($ch,CURLOPT_TIMEOUT,1200);

    // grab URL and pass it to the browser
    $response = curl_exec($ch);

    // close cURL resource, and free up system resources
    curl_close($ch);
    return $response;
};
$DecryptionLocation = function ($a)
{
    preg_match('/^\d+/', $a, $match);
    if (empty($match)) {
        return '';
    }
    $d = $match[0];
    $a = substr($a, strlen($d));
    $d = intval($d);
    if ($d < 1) {
        return '';
    }
    $len = ceil(strlen($a)/$d);
    $len2 = $len * (strlen($a)%$d) + 1;
    $tmp = [];
    for ($j=0,$i=0; $i < strlen($a); $i++,$j++) {
        if (!isset($tmp[$j])) {
            $tmp[$j] = '';
        }
        if ($i == $len2) {
            $len -=1;
        }
        if ($j == $len) {
            $j = 0;
        }
        $tmp[$j] .= $a{$i};
    }
    $output = join('', $tmp);
    $output = urldecode($output);
    $output = str_replace('^', '0', $output);
    return $output;
};
$get_song_info = function () use($curl_download, $DecryptionLocation)
{
    $songId = $_POST['songId'];
    $songId = intval($songId);
    if ($songId < 1) {
        return '';
    }
    $response = $curl_download("http://www.xiami.com/song/playlist/id/{$songId}");
    if (!$response) {
        return null;
    }
    $response = preg_replace("/<\!\[CDATA\[(.*?)\]\]>/s", "$1", $response);
    $response = simplexml_load_string($response);
    if (!$response or empty($response->trackList->track)) {
        return null;
        break;
    }
    $response = $response->trackList->track;
    if (empty($response->location)) {
        return null;
        break;
    }
    $response->song_id = trim($response->song_id);
    $response->location = trim($response->location);
    $response->title = trim($response->title);
    $response->album_name = trim($response->album_name);
    $response->artist = trim($response->artist);
    $response->album_pic = trim($response->album_pic);
    // 解密location
    $response->location = $DecryptionLocation($response->location);
    return $response;
};
$get_file_name = function ($response)
{
    return dirname(__FILE__). '/mp3/'. $response->song_id. '.' . str_replace('/', '-', $response->title);
};
if (isset($_GET['debug'])) {
    $action = 'api';
    $_POST['songId'] = '1770221910';
}
header('Content-Type: application/json');
switch ($action) {
    case 'api': {
        $response = $get_song_info();
        if ($response) {
            $mp3file = $get_file_name($response).".mp3";
            if (file_exists($mp3file)) {
                $response->hasDown = 1;
            } else {
                $response->hasDown = 0;
            }
            $response = json_encode($response);
        } else {
            $response = json_encode(['status' => 0]);
        }
        echo $response;
        break;
    }
    case 'download': {
        $response = $get_song_info();
        if (!$response) {
            // out
            $output = [];
            $output['status'] = 0;
            $output['song_id'] = $_POST['songId'];
            echo json_encode($output);
            break;
        }
        $imgFileExt = pathinfo($response->album_pic, PATHINFO_EXTENSION);
        $mp3file = $get_file_name($response).".mp3";
        
        if (!file_exists($mp3file)) {
            // 先下文件
            $mp3data = $curl_download($response->location);
            file_put_contents($mp3file, $mp3data);
        }
        // 处理文件，亲
        /*
         * 返回 的 $response 信息
        {
          "status": "ok",
          "song": {
            "song_id": "3341658",
            "title": "\u7f8e\u3057\u304d\u3082\u306e",
            "location": "http:\/\/m5.file.xiami.com\/976\/54976\/301986\/3341658_10853734_l.mp3?auth_key=2b681780455fee58d0f2a0652b61354f-1403568000-0-null",
            "song_lrc": "http:\/\/img.xiami.net\/lyric\/58\/3341658_13995474539483.lrc",
            "album_pic": "http:\/\/img.xiami.net\/images\/album\/img76\/54976\/3019861370588827_2.jpg",
            "song_level": "-1",
            "album_id": "301986",
            "album_name": "Roman",
            "album_pic": "http:\/\/img.xiami.net\/images\/album\/img76\/54976\/3019861370588827_2.jpg",
            "artist_id": "54976",
            "artist": "Sound Horizon",
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
        $tit2->setText($response->title);
        $id3->addFrame($tit2);
        // talb
        $talb = new Zend_Media_Id3_Frame_Talb();
        $talb->setText($response->album_name);
        $id3->addFrame($talb);
        // tcmp
        $tcmp = new Zend_Media_Id3_Frame_Tcmp();
        $tcmp->setText($response->artist);
        $id3->addFrame($tcmp);
        // Tso2
        $tso2 = new Zend_Media_Id3_Frame_Tso2();
        $tso2->setText($response->artist);
        $id3->addFrame($tso2);
        // Tmcl
        $tmcl = new Zend_Media_Id3_Frame_Tmcl();
        $tmcl->setText($response->artist);
        $id3->addFrame($tmcl);
        // Tope
        $tope = new Zend_Media_Id3_Frame_Tope();
        $tope->setText($response->artist);
        $id3->addFrame($tope);
        // Tpe1
        $tpe1 = new Zend_Media_Id3_Frame_Tpe1();
        $tpe1->setText($response->artist);
        $id3->addFrame($tpe1);
        // pic
        $apic = new Zend_Media_Id3_Frame_Apic();
        if ($imgFileExt === 'jpg') {
            $apic->setMimeType('image/jpeg');
        } else {
            $apic->setMimeType('image/'. $imgFileExt);
        }
        // 远程获取图片
        $imgdata = $curl_download($response->album_pic);
        $apic->setImageData($imgdata); 
        $apic->setImageType(3); 
        $id3->addFrame($apic); 
        // add
        $id3->write($mp3file);
        // 移除图片
        $imgFile = $get_file_name($response).".{$imgFileExt}";
        if (file_exists($imgFile)) {
            unlink($imgFile);
        }
        // out
        $output = [];
        $output['status'] = 1;
        $output['song_id'] = $response->song_id;
        echo json_encode($output);
        break;
    }
    default:
        # code...
        break;
}