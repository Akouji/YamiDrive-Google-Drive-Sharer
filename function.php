<?php
/*
@author gusmanu
@link github.com/gusmanu
@Core Function GD Sharer v2
@YamiDrive author Akouji 
*/
function load($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_AUTOREFERER, true);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

function config($key)
{
    $file = file_get_contents("base/data/setting/******.json");
    $deco = json_decode($file, true);
    $conf = $deco[$key];
    return $conf;
}

function head_content()
{
    $id = config('google.webmaster.id');
    if (!empty($id))
    {
        $content = "<meta name=\"google-site-verification\" content=\"$id\" />";
    }
    return $content;
}

function analytics()
{
    $id = config('google.analytics.id');
    if (!empty($id))
    {
        $content = "
<script>
(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
})(window,document,'script','https://www.google-analytics.com/analytics.js','ga');
ga('create', '$id', 'auto');
ga('send', 'pageview');
</script>";
    }
    return $content;
}

function token($type, $refresh)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://www.googleapis.com/oauth2/v4/token");
    curl_setopt($ch, CURLOPT_POST, 1);
    if ($type == "code")
    {
        curl_setopt($ch, CURLOPT_POSTFIELDS, "code=$refresh&redirect_uri=" . config('drive.redirect.uris') . "&client_id=" . config('drive.client.id') . "&client_secret=" . config('drive.client.secret') . "&grant_type=authorization_code");
    }
    else if ($type == "refresh")
    {
        if ($refresh)
        {
            $user = json_decode(file_get_contents("base/data/user/$refresh.json") , true);
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, "client_secret=" . config('drive.client.secret') . "&grant_type=refresh_token&refresh_token=$user[refresh_token]&client_id=" . config('drive.client.id') . "");
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    curl_close($ch);
    return json_decode($result, true);
}
function directdl($id, $token)
{
    $files = json_decode(load("https://www.googleapis.com/drive/v2/files/$id?fields=downloadUrl&access_token=$token") , true);
    $downloadUrl = str_replace("&gd=true", "", $files['downloadUrl']);
    return $downloadUrl;
}

function directdl2($id)
{
    $ch = curl_init("https://drive.google.com/uc?id=$id&authuser=0&export=download");
    curl_setopt_array($ch, array(
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_POSTFIELDS => [],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => 'gzip,deflate',
        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
        CURLOPT_HTTPHEADER => ['accept-encoding: gzip, deflate, br',
        'content-length: 0',
        'content-type: application/x-www-form-urlencoded;charset=UTF-8',
        'origin: https://drive.google.com',
        'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.181 Safari/537.36',
        'x-client-data: CKG1yQEIkbbJAQiitskBCMS2yQEIqZ3KAQioo8oBGLeYygE=',
        'x-drive-first-party: DriveWebUi',
        'x-json-requested: true']
    ));
    $response = curl_exec($ch);
    $object = json_decode(str_replace(')]}\'', '', $response) , true);
    return $object[downloadUrl];
}

function delete($id, $token)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://www.googleapis.com/drive/v3/files/$id?key=$token");
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Authorization: Bearer $token"
    ));
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    curl_close($ch);
}

function menu_file($token, $next, $q)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_AUTOREFERER, true);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, "https://www.googleapis.com/drive/v3/files?q=fullText+contains+%27$q%27&pageSize=15&fields=files(fileExtension%2Cid%2Cname%2Csize%2CoriginalFilename)%2CnextPageToken&access_token=$token&pageToken=$next");
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $data = curl_exec($ch);
    curl_close($ch);
    return json_decode($data, true);
}

function formatBytes($size, $precision = 2)
{
    $base = log($size, 1024);
    $suffixes = array(
        '',
        'KB',
        'MB',
        'GB',
        'TB'
    );
    return round(pow(1024, $base - floor($base)) , $precision) . ' ' . $suffixes[floor($base) ];
}

