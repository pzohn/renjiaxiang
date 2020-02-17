<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Libs\GuzzleHttp;


class ImageController extends Controller
{
    function request_post($url = '', $param = '')
    {
        if (empty($url) || empty($param)) {
            return false;
        }

        $postUrl = $url;
        $curlPost = $param;
        // 初始化curl
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $postUrl);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        // 要求结果为字符串且输出到屏幕上
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        // post提交方式
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $curlPost);
        // 运行curl
        $data = curl_exec($curl);
        curl_close($curl);

        return $data;
    }

    public function getOcrResult(Request $req)
    {
        $urlToken = "https://aip.baidubce.com/oauth/2.0/token";
        $paramsToken = [
            'client_id' => $req->get('client_id'),
            'client_secret' => $req->get('client_secret'),
            'grant_type' => "client_credentials",
        ];
        try {
            $resultToken = GuzzleHttp::guzzleGet($urlToken, $paramsToken);
            if (isset($resultToken['error'])) {
                return [
                    "error" => $resultToken['error'],
                    "error_description" => $resultToken['error_description'],
                ];
            }
            $token = $resultToken['access_token'];
            $url = 'https://aip.baidubce.com/rest/2.0/ocr/v1/general?access_token=' . $token;
            $img = file_get_contents('[本地文件路径]');
            $img = base64_encode($img);
            $bodys = array(
                'image' => $img
            );
            $res = request_post($url, $bodys);

            var_dump($res);
            if ($access_token) {
                $urlOcr = "https://aip.baidubce.com/rest/2.0/ocr/v1/general";
                $params = [
                    'url' => $req->get('url'),
                    'access_token' =>  $access_token,
                    'Content-Type' => "application/x-www-form-urlencoded"
                    ];

                 $result = GuzzleHttp::guzzleGet($urlOcr, $params);
                 return [
                    'result' => $result,
                    'params' => $params,
                    'urlOcr' =>  $urlOcr
                 ];
                 
                 if(isset($result["error_code"]))
                 {
                     return [
                        "error_code" => $result["err_code"],
                        "error_msg" => $result["err_code_des"],
                     ];
                 }
                 else
                 {
                    return $result["words_result"];
                 }

            }

        } catch (\Exception $e) {
            // 异常处理
            \Log::info("----------", [$e]);
            return [
                "code" => $e->getCode(),
                "msg"  => $e->getMessage(),
                "data" => [],
            ];
        }
    }

    public function getOcrResultEx(Request $req)
    {
        $urlToken = "https://aip.baidubce.com/rest/2.0/ocr/v1/general";
        $paramsToken = [
            'url' => $req->get('url'),
            'access_token' =>  $req->get('access_token'),
            'Content-Type' => "application/x-www-form-urlencoded"
        ];
        try {
            $resultToken = GuzzleHttp::guzzleGet($urlToken, $paramsToken);
            return $resultToken

        } catch (\Exception $e) {
            // 异常处理
            \Log::info("----------", [$e]);
            return [
                "code" => $e->getCode(),
                "msg"  => $e->getMessage(),
                "data" => [],
            ];
        }
    }
}
