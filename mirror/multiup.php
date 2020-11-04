<?php
defined("BASEPATH") or exit("No direct access allowed"); 
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest')):
?>
<?php

    /*
    @author gusmanu
    @link github.com/gusmanu
    @Multiup Remote Upload
	@YamiDrive author Akouji
    */

    header("Content-type:application/json");
    error_reporting(0);
    include "system/function.php";
    if (isset($_GET['id']))
    {
        $file = json_decode(file_get_contents("base/data/main/share/$_GET[id].json") , true);
        $account = json_decode(file_get_contents("base/data/setting/mirror.json") , true);
        $fileid = $file['file']['file_id'];
        $name = $file['file']['title'];
        $a = directdl2($fileid);
        $login = $account['mirroruser'];
        $key = $account['mirrorpass'];
        $urls = $a;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://multiup.org/api/remote-upload");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, ['username' => $login, 'password' => $key, 'fileName' => $name, 'link' => $urls]);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        $res = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $ch = null;
        $response = json_decode($res, true);
        if ($response['error'] == "success")
        {
            $hasil = array(
                code => "200",
                file => $response['link']
            );
            $file['file']['mirror'] = $response['link'];
            file_put_contents("base/data/main/share/$_GET[id].json", json_encode($file, true));
            echo json_encode($hasil);
        }
        else
        {
            $hasil = array(
                code => "404",
                file => "File Not Found/Server Down"
            );
            echo json_encode($hasil);
        }
    }
?>
<?php
endif; ?>