function activity($user_id, $file_id, $title, $format, $size, $mime)
{
    $date = date("d-M-Y H:i:s");
    $folder = "base/data/main/user/$user_id";
    if (is_null($id))
    {
        $id = 123;
    }
    while (file_exists("base/data/main/share/$id.json"))
    {
        $id += $size;
    }
    if (!is_dir($folder))
    {
        mkdir($folder, 0777, true);
    }
    $info = array(
        status => "publish",
        user_id => $user_id,
        file_id => $file_id,
        share_id => $id,
        date => $date,
        title => $title,
        format => $format,
        size => $size,
        poster => $poster,
        mirror => $mirror,
        mime => $mime
    );
    $format = array(
        "file" => $info
    );
    file_put_contents("base/data/main/share/$id.json", json_encode($format));
    file_put_contents("base/data/main/user/$user_id/$id.json", null);
    echo "<a href=\"http://" . config('site.domain') . "/file/$id\">$title</a>";
    return true;
}
function anyone2($id, $token)
{
    $post = array(
        "role" => 'reader',
        "type" => 'anyone'
    );
    $url = "https://www.googleapis.com/drive/v2/files/$id/permissions";
    $authorization = "Authorization: Bearer $token";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        $authorization
    ));
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));
    curl_setopt($ch, CURLOPT_ENCODING, '');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.99 Safari/537.36');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    $result = curl_exec($ch);
    curl_close($ch);
}
function copyfile2($id, $token, $folder)
{
    $url = "https://www.googleapis.com/drive/v2/files/$id/copy";
    $authorization = "Authorization: Bearer $token";
    $data = array(
        "parents" => [["id" => $folder]],
        'description' => 'download from Google Sharer'
    );
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        $authorization
    ));
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_ENCODING, '');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.99 Safari/537.36');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    $result = curl_exec($ch);
    $json = json_decode($result, true);
    curl_close($ch);
    return $json;
}
function totaluser()
{
    $getuser = glob("base/data/user/*.json");
    $totaluser = count($getuser);
    return $totaluser;
}
function totalshare()
{
    $getshare = glob("base/data/main/share/*.json");
    $totalshare = count($getshare);
    return $totalshare;
}
function checksize()
{
    $file = "base/data/setting/totalsize.json";
    if (file_exists($file))
    {
        unlink($file);
    }
    $getshare = glob("base/data/main/share/*.json");
    foreach ($getshare as $getsize)
    {
        $data = file_get_contents($getsize);
        $content = json_decode($data, true);
        $shareid = $content[file][share_id];
        $size = $content[file][size];
        $get[$shareid] = $size;
        file_put_contents("base/data/setting/totalsize.json", json_encode($get, true));
    }
}
function totalsize()
{
    $get = json_decode(file_get_contents("base/data/setting/totalsize.json") , true);
    $ukuran = array_sum($get);
    $ukuran = formatBytes($ukuran);
    return $ukuran;
}
function totaldownload()
{
    $file = "base/data/setting/****.json";
    if (!file_exists($file))
    {
        file_put_contents($file, null);
    }
    $get = json_decode(file_get_contents($file) , true);
    $download = $get[download];
    if ($download == null)
    {
        $down = "0";
    }
    else
    {
        $down = $download;
    }
    return $down;
}
function totalshareuser($user)
{
    $getshare = glob("base/data/main/user/$user/*.json");
    $totalshare = count($getshare);
    return $totalshare;
}
function totaldownloaduser($user)
{
    $file = "base/data/main/user/$user/cache/****.json";
    $folder = "base/data/main/user/$user/cache";
    if (!is_dir($folder))
    {
        mkdir($folder, 0777, true);
    }
    if (!file_exists($file))
    {
        file_put_contents("base/data/main/user/$user/cache/****.json", null);
    }
    $get = json_decode(file_get_contents($file) , true);
    $download = $get[download];
    if ($download == null)
    {
        $down = "0";
    }
    else
    {
        $down = $download;
    }
    return $down;
}
function cekukuran($user)
{
    $folder = "base/data/main/user/$user/cache";
    $file = "base/data/main/user/$user/cache/size.json";
    if (!is_dir($folder))
    {
        mkdir($folder, 0777, true);
    }
    if (file_exists($file))
    {
        unlink($file);
    }
    $getshare2 = glob("base/data/main/user/$user/*.json");
    foreach ($getshare2 as $getsize2)
    {
        $kill = explode("/", $getsize2);
        $file = "base/data/main/share/$kill[5]";
        $data = file_get_contents($file);
        $content = json_decode($data, true);
        $shareid = $content[file][share_id];
        $size = $content[file][size];
        $get[$shareid] = $size;
        file_put_contents("base/data/main/user/$user/cache/size.json", json_encode($get, true));
    }
}
function ukuranuser($user)
{
    $file = "base/data/main/user/$user/cache/size.json";
    if (file_exists($file))
    {
        $get = json_decode(file_get_contents("base/data/main/user/$user/cache/size.json") , true);
        $ukuran = array_sum($get);
    }
    else
    {
        $ukuran = null;
    }
    if ($ukuran != null)
    {
        $ukuran = formatBytes($ukuran);
    }
    else
    {
        $ukuran = "0 MB";
    }
    return $ukuran;
}
function totalbroken($user)
{
    $getshare = glob("base/data/main/user/$user/broken/*.json");
    $totalshare = count($getshare);
    if ($totalshare == null)
    {
        $broken = "0";
    }
    else
    {
        $broken = $totalshare;
    }
    return $broken;
}
function create_folder($token)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://www.googleapis.com/drive/v3/files?fields=name%2c+id%2c+parents');
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-type: application/json',
        'Authorization: Bearer ' . $token
    ));
    curl_setopt($ch, CURLOPT_POSTFIELDS, '{"name" : "' . config('site.title') . '", "mimeType" : "application/vnd.google-apps.folder"}');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    curl_close($ch);
    return json_decode($result, true);
}
function move($file, $folder, $root, $token)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://www.googleapis.com/drive/v3/files/' . $file . '?addParents=' . $folder . '&removeParents=' . $root . '&fields=id%2c+parents');
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Authorization: Bearer ' . $token
    ));
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}
function anyonefolder($folder, $token)
{
    $post = array(
        "role" => 'reader',
        "type" => 'anyone'
    );
    $url = "https://www.googleapis.com/drive/v2/files/$folder/permissions";
    $authorization = "Authorization: Bearer $token";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        $authorization
    ));
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));
    curl_setopt($ch, CURLOPT_ENCODING, '');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.99 Safari/537.36');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    $result = curl_exec($ch);
    curl_close($ch);
}
function copyfilev3($id, $token)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://www.googleapis.com/drive/v3/files/' . $id . '/copy?access_token=' . $token);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-length: 0',
        'Content-type: application/json',
        'Authorization: Bearer ' . $token
    ));
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    curl_close($ch);
    return json_decode($result, true);
}
function check_quota($token)
{
    $l5 = curl_init();
    curl_setopt($l5, CURLOPT_URL, 'https://www.googleapis.com/drive/v3/about?fields=storageQuota&key=' . $token);
    curl_setopt($l5, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    curl_setopt($l5, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($l5, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($l5, CURLOPT_FRESH_CONNECT, 1);
    curl_setopt($l5, CURLOPT_FORBID_REUSE, 1);
    curl_setopt($l5, CURLOPT_HTTPHEADER, array(
        'Content-type: application/json',
        'Authorization: Bearer ' . $token
    ));
    curl_setopt($l5, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($l5, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($l5, CURLOPT_ENCODING, '');
    curl_setopt($l5, CURLOPT_VERBOSE, 0);
    return json_decode(curl_exec($l5) , 1);
}
function check_cache()
{
    $cached = "base/data/setting/cached.json";
    $totalsize = "base/data/setting/totalsize.json";
    if (!file_exists($cached))
    {
        if (file_exists($totalsize))
        {
            $cek = json_decode(file_get_contents($totalsize) , true);
            $jum = count($cek);
            $size = array_sum($cek);
            $hasil = array(
                count => $jum,
                size => $size,
            );
            file_put_contents($cached, json_encode($hasil, true));
        }
        else
        {
            $hasil = array(
                count => null,
                size => null
            );
            file_put_contents($cached, json_encode($hasil, true));
        }
    }
}
function total_size2()
{
    $cached = "base/data/setting/cached.json";
    $file = glob("base/data/main/share/*.json");
     usort($file, function ($a, $b)
        {
            return filemtime($b) - filemtime($a);
        });
    $jum = count($file);
    $cek = json_decode(file_get_contents($cached) , true);
    if ($cek[count] == $jum)
    {
        $size = $cek[size];
    }
    else
    {
        $selisih = $jum - $cek[count];
        $cache = "base/data/setting/cache.json";
        if (file_exists($cache))
        {
            unlink($cache);
        }
        for ($i = 0;$i < $selisih;$i++)
        {
            $data = json_decode(file_get_contents($file[$i]) , true);
            $shareid = $data[file][share_id];
            $uk = $data[file][size];
            $get[$shareid] = $uk;
            file_put_contents("base/data/setting/cache.json", json_encode($get, true));
        }
        $coba = json_decode(file_get_contents($cache) , true);
        $uk2 = array_sum($coba);
        $size = $cek[size] + $uk;
        $hasil = array(
            count => $jum,
            size => $size,
        );
        file_put_contents($cached, json_encode($hasil, true));
    }
    return $size;
}

function checkfolder($id)
{
    $key = "AIzaSyCno_*****************";
    $url = "https://www.googleapis.com/drive/v2/files?q='$id'+in+parents&key=$key";
    $curl = curl_init();
    $opts = [CURLOPT_URL => $url, CURLOPT_RETURNTRANSFER => true, ];
    curl_setopt_array($curl, $opts);
    $response = json_decode(curl_exec($curl) , true);
    return $response;
}

function secure_link($id)
{
$domain = config('site.domain');
$link = "https://$domain/secure/$id";
$poster = "https://$domain/image/$id";
        $hasil = array(
            link => $link,
            poster => $poster
            );
    return $hasil;
}

function ErrMessage($code){
    if($code == '400')
    {
        $message = "Sharing succeeded, but the notification email was not correctly delivered. Please login with another Google account.";
    }
    elseif($code == '401')
    {
        $message = "Authorization Error, your login session has expired. Please log-in again.";
    }
    elseif($code == '403')
    {
        $message = "Your Google Drive account storage was full, please delete another file in your Google Drive, or login with another Google account.";
    }
    elseif($code == '404')
    {
        $message = "File was not Found!";
    }
    elseif($code == '500')
    {
        $message = "500 Internal Server Error";
    }
    return $message;
}
?>
