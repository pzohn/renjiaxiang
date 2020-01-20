<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Libs\GuzzleHttp;
use App\Models\Trade;
use App\Models\Card;
use App\Models\Information;
use App\Models\Shop;
use App\Models\Parter;
use App\Models\Group;
use App\Models\Childtrade;
use App\Models\Shopping;
use App\Models\SendAddress;
use App\Models\Address;
use App\Models\Image;
use App\Models\Wxuser;
use App\Models\Member;
use App\Models\Zhang;

class PayController extends Controller
{
    public function onPay(Request $req) {
        $zhang = Zhang::getZhang($req->get('shop_id'));
        $urlLogin = "https://api.weixin.qq.com/sns/jscode2session";
        $paramsLogin = [
            'appid' => $zhang->a,
            'secret' => $zhang->b,
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
                    'mch_id' => $zhang->c,
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
                    $sign = $this->createSign($stringA,$zhang->d);


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
                 return $resultPay;
                 $decode = $this->decodeXml($resultPay);
                 if ($decode["result_code"] == "SUCCESS")
                 {
                    $sian_time = (string)time();
                    $resign = $this->createReSign($decode,$sian_time,$zhang->d);
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

    protected function createSign($stringA,$sec) {
        \Log::info("------- stringA --------", [$stringA]);
        $tmp = "&key=" . $sec;
        $stringSignTemp = $stringA .  $tmp;
        $sign = strtoupper(md5($stringSignTemp));
        return $sign;
    }

    protected function createReSign($req,$sian_time,$sec) {

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
        $resign = $this->createSign($StringTmp,$sec);
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
        $tradeTmp = Trade::paySelect($params["out_trade_no"]);
        $zhang = Zhang::getZhang($tradeTmp->shop_id);
        if ($zhang) {
            $tmp = "&key=" . $zhang->d;
            $str .= $tmp;
            $str = ltrim($str, "&");
            $sign_strTmp = strtoupper(md5($str));
            $updateTrade;
            if($sign_strTmp == $sign_str)
            {
                $trade1 = Trade::paySelect($params["out_trade_no"]);
                if($trade1->pay_status == 1){
                    return  $trade1;
                }
                $flag = Trade::payUpdate($params["out_trade_no"],$zhang->pay_status);
                $trade = Trade::paySelect($params["out_trade_no"]);
                if ( $flag == 1){
                    $this->doForme($trade);
                    return 1;
                }
                $card = Card::getCard($trade->detail_id);
                $infoPara =[
                    'PHONE' => $trade->phone,
                    'CARDID' => $trade->detail_id,
                    'CARDNUM' => $card->USENUM
                ];
                $info = Information::updateCard($infoPara);
                return $info;
            }
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

    public function getCards(Request $req) {
        $cards = Card::getCards();
        $cardsTmp = [];
        if (count($cards)){
            foreach ($cards as $k => $v) {
                $card = Card::getCard($v->ID);
                if ($v->ID)
                if ($card) {
                    $cardsTmp[] = [
                    "id" => $card->ID,
                    "pic_id" => mt_rand(1,9),
                    "title" => $card->DESC,
                    "price" => $card->PRICE,
                    "usetype" => $this->getUseType($card->USENUM),
                    "playtype" => $this->getPlayType($card->TYPE),
                    ];
                }
            }
        }
        return $cardsTmp;
    }

    protected function getUseType($usetype) {
        if ($usetype == 1){
            return '单词卡';
        }else if ($usetype > 1){
            return strval($usetype) . '次场';
        }else{
            return "";
        }
    }

    protected function getPlayType($playtype) {
        if ($cardtype == 1){
            return '半日场';
        }else if ($cardtype == 2){
            return '全日场';
        }else if ($cardtype == 3){
            return '通用场';
        }else{
            return "";
        }
    }

    public function getShop(Request $req) {
        $phone = $req->get('phone');
        $pass = $req->get('pass');
        $shop = Shop::shopSelect($phone,$pass);
        return $shop;
    }

    public function getShopById(Request $req) {
        $id = $req->get('id');
        $shop = Shop::getShopById($id);
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
        	'appid' => "wx8d32477fdd368d9a",
            'secret' => "d46d773f17e3f483e0673ec5b22aaa10",
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
                $money = $req->get('money');
                $urlPay = "https://api.mch.weixin.qq.com/pay/unifiedorder";
                $params = [
                    'appid' => $paramsLogin["appid"],
                    'body' => $req->get('body'),
                    'mch_id' => "1558764141",
                    'nonce_str' => $this->createRand(32),
                    'notify_url' => "https://www.hattonstar.com/onPayShopBack",
                    'openid' => $openid,
                    'out_trade_no'=> $this->createTradeNo(),
                    'spbill_create_ip' => $req->getClientIp(),
                    'total_fee' => $money * 100,
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
                    'total_fee' => $params["total_fee"]  * 0.01,
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
            $balance = $shop->balance + $trade->total_fee;
            $shopinfo = Shop::balanceUpdate($trade->phone,$balance);
            return $shopinfo;
        }
    }

    public function getParter(Request $req) {
        $phone = $req->get('phone');
        $parter = Parter::getParter($phone);
        \Log::debug("------ get Partner ------", [$phone, $parter]);
        return $parter;
    }

    public function getParterInfo(Request $req) {
        $phone = $req->get('phone');
        $pass = $req->get('pass');
        $parter = Parter::getParterInfo($phone,$pass);
        return $parter;
    }

    public function updateFoodandCar(Request $req) {
        $id = $req->get('id');
        $car = $req->get('car');
        $food = $req->get('food');
        $date = $req->get('date');
        $parter = Shop::updateFoodandCar($id,$food,$car,$date);
        return $parter;
    }


    public function onPayGroup(Request $req) {

        $urlLogin = "https://api.weixin.qq.com/sns/jscode2session";
        $paramsLogin = [
        	'appid' => "wx8d32477fdd368d9a",
            'secret' => "d46d773f17e3f483e0673ec5b22aaa10",
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
                $money = $req->get('money');
                $urlPay = "https://api.mch.weixin.qq.com/pay/unifiedorder";
                $params = [
                    'appid' => $paramsLogin["appid"],
                    'body' => $req->get('body'),
                    'mch_id' => "1558764141",
                    'nonce_str' => $this->createRand(32),
                    'notify_url' => "https://www.hattonstar.com/onPayGroupBack",
                    'openid' => $openid,
                    'out_trade_no'=> $this->createTradeNo(),
                    'spbill_create_ip' => $req->getClientIp(),
                    'total_fee' => $money,
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
                    'total_fee' => $params["total_fee"],
                    'phone' => $req->get('phone'),
                    'shop_id' => $req->get('shop_id'),
                    'name' => $req->get('name'),
                 ];
                 Trade::payInsertGroup($trade);
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

    public function onPayGroupBack(Request $req) {

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
            $trade1 = Trade::paySelect($params["out_trade_no"]);
            if($trade1->pay_status == 1){
                return  $trade1;
            }
            
            Trade::payUpdate($params["out_trade_no"]);
            $trade = Trade::paySelect($params["out_trade_no"]);
            $arry = preg_split("/@/",$trade->body);
            $para =[
                'parent_num' => $arry[1],
                'food_num' => $arry[2],
                'car_num' => $arry[3],
                'phone' => $trade->phone,
                'name' => $trade->name,
                'group_id' => $trade->group_id,
                'group_class' => $arry[4]
            ];
            $group = Group::groupInsert($para);
            return $group;
            
        }
    }

    public function getGroup(Request $req) {
        $phone = $req->get('phone');
        $group = Group::getGroup($phone);
        return $group;
    }

    public function IsUnUse(Request $req) {
        $phone = $req->get('phone');
        return Group::IsUnUse($phone);
    }

    public function geTrades() {
        $trades = Trade::getTrades();
        $tradesTmp = [];
        foreach ($trades as $k => $v) {
            $tradesTmp[] = [
            "id" => $v->id,
            "body" => $v->body,
            "tradeid" => $v->out_trade_no,
            "status" => $v->pay_status,
            "phone" => $v->phone,
            "time" => '1510363800000'
            ];
        }
        if ($trades) {
            $result_data = [
                'code' => 0,
                'msg' => '',
                'count' => count($trades),
                'data' => $tradesTmp
            ];
            return $result_data;
        }
    }

    public function getOrderAllForPerson(Request $req) {
        $wx_id = $req->get('wx_id');
        $trades = Trade::getOrderAllForPerson($wx_id);
        $title = "title";
        if ($trades){
            $tradesTmp = [];
            foreach ($trades as $k => $v) {
                $count = 0;
                $childtrades = Childtrade::paySelectById($v->id);
                $childtradesTmp = [];
                foreach ($childtrades as $k1 => $v1) {
                    $shopping = Shopping::shoppingSelect($v1->shopping_id);
                    if ($shopping){
                        $count += 1;
                        $childtradesTmp[] = [
                            "name" => $shopping->name,
                            "charge" => $shopping->price,
                            "title_pic" => Image::GetImageUrlByParentId($shopping->id,$title,$shopping->type),
                            "shopping_id" => $shopping->id,
                            "num" => $v1->num
                        ]; 
                    }
                }

                // if ($count == 0){
                //     $childtradesTmp[] = [
                //         "name" => $v->body,
                //         "charge" => $v->total_fee,
                //         "num" => 1
                //     ]; 
                //     $tradesTmp[] = [
                //         "time" => $v->updated_at->format('Y-m-d H:i:s'),
                //         "tradeid" => $v->out_trade_no,
                //         "charge" => $v->total_fee,
                //         "count" => $count,
                //         "detail" => $childtradesTmp,
                //         "address" => SendAddress::GetAddress($v->id),
                //         "status" => $this->getStatus($v->pay_status,1),
                //         "phone" =>  $v->phone,
                //         "body" => $v->body,
                //         "id" => $v->id                
                //     ];
                // }

                if ($count){
                    $tradesTmp[] = [
                        "time" => $v->updated_at->format('Y-m-d H:i:s'),
                        "tradeid" => $v->out_trade_no,
                        "charge" => $v->total_fee,
                        "count" => $count,
                        "detail" => $childtradesTmp,
                        "address" => SendAddress::GetAddressEx($v->id),
                        "status" => $this->getStatus($v->pay_status,$v->send_status,$v->finish_status),
                        "color" => $this->getColor($v->pay_status,$v->send_status,$v->finish_status),
                        "phone" =>  $v->phone,
                        "body" => $v->body,
                        "id" => $v->id,
                        "use_royalty" => $v->use_royalty
                    ];
                }
            }
            $result_data = [
                'code' => 0,
                'msg' => '',
                'count' => count($tradesTmp),
                'data' => $tradesTmp
            ];
            return $result_data;
        }
    }

    public function getOrderUnPayForPerson(Request $req) {
        $wx_id = $req->get('wx_id');
        $trades = Trade::getOrderUnPayForPerson($wx_id);
        $title = "title";
        if ($trades){
            $tradesTmp = [];
            foreach ($trades as $k => $v) {
                $count = 0;
                $childtrades = Childtrade::paySelectById($v->id);
                $childtradesTmp = [];
                foreach ($childtrades as $k1 => $v1) {
                    $shopping = Shopping::shoppingSelect($v1->shopping_id);
                    if ($shopping){
                        $count += 1;
                        $childtradesTmp[] = [
                            "name" => $shopping->name,
                            "charge" => $shopping->price,
                            "title_pic" => Image::GetImageUrlByParentId($shopping->id,$title,$shopping->type),
                            "shopping_id" => $shopping->id,
                            "num" => $v1->num
                        ]; 
                    }
                }

                if ($count){
                    $tradesTmp[] = [
                        "time" => $v->updated_at->format('Y-m-d H:i:s'),
                        "tradeid" => $v->out_trade_no,
                        "charge" => $v->total_fee,
                        "count" => $count,
                        "detail" => $childtradesTmp,
                        "address" => SendAddress::GetAddressEx($v->id),
                        "status" => '待付款',
                        "color" => 'red',
                        "phone" =>  $v->phone,
                        "body" => $v->body,
                        "id" => $v->id,
                        "use_royalty" => $v->use_royalty                
                    ];
                }
            }
            $result_data = [
                'code' => 0,
                'msg' => '',
                'count' => count($tradesTmp),
                'data' => $tradesTmp
            ];
            return $result_data;
        }
    }

    public function getOrderUnPay(Request $req) {
        $phone = $req->get('phone');
        $trades = Trade::getOrderUnPay($phone);
        if ($trades){
            $tradesTmp = [];
            foreach ($trades as $k => $v) {
                $count = 0;
                $childtrades = Childtrade::paySelectById($v->id);
                $childtradesTmp = [];
                foreach ($childtrades as $k1 => $v1) {
                    $activity = Campactivity::GetCampactivityById($v1->shopping_id);
                    $wxinfo = Wxinfo::GetWxinfoById($activity->wx_id);
                    if ($activity && $wxinfo){
                        $count += 1;
                        $childtradesTmp[] = [
                            "name" => $activity->name,
                            "title_pic" => Image::GetImageUrl($wxinfo->title_id),
                            "wx_id" => $wxinfo->id,
                            "activity_id" => $activity->id,
                            "charge" => $activity->charge,
                            "num" => $v1->num
                        ]; 
                    }
                }

                if ($count){
                    $tradesTmp[] = [
                        "out_trade_no" => $v->out_trade_no,
                        "date" => $v->updated_at->format('Y-m-d H:i:s'),
                        "trade_id" => $v->id,
                        "charge" => $v->total_fee,
                        "count" => $count,
                        "detail" => $childtradesTmp,
                        "status" => '待付款'
                        ];
                }
            }
            return  $tradesTmp;
        }
    }

    public function getOrderUnsendForPerson(Request $req) {
        $wx_id = $req->get('wx_id');
        $trades = Trade::getOrderUnsendForPerson($wx_id);
        $title = "title";
        if ($trades){
            $tradesTmp = [];
            foreach ($trades as $k => $v) {
                $count = 0;
                $childtrades = Childtrade::paySelectById($v->id);
                $childtradesTmp = [];
                foreach ($childtrades as $k1 => $v1) {
                    $shopping = Shopping::shoppingSelect($v1->shopping_id);
                    if ($shopping){
                        $count += 1;
                        $childtradesTmp[] = [
                            "name" => $shopping->name,
                            "charge" => $shopping->price,
                            "title_pic" => Image::GetImageUrlByParentId($shopping->id,$title,$shopping->type),
                            "shopping_id" => $shopping->id,
                            "num" => $v1->num
                        ]; 
                    }
                }

                if ($count){
                    $tradesTmp[] = [
                        "time" => $v->updated_at->format('Y-m-d H:i:s'),
                        "tradeid" => $v->out_trade_no,
                        "charge" => $v->total_fee,
                        "count" => $count,
                        "detail" => $childtradesTmp,
                        "address" => SendAddress::GetAddressEx($v->id),
                        "status" => '待发货',
                        "color" => 'orange',
                        "phone" =>  $v->phone,
                        "body" => $v->body,
                        "id" => $v->id,
                        "use_royalty" => $v->use_royalty                
                    ];
                }
            }
            $result_data = [
                'code' => 0,
                'msg' => '',
                'count' => count($tradesTmp),
                'data' => $tradesTmp
            ];
            return $result_data;
        }
    }

    public function getOrderUnsend(Request $req) {
        $phone = $req->get('phone');
        $trades = Trade::getOrderUnUse($phone);
        if ($trades){
            $tradesTmp = [];
            foreach ($trades as $k => $v) {
                $count = 0;
                $childtrades = Childtrade::paySelectById($v->id);
                $childtradesTmp = [];
                foreach ($childtrades as $k1 => $v1) {
                    $activity = Campactivity::GetCampactivityById($v1->shopping_id);
                    $wxinfo = Wxinfo::GetWxinfoById($activity->wx_id);
                    if ($activity && $wxinfo){
                        $count += 1;
                        $childtradesTmp[] = [
                            "name" => $activity->name,
                            "title_pic" => Image::GetImageUrl($wxinfo->title_id),
                            "wx_id" => $wxinfo->id,
                            "activity_id" => $activity->id,
                            "charge" => $activity->charge,
                            "num" => $v1->num
                            
                        ]; 
                    }
                }

                if ($count){
                    $tradesTmp[] = [
                        "out_trade_no" => $v->out_trade_no,
                        "date" => $v->updated_at->format('Y-m-d H:i:s'),
                        "trade_id" => $v->id,
                        "charge" => $v->total_fee,
                        "count" => $count,
                        "detail" => $childtradesTmp,
                        "status" => '待发货'
                        ];
                }
            }
            return  $tradesTmp;
        }
    }

    public function getOrderUnreceiveForPerson(Request $req) {
        $wx_id = $req->get('wx_id');
        $trades = Trade::getOrderUnreceiveForPerson($wx_id);
        $title = "title";
        if ($trades){
            $tradesTmp = [];
            foreach ($trades as $k => $v) {
                $count = 0;
                $childtrades = Childtrade::paySelectById($v->id);
                $childtradesTmp = [];
                foreach ($childtrades as $k1 => $v1) {
                    $shopping = Shopping::shoppingSelect($v1->shopping_id);
                    if ($shopping){
                        $count += 1;
                        $childtradesTmp[] = [
                            "name" => $shopping->name,
                            "charge" => $shopping->price,
                            "title_pic" => Image::GetImageUrlByParentId($shopping->id,$title,$shopping->type),
                            "shopping_id" => $shopping->id,
                            "num" => $v1->num
                        ]; 
                    }
                }

                if ($count){
                    $tradesTmp[] = [
                        "time" => $v->updated_at->format('Y-m-d H:i:s'),
                        "tradeid" => $v->out_trade_no,
                        "charge" => $v->total_fee,
                        "count" => $count,
                        "detail" => $childtradesTmp,
                        "address" => SendAddress::GetAddressEx($v->id),
                        "status" => '待收货',
                        "color" => 'blue',
                        "phone" =>  $v->phone,
                        "body" => $v->body,
                        "id" => $v->id,
                        "use_royalty" => $v->use_royalty                
                    ];
                }
            }
            $result_data = [
                'code' => 0,
                'msg' => '',
                'count' => count($tradesTmp),
                'data' => $tradesTmp
            ];
            return $result_data;
        }
    }   

    public function getOrderFinishForPerson(Request $req) {
        $wx_id = $req->get('wx_id');
        $trades = Trade::getOrderFinishForPerson($wx_id);
        $title = "title";
        if ($trades){
            $tradesTmp = [];
            foreach ($trades as $k => $v) {
                $count = 0;
                $childtrades = Childtrade::paySelectById($v->id);
                $childtradesTmp = [];
                foreach ($childtrades as $k1 => $v1) {
                    $shopping = Shopping::shoppingSelect($v1->shopping_id);
                    if ($shopping){
                        $count += 1;
                        $childtradesTmp[] = [
                            "name" => $shopping->name,
                            "charge" => $shopping->price,
                            "title_pic" => Image::GetImageUrlByParentId($shopping->id,$title,$shopping->type),
                            "shopping_id" => $shopping->id,
                            "num" => $v1->num
                        ]; 
                    }
                }

                if ($count){
                    $tradesTmp[] = [
                        "time" => $v->updated_at->format('Y-m-d H:i:s'),
                        "tradeid" => $v->out_trade_no,
                        "charge" => $v->total_fee,
                        "count" => $count,
                        "detail" => $childtradesTmp,
                        "address" => SendAddress::GetAddressEx($v->id),
                        "status" => '已完成',
                        "color" => 'green',
                        "phone" =>  $v->phone,
                        "body" => $v->body,
                        "id" => $v->id,
                        "use_royalty" => $v->use_royalty                
                    ];
                }
            }
            $result_data = [
                'code' => 0,
                'msg' => '',
                'count' => count($tradesTmp),
                'data' => $tradesTmp
            ];
            return $result_data;
        }
    }   

    public function getOrderSend(Request $req) {
        $phone = $req->get('phone');
        $trades = Trade::getOrderUse($phone);
        if ($trades){
            $tradesTmp = [];
            foreach ($trades as $k => $v) {
                $count = 0;
                $childtrades = Childtrade::paySelectById($v->id);
                $childtradesTmp = [];
                foreach ($childtrades as $k1 => $v1) {
                    $activity = Campactivity::GetCampactivityById($v1->shopping_id);
                    $wxinfo = Wxinfo::GetWxinfoById($activity->wx_id);
                    if ($activity && $wxinfo){
                        $count += 1;
                        $childtradesTmp[] = [
                            "name" => $activity->name,
                            "title_pic" => Image::GetImageUrl($wxinfo->title_id),
                            "wx_id" => $wxinfo->id,
                            "activity_id" => $activity->id,
                            "charge" => $activity->charge,
                            "num" => $v1->num
                        ]; 
                    }
                }

                if ($count){
                    $tradesTmp[] = [
                        "out_trade_no" => $v->out_trade_no,
                        "date" => $v->updated_at->format('Y-m-d H:i:s'),
                        "trade_id" => $v->id,
                        "charge" => $v->total_fee,
                        "count" => $count,
                        "detail" => $childtradesTmp,
                        "status" => '待收货'
                        ];
                }
            }
            return  $tradesTmp;
        }
    }

    protected function getStatus($paystatus,$sendstatus,$finishstatus) {
        if ($paystatus == 0){
            return '待付款';
        }else if ($paystatus == 1){
            if ($sendstatus == 0){
                return '待发货';
            }else if ($sendstatus == 1){
                if ($finishstatus == 0){
                    return '待收货';
                }else{
                    return '已完成';
                }
            }
        }
    }

    protected function getColor($paystatus,$sendstatus,$finishstatus) {
        if ($paystatus == 0){
            return 'red';
        }else if ($paystatus == 1){
            if ($sendstatus == 0){
                return 'orange';
            }else if ($sendstatus == 1){
                if ($finishstatus == 0){
                    return 'blue';
                }else{
                    return 'green';
                }
            }
        }
    }

    public function onPayShopping(Request $req) {
        $zhang = Zhang::getZhang($req->get('shop_id'));
        $urlLogin = "https://api.weixin.qq.com/sns/jscode2session";
        $paramsLogin = [
        	'appid' => $zhang->a,
            'secret' => $zhang->b,
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
                $shopping = Shopping::shoppingSelect($req->get('detail_id'));
                $urlPay = "https://api.mch.weixin.qq.com/pay/unifiedorder";
                $params = [
                    'appid' => $paramsLogin["appid"],
                    'body' => $shopping->name,
                    'mch_id' => $zhang->c,
                    'nonce_str' => $this->createRand(32),
                    'notify_url' => "https://www.hattonstar.com/onPayBack",
                    'openid' => $openid,
                    'out_trade_no'=> $this->createTradeNo(),
                    'spbill_create_ip' => $req->getClientIp(),
                    'total_fee' => $req->get('total_fee') * 100 ,
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
                    $sign = $this->createSign($stringA,$zhang->d);


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
                 \Log::info("-----------pay", [$data]);
                 $trade = [
                    'out_trade_no' => $params["out_trade_no"],
                    'body' => $params["body"],
                    'detail_id' => $req->get('detail_id'),
                    'total_fee' => $params["total_fee"] * 0.01,
                    'wx_id' => $req->get('wx_id'),
                    'shop_id' => $req->get('shop_id'),
                    'name' => $req->get('name'),
                    'share_id' => $req->get('share_id'),
                    'use_royalty' => $req->get('use_royalty')
                 ];
                 $tradeNew = Trade::payInsertForId($trade);
                 $childtrade = [
                    'shopping_id' => $req->get('detail_id'),
                    'num' => $req->get('num'),
                    'trade_id' => $tradeNew->id
                 ];
                 Childtrade::payInsert($childtrade);
                 $this->insertAddress($req->get('address_id'),$tradeNew->id);
                 $resultPay = GuzzleHttp:: postXml($urlPay, $data);
                 $decode = $this->decodeXml($resultPay);
                 if ($decode["result_code"] == "SUCCESS")
                 {
                    $sian_time = (string)time();
                    $resign = $this->createReSign($decode,$sian_time,$zhang->d);
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

    public function onPayShoppingFix(Request $req) {
        $zhang = Zhang::getZhang($req->get('shop_id'));
        $urlLogin = "https://api.weixin.qq.com/sns/jscode2session";
        $paramsLogin = [
        	'appid' => $zhang->a,
            'secret' => $zhang->b,
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
                $shopping = Shopping::shoppingSelect($req->get('detail_id'));
                $urlPay = "https://api.mch.weixin.qq.com/pay/unifiedorder";
                $params = [
                    'appid' => $paramsLogin["appid"],
                    'body' => $shopping->name,
                    'mch_id' => $zhang->c,
                    'nonce_str' => $this->createRand(32),
                    'notify_url' => "https://www.hattonstar.com/onPayBack",
                    'openid' => $openid,
                    'out_trade_no'=> $this->createTradeNo(),
                    'spbill_create_ip' => $req->getClientIp(),
                    'total_fee' => $req->get('total_fee') * 100 ,
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
                    $sign = $this->createSign($stringA,$zhang->d);


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
                 \Log::info("-----------pay", [$data]);
                 $trade = [
                    'out_trade_no' => $params["out_trade_no"],
                    'body' => $params["body"],
                    'detail_id' => $req->get('detail_id'),
                    'total_fee' => $params["total_fee"] * 0.01,
                    'wx_id' => $req->get('wx_id'),
                    'shop_id' => $req->get('shop_id'),
                    'name' => $req->get('name'),
                    'share_id' => $req->get('share_id'),
                    'use_royalty' => $req->get('use_royalty')
                 ];
                 $tradeNew = Trade::payInsertForId($trade);
                 $childtrade = [
                    'shopping_id' => $req->get('detail_id'),
                    'num' => $req->get('num'),
                    'trade_id' => $tradeNew->id
                 ];
                 Childtrade::payInsert($childtrade);
                 $this->insertAddressFix($req->get('address_id'),$tradeNew->id);
                 $resultPay = GuzzleHttp:: postXml($urlPay, $data);
                 $decode = $this->decodeXml($resultPay);
                 if ($decode["result_code"] == "SUCCESS")
                 {
                    $sian_time = (string)time();
                    $resign = $this->createReSign($decode,$sian_time,$zhang->d);
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

    public function onPayForCertFix(Request $req) {
        $zhang = Zhang::getZhang($req->get('shop_id'));
        $urlLogin = "https://api.weixin.qq.com/sns/jscode2session";
        $paramsLogin = [
            'appid' => $zhang->a,
            'secret' => $zhang->b,
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
                $urlPay = "https://api.mch.weixin.qq.com/pay/unifiedorder";
                $params = [
                    'appid' => $paramsLogin["appid"],
                    'body' => $req->get('body'),
                    'mch_id' =>  $zhang->c,
                    'nonce_str' => $this->createRand(32),
                    'notify_url' => "https://www.hattonstar.com/onPayBack",
                    'openid' => $openid,
                    'out_trade_no'=> $this->createTradeNo(),
                    'spbill_create_ip' => $req->getClientIp(),
                    'total_fee' => $req->get('charge') * 100,
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
                    $sign = $this->createSign($stringA,$zhang->d);


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
                 \Log::info("-----------pay", [$data]);
                 $trade = [
                    'out_trade_no' => $params["out_trade_no"],
                    'body' => $params["body"],
                    'detail_id' => 0,
                    'total_fee' => $params["total_fee"] * 0.01,
                    'wx_id' => $req->get('wx_id'),
                    'shop_id' => $this->getShopIdByCert($req->get('certInfo')),
                    'name' => $req->get('name'),
                    'share_id' => $req->get('share_id'),
                    'use_royalty' => $req->get('use_royalty')
                 ];
                 $tradeNew = Trade::payInsertForId($trade);
                 $this->certsInsert($req->get('certInfo'), $tradeNew->id);
                 $this->insertAddressFix($req->get('address_id'),$tradeNew->id);
                 $resultPay = GuzzleHttp:: postXml($urlPay, $data);
                 $decode = $this->decodeXml($resultPay);
                 if ($decode["result_code"] == "SUCCESS")
                 {
                    $sian_time = (string)time();
                    $resign = $this->createReSign($decode,$sian_time,$zhang->d);
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

    public function onRePay(Request $req) {
        $zhang = Zhang::getZhang($req->get('shop_id'));
        $urlLogin = "https://api.weixin.qq.com/sns/jscode2session";
        $paramsLogin = [
        	'appid' => $zhang->a,
            'secret' => $zhang->b,
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
                $trade = Trade::paySelectById($req->get('trade_id'));
                $urlPay = "https://api.mch.weixin.qq.com/pay/unifiedorder";
                $params = [
                    'appid' => $paramsLogin["appid"],
                    'body' => $trade->body,
                    'mch_id' =>  $zhang->c,
                    'nonce_str' => $this->createRand(32),
                    'notify_url' => "https://www.hattonstar.com/onPayBack",
                    'openid' => $openid,
                    'out_trade_no'=> $trade->out_trade_no,
                    'spbill_create_ip' => $req->getClientIp(),
                    'total_fee' => $trade->total_fee * 100,
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
                    $sign = $this->createSign($stringA,$zhang->d);


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
                 \Log::info("-----------repay", [$data]);
                 $resultPay = GuzzleHttp:: postXml($urlPay, $data);
                 $decode = $this->decodeXml($resultPay);
                 if ($decode["result_code"] == "SUCCESS")
                 {
                    $sian_time = (string)time();
                    $resign = $this->createReSign($decode,$sian_time,$zhang->d);
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

    protected function insertAddress($id,$trade_id) {
        $address = Address::GetAddressById($id);
        $params =[
            'name' => $address->name,
            'phone' => $address->phone,
            'province' => $address->province,
            'city' => $address->city,
            'area' => $address->area,
            'detail' => $address->detail, 
            'trade_id' => $trade_id
        ];
        SendAddress::addressInsert($params);
    }

    protected function insertAddressFix($id,$trade_id) {
        $params =[
            'fixed_id' => $id,
            'trade_id' => $trade_id
        ];
        SendAddress::addressInsertFix($params);
    }

    public function onPayForCert(Request $req) {
        $zhang = Zhang::getZhang($req->get('shop_id'));
        $urlLogin = "https://api.weixin.qq.com/sns/jscode2session";
        $paramsLogin = [
            'appid' => $zhang->a,
            'secret' => $zhang->b,
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
                $urlPay = "https://api.mch.weixin.qq.com/pay/unifiedorder";
                $params = [
                    'appid' => $paramsLogin["appid"],
                    'body' => $req->get('body'),
                    'mch_id' => $zhang->c,
                    'nonce_str' => $this->createRand(32),
                    'notify_url' => "https://www.hattonstar.com/onPayBack",
                    'openid' => $openid,
                    'out_trade_no'=> $this->createTradeNo(),
                    'spbill_create_ip' => $req->getClientIp(),
                    'total_fee' => $req->get('charge') * 100,
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
                    $sign = $this->createSign($stringA,$zhang->d);


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
                 \Log::info("-----------pay", [$data]);
                 $trade = [
                    'out_trade_no' => $params["out_trade_no"],
                    'body' => $params["body"],
                    'detail_id' => 0,
                    'total_fee' => $params["total_fee"] * 0.01,
                    'wx_id' => $req->get('wx_id'),
                    'shop_id' => $this->getShopIdByCert($req->get('certInfo')),
                    'name' => $req->get('name'),
                    'share_id' => $req->get('share_id'),
                    'use_royalty' => $req->get('use_royalty')
                 ];
                 $tradeNew = Trade::payInsertForId($trade);
                 $this->certsInsert($req->get('certInfo'), $tradeNew->id);
                 $this->insertAddress($req->get('address_id'),$tradeNew->id);
                 $resultPay = GuzzleHttp:: postXml($urlPay, $data);
                 $decode = $this->decodeXml($resultPay);
                 if ($decode["result_code"] == "SUCCESS")
                 {
                    $sian_time = (string)time();
                    $resign = $this->createReSign($decode,$sian_time,$zhang->d);
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

    protected function certsInsert($certInfo,$trade_id) {
        $arryCert = preg_split("/@/",$certInfo);
        $charge = 0;
        foreach ($arryCert as $v) {
            $item = $v;
            $arryItem = preg_split("/,/",$item);
            $id = $arryItem[0];
            $num = $arryItem[1];
            $childtrade = [
                'shopping_id' => $id,
                'num' => $num,
                'trade_id' => $trade_id
             ];
            Childtrade::payInsert($childtrade);
        }
    }

    protected function getShopIdByCert($certInfo) {
        $arryCert = preg_split("/@/",$certInfo);
        foreach ($arryCert as $v) {
            $item = $v;
            $arryItem = preg_split("/,/",$item);
            $id = $arryItem[0];
            $shopping = Shopping::shoppingSelect($id);
            return $shopping->shop_id;
        }
    }

    public function hideOrder(Request $req) {
        $id = $req->get('id');
        $trade = Trade::hideOrder($id);
        if ($trade) {
            return  $trade;
        }
    }

    public function postRefund(Request $req) {
        $trade = Trade::postRefund($req->get('id'),$req->get('refund_status'));
        if ($trade) {
            return  $trade;
        }
    }

    public function getOrderRefundForPerson(Request $req) {
        $trades = Trade::getOrderRefundForPerson($req->get('wx_id'));
        $title = "title";
        if ($trades){
            $tradesTmp = [];
            foreach ($trades as $k => $v) {
                $count = 0;
                $childtrades = Childtrade::paySelectById($v->id);
                $childtradesTmp = [];
                foreach ($childtrades as $k1 => $v1) {
                    $shopping = Shopping::shoppingSelect($v1->shopping_id);
                    if ($shopping){
                        $count += 1;
                        $childtradesTmp[] = [
                            "name" => $shopping->name,
                            "charge" => $shopping->price,
                            "title_pic" => Image::GetImageUrlByParentId($shopping->id,$title,$shopping->type),
                            "shopping_id" => $shopping->id,
                            "num" => $v1->num
                        ]; 
                    }
                }

                if ($count){
                    $tradesTmp[] = [
                        "time" => $v->updated_at->format('Y-m-d H:i:s'),
                        "tradeid" => $v->out_trade_no,
                        "charge" => $v->total_fee,
                        "count" => $count,
                        "detail" => $childtradesTmp,
                        "address" => SendAddress::GetAddressEx($v->id),
                        "status" => $this->getRefundStatus($v->finish_refund_status),
                        "color" => $this->getRefundColor($v->finish_refund_status),
                        "phone" =>  $v->phone,
                        "body" => $v->body,
                        "id" => $v->id,
                        "use_royalty" => $v->use_royalty                
                    ];
                }
            }
            $result_data = [
                'code' => 0,
                'msg' => '',
                'count' => count($tradesTmp),
                'data' => $tradesTmp
            ];
            return $result_data;
        }
    }

    public function getShareForPerson(Request $req) {
        $member = Member::memberSelect($req->get('wx_id'));
        $trades = Trade::getShareForPerson($req->get('wx_id'));
        $tradesTmp = [];
        foreach ($trades as $k => $v) {
            $tradesTmp[] = [
                "time" => $v->updated_at->format('Y-m-d H:i:s'),
                "tradeid" => $v->out_trade_no,
                "charge" => $v->total_fee,
                "body" => $v->body,
                "nikename" => Wxuser::getNameById($v->wx_id),
                "royalty" => $v->royalty
            ];
        }
        $tradesUse = Trade::getShareUseForPerson($req->get('wx_id'));
        $tradesUseTmp = [];
        foreach ($tradesUse as $k1 => $v1) {
            $tradesUseTmp[] = [
                "time" => $v1->updated_at->format('Y-m-d H:i:s'),
                "tradeid" => $v1->out_trade_no,
                "charge" => $v1->total_fee,
                "body" => $v1->body,
                "use_royalty" => $v1->use_royalty
            ];
        }
        $result_data = [
            'code' => 0,
            'msg' => '',
            'count' => count($tradesTmp),
            'royalty' => $member->royalty,
            'data' => $tradesTmp,
            'dataTrade' => $tradesUseTmp
        ];
        return $result_data;
    }

    protected function getRefundStatus($finish_status) {
        if ($finish_status == 0){
            return '待处理';
        }else {
            return '已处理';
        }
    }

    protected function getRefundColor($finish_status) {
        if ($finish_status == 0){
            return 'red';
        }else {
            return 'green';
        }
    }

    public function onPayShoppingFree(Request $req) {
        $shopping = Shopping::shoppingSelect($req->get('detail_id'));
        $zhang = Zhang::getZhang($shopping->shop_id);
        $params = [
            'out_trade_no' => $this->createTradeNo(),
            'body' => $shopping->name,
            'detail_id' => $shopping->id,
            'wx_id' => $req->get('wx_id'),
            'shop_id' => $shopping->shop_id,
            'name' => $req->get('name'),
            'share_id' => $req->get('share_id'),
            'use_royalty' => $req->get('use_royalty')
            ];
        $trade =Trade::payInsertFree($params,$zhang->pay_status);
        $childtrade = [
            'shopping_id' => $shopping->id,
            'num' => $req->get('num'),
            'trade_id' => $trade->id
         ];
        Childtrade::payInsert($childtrade);
        if ($zhang->pay_status == 1){
            $this->insertAddress($req->get('address_id'),$trade->id);
        } else if (($zhang->pay_status == 2) || ($zhang->pay_status == 3)){
            $this->insertAddressFix($req->get('address_id'),$trade->id);
        }
        $this->doForme($trade);
        return $trade;
    }

    public function onPayrCertFree(Request $req) {
        $shop_id = $this->getShopIdByCert($req->get('certInfo'));
        $zhang = Zhang::getZhang($shop_id);
        $shopping = Shopping::shoppingSelect($req->get('detail_id'));
        $params = [
            'out_trade_no' => $this->createTradeNo(),
            'body' => $req->get('body'),
            'detail_id' => 0,
            'wx_id' => $req->get('wx_id'),
            'shop_id' => $shop_id,
            'name' => $req->get('name'),
            'share_id' => $req->get('share_id'),
            'use_royalty' => $req->get('use_royalty')
            ];
        $trade =Trade::payInsertFree($params,$zhang->pay_status);
        $this->certsInsert($req->get('certInfo'), $trade->id);
        if ($zhang->pay_status == 1){
            $this->insertAddress($req->get('address_id'),$trade->id);
        } else if (($zhang->pay_status == 2) || ($zhang->pay_status == 3)){
            $this->insertAddressFix($req->get('address_id'),$trade->id);
        }
        $this->doForme($trade);
        return $trade;
    }

    protected function doForme($trade) {
        $royalty = 0;
        $integral = 0;
        $childtrades = Childtrade::paySelectById($trade->id);
        $childtradesTmp = [];
        foreach ($childtrades as $k => $v) {
            $shopping = Shopping::shoppingSelect($v->shopping_id);
            if ($shopping){
                $royalty += $shopping->royalty * $v->num;
                $royalty = number_format($royalty,2);
                $integral += $shopping->integral * $v->num;
                Shopping::updateStock($v->shopping_id,$v->num);
            }
        }
        if (($trade->share_id != 0) && ($trade->share_id != $trade->wx_id)){
            if ($royalty != 0){
                $member = Member::memberUpdateRoyalty($trade->share_id,$royalty);
            }
        }
        if ($integral != 0){
            $member = Member::memberUpdateIntegral($trade->wx_id,$integral);
        }
        if ($trade->use_royalty != 0){
            $member = Member::memberUpdateRoyaltySell($trade->wx_id,$trade->use_royalty);
        }
    }

    public function repayStock(Request $req) {
        $str = '';
        $index = 0;
        $childtrades = Childtrade::paySelectById($req->get('trade_id'));
        foreach ($childtrades as $k => $v) {
            $shopping = Shopping::shoppingSelect($v->shopping_id);
            if ($shopping){
                if (($shopping->stock == 0) || ($shopping->stock < $v->num)){
                    $str = $str . $shopping->name;
                    $str = $str . ',';
                    $index ++;
                }
            }
        }
        if ($index){
            $str = rtrim($str, ",");
         }
         return [
            'result' => $index,
            'str' => $str
         ];
    }
}