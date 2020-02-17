<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Libs\GuzzleHttp;


class ImageController extends Controller
{
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
            $access_token = $resultToken['access_token'];
            if ($access_token) {
                $urlOcr = "https://aip.baidubce.com/rest/2.0/ocr/v1/general";
                $params = [
                    'url' => $req->get('url'),
                    'access_token' =>  $access_token,
                    'Content-Type' => "application/x-www-form-urlencoded"
                    ];

                 $result= GuzzleHttp::guzzleGet($urlOcr, $params);
                 if($result["error_code"] == "FAIL")
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
}
