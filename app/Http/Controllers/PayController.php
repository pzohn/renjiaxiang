<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Libs\GuzzleHttp;
use App\Models\Trade;
use App\Models\Card;
use App\Models\Information;
use App\Models\Shop;
use App\Models\Parter;

class PayController extends Controller
{
    public function onPay(Request $req) {

        $urlLogin = "https://api.weixin.qq.com/sns/jscode2session";
        $paramsLogin = [
        	'appid' => "wxa7eeeae70646a1c0",
            'secret' => "0b9476e6f8c8eac6cc5b71026f9bb23a",
            'js_code' => $req->get('js_code'),
            'grant_type' => "authorization_code",
        ];
        try {
            $resultLogin = GuzzleHttp::guzzleGet($urlLogin, $paramsLogin);
            if (isset($resultLogin['errcode'])) {
                return [
                    "errcode" => $resultLogin['errcode'],
                    "errmsg" => "无效登录信息",
                ];
            }
            $openid = $resultLogin['openid'];
            $session_key = $resultLogin['session_key'];

            if ($openid && $session_key) {
                $card = Card::getCard($req->get('detail_id'));
                $urlPay = "https://api.mch.weixin.qq.com/pay/unifiedorder";
                $params = [
                    'appid' => $paramsLogin["appid"],
                    'body' => $req->get('body'),
                    'mch_id' => "1509185861",
                    'nonce_str' => $this->createRand(32),
                    'notify_url' => "https://www.hattonstar.com/onPayBack",
                    'openid' => $openid,
                    'out_trade_no'=> $this->createTradeNo(),
                    'spbill_create_ip' => $req->getClientIp(),
                    'total_fee' => $card->PRICE * 100,
                    'trade_type' => "JSAPI",
                    ];

                    ksort($params);

                    $stringA = "";
                    foreach ($params as $k => $v) {
                        $stringA = $stringA . "&" . $k . "=" . $v;
                    }
                    $stringA = ltrim($stringA, "&");

                    $appid = $params["appid"];
                    $mch_id = $params["mch_id"];
                    $nonce_str = $params["nonce_str"];
                    $body = $params["body"];
                    $out_trade_no = $params["out_trade_no"];
                    $total_fee = $params["total_fee"];
                    $spbill_create_ip = $req->getClientIp();
                    $notify_url = $params["notify_url"];
                    $trade_type = $params["trade_type"];
                    $sign = $this->createSign($stringA);


                    $data = "<xml>
                    <appid>$appid</appid>
                    <body>$body</body>
                    <mch_id>$mch_id</mch_id>
                    <nonce_str>$nonce_str</nonce_str>
                    <notify_url>$notify_url</notify_url>
                    <openid>$openid</openid>
                    <out_trade_no>$out_trade_no</out_trade_no>
                    <spbill_create_ip>$spbill_create_ip</spbill_create_ip>
                    <total_fee>$total_fee</total_fee>
                    <trade_type>$trade_type</trade_type>
                    <sign>$sign</sign>
                 </xml>";
                 \Log::info("-----------", [$data]);
                 $trade = [
                    'out_trade_no' => $params["out_trade_no"],
                    'body' => $params["body"],
                    'detail_id' => $req->get('detail_id'),
                    'total_fee' => $params["total_fee"] * 0.01,
                    'phone' => $req->get('phone'),
                    'shop_id' => $req->get('shop_id'),
                    'name' => $req->get('name'),
                 ];
                 Trade::payInsert($trade);
                 $resultPay = GuzzleHttp:: postXml($urlPay, $data);
                 $decode = $this->decodeXml($resultPay);
                 if ($decode["result_code"] == "SUCCESS")
                 {
                    $sian_time = (string)time();
                    $resign = $this->createReSign($decode,$sian_time);
                    return $this->wxBack($decode,$resign,$sian_time);
                 }
                 else if($decode["result_code"] == "FAIL")
                 {
                     return [
                        "errcode" => $decode["err_code"],
                        "errmsg" => $decode["err_code_des"],
                     ];
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

    protected function decodeXml($xml) {
        libxml_disable_entity_loader(true);
        $values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $values;
    }

    protected function createRand($length) {
        $str='abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $len=strlen($str)-1;
        $randstr='';
        for($i=0;$i<$length;$i++){
        $num=mt_rand(0,$len);
        $randstr .= $str[$num];
        }
        return $randstr;
    }

    protected function createSign($stringA) {
        \Log::info("------- stringA --------", [$stringA]);
        $stringSignTemp = $stringA . "&key=renzheng840728chengboren15081900";
        $sign = strtoupper(md5($stringSignTemp));
        return $sign;
    }

    protected function createReSign($req,$sian_time) {

        $params = [
            'appId' => $req['appid'],
            'nonceStr' => $req['nonce_str'],
            'package' => "prepay_id=" . $req['prepay_id'],
            'signType' => "MD5",
            'timeStamp' => $sian_time,
            ];

            ksort($params);

        $stringA = "";
        foreach ($params as $k => $v) {
            $stringA = $stringA . "&" . $k . "=" . $v;
        }

        $StringTmp = ltrim($stringA, "&");
        $resign = $this->createSign($StringTmp);
        return $resign;
    }

    protected function getDateTime() {
        $date = date("Ymd");
        $time = date("his");
        $datetime = $date . $time;
        return $datetime;
    }

    protected function createTradeNo() {
        $trade_no = $this->getDateTime() . $this->createRand(6);
        return $trade_no;
    }


    public function onPayBack(Request $req) {

        $content = file_get_contents("php://input");
        $content = str_replace("\n","",$content);
        $params = $this->decodeXml($content);

        $sign_str = $params['sign'];
        unset($params['sign']);
        
        ksort($params);
        $str = "";
        foreach ($params as $k => $v) {
            $str .= "&".$k ."=" . $v;
        }
        $str .= "&key=renzheng840728chengboren15081900";
        $str = ltrim($str, "&");
        $sign_strTmp = strtoupper(md5($str));
        $updateTrade;
        if($sign_strTmp == $sign_str)
        {
            $updateTrade = Trade::payUpdate($params["out_trade_no"]);
            /*
            $trade = Trade::paySelect($params["out_trade_no"]);
            $card = Card::getCard($trade->detail_id);
            $infoPara =[
                'PHONE' => $trade->phone,
                'CARDID' => $trade->detail_id,
                'CARDNUM' => $card->USENUM
            ];
            
            $info = Information::updateCard($infoPara);
            return $info;

            */
        }
    }

    public function wxBack($decode,$resign,$sian_time) {
        return [
            "timeStamp" => $sian_time,
            "nonceStr"  => $decode['nonce_str'],
            "package" => "prepay_id=" . $decode['prepay_id'],
            "signType" => "MD5",
            "paySign" => $resign,
        ];
    }

    public function getCard(Request $req) {
        $detail_id = $req->get('detail_id');
        $card = Card::getCard($detail_id);
        return $card;
    }

    public function getShop(Request $req) {
        $phone = $req->get('phone');
        $pass = $req->get('pass');
        $shop = Shop::shopSelect($phone,$pass);
        return $shop;
    }

    public function getShopNopass(Request $req) {
        $phone = $req->get('phone');
        $shop = Shop::getShop($phone);
        return $shop;
    }

    public function flashShop(Request $req) {
        $phone = $req->get('phone');
        $shop = Shop::getShop($phone);
        return $shop;
    }

    public function onPayShop(Request $req) {

        $urlLogin = "https://api.weixin.qq.com/sns/jscode2session";
        $paramsLogin = [
        	'appid' => "wxa7eeeae70646a1c0",
            'secret' => "0b9476e6f8c8eac6cc5b71026f9bb23a",
            'js_code' => $req->get('js_code'),
            'grant_type' => "authorization_code",
        ];
        try {
            $resultLogin = GuzzleHttp::guzzleGet($urlLogin, $paramsLogin);
            if (isset($resultLogin['errcode'])) {
                return [
                    "errcode" => $resultLogin['errcode'],
                    "errmsg" => "无效登录信息",
                ];
            }
            $openid = $resultLogin['openid'];
            $session_key = $resultLogin['session_key'];

            if ($openid && $session_key) {
                $shopprice = 0;
                if($req->get('detail_id') == 1){
                    $shopprice = $req->get('card_one_price');
                }else if ($req->get('detail_id') == 2){
                    $shopprice = $req->get('card_two_price');
                }
                $card = Card::getCard($req->get('detail_id'));
                $urlPay = "https://api.mch.weixin.qq.com/pay/unifiedorder";
                $params = [
                    'appid' => $paramsLogin["appid"],
                    'body' => $req->get('body'),
                    'mch_id' => "1509185861",
                    'nonce_str' => $this->createRand(32),
                    'notify_url' => "https://www.hattonstar.com/onPayShopBack",
                    'openid' => $openid,
                    'out_trade_no'=> $this->createTradeNo(),
                    'spbill_create_ip' => $req->getClientIp(),
                    'total_fee' => $shopprice * $req->get('num') * 100,
                    'trade_type' => "JSAPI",
                    ];

                    ksort($params);

                    $stringA = "";
                    foreach ($params as $k => $v) {
                        $stringA = $stringA . "&" . $k . "=" . $v;
                    }
                    $stringA = ltrim($stringA, "&");

                    $appid = $params["appid"];
                    $mch_id = $params["mch_id"];
                    $nonce_str = $params["nonce_str"];
                    $body = $params["body"];
                    $out_trade_no = $params["out_trade_no"];
                    $total_fee = $params["total_fee"];
                    $spbill_create_ip = $req->getClientIp();
                    $notify_url = $params["notify_url"];
                    $trade_type = $params["trade_type"];
                    $sign = $this->createSign($stringA);


                    $data = "<xml>
                    <appid>$appid</appid>
                    <body>$body</body>
                    <mch_id>$mch_id</mch_id>
                    <nonce_str>$nonce_str</nonce_str>
                    <notify_url>$notify_url</notify_url>
                    <openid>$openid</openid>
                    <out_trade_no>$out_trade_no</out_trade_no>
                    <spbill_create_ip>$spbill_create_ip</spbill_create_ip>
                    <total_fee>$total_fee</total_fee>
                    <trade_type>$trade_type</trade_type>
                    <sign>$sign</sign>
                 </xml>";
                 \Log::info("-----------", [$data]);
                 $trade = [
                    'out_trade_no' => $params["out_trade_no"],
                    'body' => $params["body"],
                    'detail_id' => $req->get('detail_id'),
                    'total_fee' => $params["total_fee"] * 0.01,
                    'phone' => $req->get('phone'),
                    'shop_id' => $req->get('shop_id'),
                    'name' => $req->get('name'),
                 ];
                 Trade::payInsert($trade);
                 $resultPay = GuzzleHttp:: postXml($urlPay, $data);
                 $decode = $this->decodeXml($resultPay);
                 if ($decode["result_code"] == "SUCCESS")
                 {
                    $sian_time = (string)time();
                    $resign = $this->createReSign($decode,$sian_time);
                    return $this->wxBack($decode,$resign,$sian_time);
                 }
                 else if($decode["result_code"] == "FAIL")
                 {
                     return [
                        "errcode" => $decode["err_code"],
                        "errmsg" => $decode["err_code_des"],
                     ];
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

    public function onPayShopBack(Request $req) {

        $content = file_get_contents("php://input");
        $content = str_replace("\n","",$content);
        $params = $this->decodeXml($content);

        $sign_str = $params['sign'];
        unset($params['sign']);
        
        ksort($params);
        $str = "";
        foreach ($params as $k => $v) {
            $str .= "&".$k ."=" . $v;
        }
        $str .= "&key=renzheng840728chengboren15081900";
        $str = ltrim($str, "&");
        $sign_strTmp = strtoupper(md5($str));
        if($sign_strTmp == $sign_str)
        {
            $trade1 = Trade::paySelect($params["out_trade_no"]);
            if($trade1->pay_status == 1){
                return  $trade1;
            }
            Trade::payUpdate($params["out_trade_no"]);
            $trade = Trade::paySelect($params["out_trade_no"]);
            $shop = Shop::getShop($trade->phone);
            $price = 0;
            $sell_num = 0;
            if($trade->detail_id == 1)
            {
                $sell_num = ($trade->total_fee)/($shop->card_one_price);
                $sell_num = $shop->card_one_num + $sell_num;
                $shopinfo = Shop::vipOneUpdate($trade->phone,$sell_num);
                return $shopinfo;
            }
            else if($trade->detail_id == 2)
            {
                $sell_num = ($trade->total_fee)/($shop->card_two_price);
                $sell_num = $shop->card_two_num + $sell_num;
                $shopinfo = Shop::vipTwoUpdate($trade->phone,$sell_num);
                return $shopinfo;
            }
        }
    }

    public function getParter(Request $req) {
        $phone = $req->get('phone');
        $parter = Parter::getParter($phone);
        \Log::debug("------ get Partner ------", [$phone, $parter]);
        return $parter;
    }

}