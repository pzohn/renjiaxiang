<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Libs\GuzzleHttp;
use App\Models\Zhang;
use App\Models\Excompany;
use App\Models\SendAddress;
use App\Models\Childtrade;
use App\Models\Image;


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
        return [
            "count" => $resultGetall['count'],
            "data" => $resultGetall['data']
        ];
    }

    public function addOrder(Request $req)
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
        $excompany = Excompany::GetExcompanyById($zhang->f);
        $sendAddress = SendAddress::GetAddress($req->get('trade_id'));
        $sender = [
            'name' => '胡斌',
            'mobile' => '18303741618',
            'province' => '河南省',
            'city' => '许昌市',
            'area' => '魏都区',
            'address' => '胡斌用来测试的地址,快递不用发送'
        ];
        $receiver  = [
            'name' => $sendAddress->name,
            'mobile' => $sendAddress->phone,
            'province' => $sendAddress->province,
            'city' => $sendAddress->city,
            'area' => $sendAddress->area,
            'address' => $sendAddress->detail
        ];
        $childtrades = Childtrade::paySelectById($req->get('trade_id'));
        $childtradesTmp = [];
        $shop_names = '';
        $shop_names = '';
        $count = count($childtrades);
        $index = 0;
        $img_url = '';
        $wxa_path = '/pages/index/index';
        $img_url = '';
        foreach ($childtrades as $k => $v) {
            $shopping = Shopping::shoppingSelect($v->shopping_id);
            $index ++;
            if ($index == 1){
                $img_url = Image::GetImageUrlByParentId($shopping->id,'title',$shopping->type);
            }
            if ($shopping){
                $childtradesTmp[] = [
                    "name" => $shopping->name,
                    "num" => $v->num
                ];
                $img_url = Image::Image::GetImageUrlByParentId($shopping->id,$title,$shopping->type)
                $shop_names = $shop_names . $shopping->name;
                if ($index != $count){
                    $shop_names = $shop_names . '&';
                }
            }
        }
        $cargo = [
            'count' => $count,
            'weight'=> 5.5,
            'space_x'=> 30.5,
            'space_y'=> 20,
            'space_z'=> 20,
            'detail_list' => $childtradesTmp
        ];
        $shop = [
            'wxa_path' => $wxa_path,
            'img_url' => $img_url,
            'goods_name' => $shop_names,
            'goods_count' =>  $count
        ];
        $insured = [
            'use_insured' => 0,
            'insured_value' => 10000
        ];
        $service  = [
            'service_type' => $excompany->service_type,
            'service_name' => $excompany->service_name
        ];
        $paramsAdd = [
            'access_token' => $access_token,
            'add_source' => 0,
            'wx_appid' => $zhang->a,
            'order_id' => 'hubinceshibaishi20210126',
            'openid' => 'openid123456',
            'delivery_id' => $excompany->delivery_id,
            'biz_id' => $excompany->biz_id,
            'sender' => $sender,
            'receiver' => $receiver,
            'shop' => $shop,
            'insured' => $insured,
            'service' => $service
        ];
        $urlAdd = 'https://api.weixin.qq.com/cgi-bin/express/business/order/add';
        $resultAdd = GuzzleHttp::guzzlePost($urlToken, $paramsAdd);
        if (isset($resultAdd['errcode']))
        {
            return [
                "errcode" => $resultAdd['errcode'],
                "errmsg" => $resultAdd['errmsg']
            ];
        }
        return $resultAdd;
    }
}
