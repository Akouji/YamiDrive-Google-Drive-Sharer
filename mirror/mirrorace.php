<?php
defined("BASEPATH") or exit("No direct access allowed");
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest')): ?>
<?php

    /*
    @author gusmanu
    @link github.com/gusmanu
    @MirrorAce Remote Upload
	@YamiDrive author Akouji
    */

    header("Content-type:application/json");
    error_reporting(0);
    include "system/function.php";
    if (isset($_GET['id']))
    {

        $idfile = str_replace("a", "", $_GET[id]);
        $file = json_decode(file_get_contents("base/data/main/share/$idfile.json") , true);
        $account = json_decode(file_get_contents("base/data/setting/mirror.json") , true);
        $fileid = $file['file']['file_id'];
        $api_key = $account['aceuser'];
        $api_token = $account['acekey'];
        $mir1 = $account['mir1'];
        $mir2 = $account['mir2'];
        $mir3 = $account['mir3'];
        $mir4 = $account['mir4'];
        $mir5 = $account['mir5'];
        $url = sprintf('https://www.googleapis.com/drive/v3/files/%s?alt=media&key=AIzaSyD739-eb6NzS_**********', $fileid);
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://mirrorace.com/api/v1/file/upload");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, ['api_key' => $api_key, 'api_token' => $api_token]);
    $res = curl_exec($ch);
    $response = json_decode($res, true);
    curl_close($ch);
    $ch = null;

    if ($response[status] == "success")
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $response[result][server_remote]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, ['api_key' => $api_key, 'api_token' => $api_token, 'cTracker' => $response[result][cTracker], 'upload_key' => $response[result][upload_key], 'url' => $url, 'mirrors[1]' => $mir1, 'mirrors[2]' => $mir2, 'mirrors[3]' => $mir3, 'mirrors[4]' => $mir4, 'mirrors[5]' => $mir5

        ]);
        $resp = curl_exec($ch);
        $response2 = json_decode($resp, true);
        if ($response2['status'] == "success")
        {
            $hasil = array(
                code => "200",
                file => $response2['result']['url']
            );
            $file['file']['ace'] = $response2['result']['url'];
            file_put_contents("base/data/main/share/$idfile.json", json_encode($file, true));
            echo json_encode($hasil);
        } else {
            $hasil = array(
                code => "403",
                file => "Upload Failed, Please check your drive link"
            );
            echo json_encode($hasil);
       }
    }
    else
    {
        $hasil = array(
            code => "403",
            file => $response['result']
        );
        echo json_encode($hasil);
    }

?>
<?php
endif; ?>
