<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Libs\GuzzleHttp;
use App\Models\Zhang;


class ExpressController extends Controller
{
    public function getAllDelivery(Request $req)
    {
        $zhang = Zhang::getZhang($req->get('shop_id'));
        $urlToken = "https://api.weixin.qq.com/cgi-bin/token";
        $paramsToken = [
        	'appid' => $zhang->a,
            'secret' => $zhang->b,
            'grant_type' => "client_credential"
        ];
        $resultToken = GuzzleHttp::guzzleGet($urlToken, $paramsToken);
        if (isset($resultToken['errcode']))
        {
            return [
                "errcode" => $resultToken['errcode'],
                "errmsg" => $resultToken['errmsg']
            ];
        }
        $access_token = $resultToken["access_token"];
        $urlGetall = "https://api.weixin.qq.com/cgi-bin/express/business/delivery/getall";
        $paramsGetall = [
        	'access_token' => $access_token
        ];
        $resultGetall = GuzzleHttp::guzzleGet($urlGetall, $paramsGetall);
        if (isset($resultGetall['errcode']))
        {
            return [
                "count" => $resultGetall['count'],
                "data" => $resultGetall['data']
            ];
        }
    }
}
