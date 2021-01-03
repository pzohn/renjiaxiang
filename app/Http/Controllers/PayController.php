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
use App\Models\Express;

class PayController extends Controller
{
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

    public function getOrderAllForShop(Request $req) {
        $shop_id = $req->get('shop_id');
        $trades = Trade::getOrderAllForShop($req->get('shop_id'));
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
                        "status" => $this->getStatus($v->pay_status,$v->send_status,$v->finish_status),
                        "color" => $this->getColor($v->pay_status,$v->send_status,$v->finish_status),
                        "phone" =>  $v->phone,
                        "body" => $v->body,
                        "id" => $v->id,
                        "use_royalty" => $v->use_royalty,
                        "express" => Express::getExpressNum($v->id)
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
                        $retail_price = $v1->retail_price;
                        if ($retail_price == 0){
                            $retail_price = $shopping->price;
                        }
                        $childtradesTmp[] = [
                            "name" => $shopping->name,
                            "charge" => $retail_price,
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
                        "status" => $this->getStatus($v->pay_status,$v->send_status,$v->finish_status),
                        "color" => $this->getColor($v->pay_status,$v->send_status,$v->finish_status),
                        "phone" =>  $v->phone,
                        "body" => $v->body,
                        "id" => $v->id,
                        "use_royalty" => $v->use_royalty,
                        "express" => Express::getExpressNum($v->id)
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

    public function getOrderAllForShopManger(Request $req) {
        $shop_id = $req->get('shop_id');
        $trades = Trade::getOrderAllForShop($shop_id);
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
                        "status" => $this->getStatusZhaobo($v->pay_status,$v->send_status,$v->finish_status),
                        "color" => $this->getColorZhaobo($v->pay_status,$v->send_status,$v->finish_status),
                        "phone" =>  $v->phone,
                        "body" => $v->body,
                        "id" => $v->id,
                        "use_royalty" => $v->use_royalty,
                        "express" => Express::getExpressNum($v->id)
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

    public function getOrderAllForShopMangerUnFinish(Request $req) {
        $shop_id = $req->get('shop_id');
        $trades = Trade::getOrderAllForShopEx($shop_id);
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
                        "status" => $this->getStatusZhaobo($v->pay_status,$v->send_status,$v->finish_status),
                        "color" => $this->getColorZhaobo($v->pay_status,$v->send_status,$v->finish_status),
                        "phone" =>  $v->phone,
                        "body" => $v->body,
                        "id" => $v->id,
                        "use_royalty" => $v->use_royalty,
                        "express" => Express::getExpressNum($v->id)
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

    public function getOrderAllForShopMangerFinish(Request $req) {
        $shop_id = $req->get('shop_id');
        $trades = Trade::getOrderAllForShopEx1($shop_id);
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
                        "status" => $this->getStatusZhaobo($v->pay_status,$v->send_status,$v->finish_status),
                        "color" => $this->getColorZhaobo($v->pay_status,$v->send_status,$v->finish_status),
                        "phone" =>  $v->phone,
                        "body" => $v->body,
                        "id" => $v->id,
                        "use_royalty" => $v->use_royalty,
                        "express" => Express::getExpressNum($v->id)
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
        return;
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
                            "charge" => $shopping->price ,
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
                            "charge" => $shopping->price ,
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
                        "use_royalty" => $v->use_royalty,
                        "express" => Express::getExpressNum($v->id)                
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
                            "charge" => $shopping->price ,
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
                        "use_royalty" => $v->use_royalty,
                        "express" => Express::getExpressNum($v->id)                
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
                    return 'green';;
                }
            }
        }
    }

    protected function getStatusZhaobo($paystatus,$sendstatus,$finishstatus) {
        if ($paystatus == 0){
            return '待付款';
        }else if ($paystatus == 1){
            if ($sendstatus == 0 && $finishstatus == 0){
                return '待发货';
            }else if ($sendstatus == 0 && $finishstatus == 1){
                return '已完成';
            }
            else if ($sendstatus == 1){
                if ($finishstatus == 0){
                    return '线下交易待确认';
                }else if ($finishstatus == 1){
                    return '已完成';
                }
            }
        }
    }

    protected function getColorZhaobo($paystatus,$sendstatus,$finishstatus) {
        if ($paystatus == 0){
            return 'red';
        }else if ($paystatus == 1){
            if ($sendstatus == 0 && $finishstatus == 0){
                return 'orange';
            }else if ($sendstatus == 0 && $finishstatus == 1){
                return 'green';
            }else if ($sendstatus == 1){
                if ($finishstatus == 0){
                    return 'blue';
                }else if ($finishstatus == 1){
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
                 $vip_flag = $req->get('vip_flag');
                 $retail_price = $shopping->price;
                 if ($vip_flag == 1){
                    $retail_price = $shopping->vip_price;
                 }
                 $childtrade = [
                    'shopping_id' => $req->get('detail_id'),
                    'num' => $req->get('num'),
                    'trade_id' => $tradeNew->id,
                    'retail_price' => $retail_price
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

    public function onPayShoppingDown(Request $req) {
        $shopping = Shopping::shoppingSelect($req->get('detail_id'));
        $trade = [
            'out_trade_no' => $this->createTradeNo(),
            'body' => $shopping->name,
            'detail_id' => $req->get('detail_id'),
            'total_fee' => $req->get('total_fee'),
            'wx_id' => $req->get('wx_id'),
            'shop_id' => $req->get('shop_id'),
            'name' => $req->get('name'),
            'share_id' => $req->get('share_id'),
            'use_royalty' => $req->get('use_royalty')
         ];
         $tradeNew = Trade::payInsertForIdDown($trade);
         $vip_flag = $req->get('vip_flag');
         $retail_price = $shopping->price;
         if ($vip_flag == 1){
            $retail_price = $shopping->vip_price;
         }
         $childtrade = [
            'shopping_id' => $req->get('detail_id'),
            'num' => $req->get('num'),
            'trade_id' => $tradeNew->id,
            'retail_price' => $retail_price
         ];
         Childtrade::payInsert($childtrade);
         $this->insertAddress($req->get('address_id'),$tradeNew->id);
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
                 $vip_flag = $req->get('vip_flag');
                 $retail_price = $shopping->price;
                 if ($vip_flag == 1){
                    $retail_price = $shopping->vip_price;
                 }
                 $childtrade = [
                    'shopping_id' => $req->get('detail_id'),
                    'num' => $req->get('num'),
                    'trade_id' => $tradeNew->id,
                    'retail_price' => $retail_price
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
            $vip_flag = $req->get('vip_flag');
            $shopping = Shopping::shoppingSelect($req->get($id));
            $retail_price = $shopping->price;
            $vip_flag = $req->get('vip_flag');
            if ($vip_flag == 1){
                $retail_price = $shopping->vip_price;
            } 
            $childtrade = [
                'shopping_id' => $id,
                'num' => $num,
                'trade_id' => $trade_id,
                'retail_price' => $retail_price
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
        $wx_id = 0;
        if ($req->get('wx_id')){
            $wx_id = $req->get('wx_id');
        }
        $trade = Trade::hideOrder($id,$wx_id);
        if ($trade) {
            return  $trade;
        }
    }

    public function finishOrder(Request $req) {
        $id = $req->get('id');
        $wx_id = 0;
        if ($req->get('wx_id')){
            $wx_id = $req->get('wx_id');
        }
        $trade = Trade::finishOrder($id,$wx_id);
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

    public function getShareForZhaobo(Request $req) {
        $parter = Parter::getParterForWx($req->get('wx_id'));
        $dateflag = $req->get('dateflag');
        $date_begin = date("Y-m-d H:i:s", mktime(0,0,0,date('m'),1,date('Y')));
        $date_after = date("Y-m-d H:i:s", mktime(23,59,59,date('m'),date('t'),date('Y')));
        if ($dateflag == 1){
            $date_begin = $req->get('date_after') . " " . date("H:i:s", mktime(0,0,0));
            $date_after = $req->get('date_begin') . " " . date("H:i:s", mktime(23,59,59));
        }
        $share_count = 0;
        $one_flag = 0;
        if ($parter){
            $share_id = $parter->id;
            $trades = Trade::getShareForPersonEx3($share_id,$date_begin,$date_after);
            $tradesOne = [];
            $tradesTwo = [];
            foreach ($trades as $k => $v) {
                $address = SendAddress::GetAddress($v->id);
                $trade_addr = "";
                $trade_phone = "";
                $trade_name = "";
                if ($address){
                    $trade_addr = $address->province.$address->city.$address->area.$address->detail; 
                    $trade_phone = $address->phone;
                    $trade_name = $address->name;
                }
                $childtrades = Childtrade::paySelectById($v->id);
                $share_count += $childtrades[0]->num;
                $tradesOne[] = [
                    "time" => $v->created_at->format('Y-m-d H:i:s'),
                    "tradeid" => $v->out_trade_no,
                    "charge" => $v->total_fee,
                    "body" => $v->body,
                    "trade_name" => $trade_name,
                    "trade_phone" => $trade_phone,
                    "trade_addr" => $trade_addr,
                    "num" => $childtrades[0]->num
                ];
            }

            $trades = Trade::getShareForPersonEx4($share_id,$date_begin,$date_after);
            foreach ($trades as $k => $v) {
                $address = SendAddress::GetAddress($v->id);
                $trade_addr = "";
                $trade_phone = "";
                $trade_name = "";
                if ($address){
                    $trade_addr = $address->province.$address->city.$address->area.$address->detail; 
                    $trade_phone = $address->phone;
                    $trade_name = $address->name;
                }
                $childtrades = Childtrade::paySelectById($v->id);
                $share_count += $childtrades[0]->num;
                $tradesOne[] = [
                    "time" => $v->created_at->format('Y-m-d H:i:s'),
                    "tradeid" => $v->out_trade_no,
                    "charge" => $v->total_fee,
                    "body" => $v->body,
                    "trade_name" => $trade_name,
                    "trade_phone" => $trade_phone,
                    "trade_addr" => $trade_addr,
                    "num" => $childtrades[0]->num
                ];
            }

            if ($parter->share_parent_id == 1){
                $one_flag = 1;
                $parters = Parter::getPartersForParent($share_id);
                foreach ($parters as $k1 => $v1) {
                    $share_two_id = $v1->id;
                    $share_name = $v1->name;
                    $trades_Two = Trade::getShareForPersonEx3($share_two_id,$date_begin,$date_after);
                    foreach ($trades_Two as $k2 => $v2) {
                        $childtrades = Childtrade::paySelectById($v2->id);
                        $share_count += $childtrades[0]->num;
                        $address = SendAddress::GetAddress($v2->id);
                        $trade_addr = "";
                        $trade_phone = "";
                        $trade_name = "";
                        if ($address){
                            $trade_addr = $address->province.$address->city.$address->area.$address->detail; 
                            $trade_phone = $address->phone;
                            $trade_name = $address->name;
                        }
                        $tradesTwo[] = [
                            "time" => $v2->created_at->format('Y-m-d H:i:s'),
                            "tradeid" => $v2->out_trade_no,
                            "charge" => $v2->total_fee,
                            "body" => $v2->body,
                            "trade_name" => $trade_name,
                            "trade_phone" => $trade_phone,
                            "trade_addr" => $trade_addr,
                            "share_name" => $share_name,
                            "num" => $childtrades[0]->num
                        ];
                    }

                    $trades_Two = Trade::getShareForPersonEx4($share_two_id,$date_begin,$date_after);
                    foreach ($trades_Two as $k2 => $v2) {
                        $childtrades = Childtrade::paySelectById($v2->id);
                        $share_count += $childtrades[0]->num;
                        $address = SendAddress::GetAddress($v2->id);
                        $trade_addr = "";
                        $trade_phone = "";
                        $trade_name = "";
                        if ($address){
                            $trade_addr = $address->province.$address->city.$address->area.$address->detail; 
                            $trade_phone = $address->phone;
                            $trade_name = $address->name;
                        }
                        $tradesTwo[] = [
                            "time" => $v2->created_at->format('Y-m-d H:i:s'),
                            "tradeid" => $v2->out_trade_no,
                            "charge" => $v2->total_fee,
                            "body" => $v2->body,
                            "trade_name" => $trade_name,
                            "trade_phone" => $trade_phone,
                            "trade_addr" => $trade_addr,
                            "share_name" => $share_name,
                            "num" => $childtrades[0]->num
                        ];
                    }
                }
            }

            $result_data = [
                'code' => 0,
                'msg' => '返回成功',
                'one_flag' => $one_flag,
                'count' => $share_count,
                'First_count' => count($tradesOne),
                'Second_count' => count($tradesTwo),
                'tradesOne' => $tradesOne,
                'tradesTwo' => $tradesTwo
            ];
            return $result_data;

        }else {
            return [
                'code' => 1,
                'msg' => '该分销商暂未绑定'
            ];
        }
    }

    public function getShareForZhaoboEx(Request $req) {
        $dateflag = $req->get('dateflag');
        $date_begin = date("Y-m-d H:i:s", mktime(0,0,0,date('m'),1,date('Y')));
        $date_after = date("Y-m-d H:i:s", mktime(23,59,59,date('m'),date('t'),date('Y')));
        if ($dateflag == 1){
            $date_begin = $req->get('date_begin') . " " . date("H:i:s", mktime(0,0,0));
            $date_after = $req->get('date_after') . " " . date("H:i:s", mktime(23,59,59));
        }
        $share_count = 0;
        $tradesTwo = [];
        $parters = Parter::getParterForWxEx();
        if ($parters){
            foreach ($parters as $k1 => $v1) {
                $share_two_id = $v1->id;
                $share_name = $v1->name;
                $trades_Two = Trade::getShareForPersonEx3($share_two_id,$date_begin,$date_after);
                foreach ($trades_Two as $k2 => $v2) {
                    $childtrades = Childtrade::paySelectById($v2->id);
                    $share_count += $childtrades[0]->num;
                    $address = SendAddress::GetAddress($v2->id);
                    $trade_addr = "";
                    $trade_phone = "";
                    $trade_name = "";
                    if ($address){
                        $trade_addr = $address->province.$address->city.$address->area.$address->detail; 
                        $trade_phone = $address->phone;
                        $trade_name = $address->name;
                    }
                    $tradesTwo[] = [
                        "time" => $v2->created_at->format('Y-m-d H:i:s'),
                        "tradeid" => $v2->out_trade_no,
                        "charge" => $v2->total_fee,
                        "body" => $v2->body,
                        "trade_name" => $trade_name,
                        "trade_phone" => $trade_phone,
                        "trade_addr" => $trade_addr,
                        "share_name" => $share_name,
                        "num" => $childtrades[0]->num
                    ];
                }

                $trades_Two = Trade::getShareForPersonEx4($share_two_id,$date_begin,$date_after);
                foreach ($trades_Two as $k2 => $v2) {
                    $childtrades = Childtrade::paySelectById($v2->id);
                    $share_count += $childtrades[0]->num;
                    $address = SendAddress::GetAddress($v2->id);
                    $trade_addr = "";
                    $trade_phone = "";
                    $trade_name = "";
                    if ($address){
                        $trade_addr = $address->province.$address->city.$address->area.$address->detail; 
                        $trade_phone = $address->phone;
                        $trade_name = $address->name;
                    }
                    $tradesTwo[] = [
                        "time" => $v2->created_at->format('Y-m-d H:i:s'),
                        "tradeid" => $v2->out_trade_no,
                        "charge" => $v2->total_fee,
                        "body" => $v2->body,
                        "trade_name" => $trade_name,
                        "trade_phone" => $trade_phone,
                        "trade_addr" => $trade_addr,
                        "share_name" => $share_name,
                        "num" => $childtrades[0]->num
                    ];
                }
            }
            $result_data = [
                'code' => 0,
                'msg' => '返回成功',
                'count' => $share_count,
                'tradesTwo' => $tradesTwo
            ];
            return $result_data;
        }
    }

    public function getShareForZhaoboEx1(Request $req) {
        $dateflag = $req->get('dateflag');
        $date_begin = date("Y-m-d H:i:s", mktime(0,0,0,date('m'),1,date('Y')));
        $date_after = date("Y-m-d H:i:s", mktime(23,59,59,date('m'),date('t'),date('Y')));
        if ($dateflag == 1){
            $date_begin = $req->get('date_begin') . " " . date("H:i:s", mktime(0,0,0));
            $date_after = $req->get('date_after') . " " . date("H:i:s", mktime(23,59,59));
        }
        $share_count = 0;
        $tradesTwo = [];
        $parter = Parter::getParterForId($req->get('second_id'));
        if ($parter){
            $share_two_id = $parter->id;
            $share_name = $parter->name;
            $trades_Two = Trade::getShareForPersonEx3($share_two_id,$date_begin,$date_after);
            foreach ($trades_Two as $k2 => $v2) {
                $childtrades = Childtrade::paySelectById($v2->id);
                $share_count += $childtrades[0]->num;
                $address = SendAddress::GetAddress($v2->id);
                $trade_addr = "";
                $trade_phone = "";
                $trade_name = "";
                if ($address){
                    $trade_addr = $address->province.$address->city.$address->area.$address->detail; 
                    $trade_phone = $address->phone;
                    $trade_name = $address->name;
                }
                $tradesTwo[] = [
                    "time" => $v2->created_at->format('Y-m-d H:i:s'),
                    "tradeid" => $v2->out_trade_no,
                    "charge" => $v2->total_fee,
                    "body" => $v2->body,
                    "trade_name" => $trade_name,
                    "trade_phone" => $trade_phone,
                    "trade_addr" => $trade_addr,
                    "share_name" => $share_name,
                    "num" => $childtrades[0]->num
                ];
            }

            $trades_Two = Trade::getShareForPersonEx4($share_two_id,$date_begin,$date_after);
            foreach ($trades_Two as $k2 => $v2) {
                $childtrades = Childtrade::paySelectById($v2->id);
                $share_count += $childtrades[0]->num;
                $address = SendAddress::GetAddress($v2->id);
                $trade_addr = "";
                $trade_phone = "";
                $trade_name = "";
                if ($address){
                    $trade_addr = $address->province.$address->city.$address->area.$address->detail; 
                    $trade_phone = $address->phone;
                    $trade_name = $address->name;
                }
                $tradesTwo[] = [
                    "time" => $v2->created_at->format('Y-m-d H:i:s'),
                    "tradeid" => $v2->out_trade_no,
                    "charge" => $v2->total_fee,
                    "body" => $v2->body,
                    "trade_name" => $trade_name,
                    "trade_phone" => $trade_phone,
                    "trade_addr" => $trade_addr,
                    "share_name" => $share_name,
                    "num" => $childtrades[0]->num
                ];
            }
        $result_data = [
            'code' => 0,
            'msg' => '返回成功',
            'count' => $share_count,
            'tradesTwo' => $tradesTwo
        ];
        return $result_data;
        }
    }

    public function getShareForZhaoboEx2(Request $req) {
        $parter = Parter::getParterForId($req->get('first_id'));
        $dateflag = $req->get('dateflag');
        $date_begin = date("Y-m-d H:i:s", mktime(0,0,0,date('m'),1,date('Y')));
        $date_after = date("Y-m-d H:i:s", mktime(23,59,59,date('m'),date('t'),date('Y')));
        if ($dateflag == 1){
            $date_begin = $req->get('date_begin') . " " . date("H:i:s", mktime(0,0,0));
            $date_after = $req->get('date_after') . " " . date("H:i:s", mktime(23,59,59));
        }
        $share_count = 0;
        $one_flag = 0;
        if ($parter){
            $share_id = $parter->id;
            $trades = Trade::getShareForPersonEx3($share_id,$date_begin,$date_after);
            $tradesOne = [];
            $tradesTwo = [];
            foreach ($trades as $k => $v) {
                $address = SendAddress::GetAddress($v->id);
                $trade_addr = "";
                $trade_phone = "";
                $trade_name = "";
                if ($address){
                    $trade_addr = $address->province.$address->city.$address->area.$address->detail; 
                    $trade_phone = $address->phone;
                    $trade_name = $address->name;
                }
                $childtrades = Childtrade::paySelectById($v->id);
                $share_count += $childtrades[0]->num;
                $tradesOne[] = [
                    "time" => $v->created_at->format('Y-m-d H:i:s'),
                    "tradeid" => $v->out_trade_no,
                    "charge" => $v->total_fee,
                    "body" => $v->body,
                    "trade_name" => $trade_name,
                    "trade_phone" => $trade_phone,
                    "trade_addr" => $trade_addr,
                    "num" => $childtrades[0]->num
                ];
            }

            $trades = Trade::getShareForPersonEx4($share_id,$date_begin,$date_after);
            foreach ($trades as $k => $v) {
                $address = SendAddress::GetAddress($v->id);
                $trade_addr = "";
                $trade_phone = "";
                $trade_name = "";
                if ($address){
                    $trade_addr = $address->province.$address->city.$address->area.$address->detail; 
                    $trade_phone = $address->phone;
                    $trade_name = $address->name;
                }
                $childtrades = Childtrade::paySelectById($v->id);
                $share_count += $childtrades[0]->num;
                $tradesOne[] = [
                    "time" => $v->created_at->format('Y-m-d H:i:s'),
                    "tradeid" => $v->out_trade_no,
                    "charge" => $v->total_fee,
                    "body" => $v->body,
                    "trade_name" => $trade_name,
                    "trade_phone" => $trade_phone,
                    "trade_addr" => $trade_addr,
                    "num" => $childtrades[0]->num
                ];
            }

            if ($parter->share_parent_id == 1){
                $one_flag = 1;
                $parters = Parter::getPartersForParent($share_id);
                foreach ($parters as $k1 => $v1) {
                    $share_two_id = $v1->id;
                    $share_name = $v1->name;
                    $trades_Two = Trade::getShareForPersonEx3($share_two_id,$date_begin,$date_after);
                    foreach ($trades_Two as $k2 => $v2) {
                        $childtrades = Childtrade::paySelectById($v2->id);
                        $share_count += $childtrades[0]->num;
                        $address = SendAddress::GetAddress($v2->id);
                        $trade_addr = "";
                        $trade_phone = "";
                        $trade_name = "";
                        if ($address){
                            $trade_addr = $address->province.$address->city.$address->area.$address->detail; 
                            $trade_phone = $address->phone;
                            $trade_name = $address->name;
                        }
                        $tradesTwo[] = [
                            "time" => $v2->created_at->format('Y-m-d H:i:s'),
                            "tradeid" => $v2->out_trade_no,
                            "charge" => $v2->total_fee,
                            "body" => $v2->body,
                            "trade_name" => $trade_name,
                            "trade_phone" => $trade_phone,
                            "trade_addr" => $trade_addr,
                            "share_name" => $share_name,
                            "num" => $childtrades[0]->num
                        ];
                    }

                    $trades_Two = Trade::getShareForPersonEx4($share_two_id,$date_begin,$date_after);
                    foreach ($trades_Two as $k2 => $v2) {
                        $childtrades = Childtrade::paySelectById($v2->id);
                        $share_count += $childtrades[0]->num;
                        $address = SendAddress::GetAddress($v2->id);
                        $trade_addr = "";
                        $trade_phone = "";
                        $trade_name = "";
                        if ($address){
                            $trade_addr = $address->province.$address->city.$address->area.$address->detail; 
                            $trade_phone = $address->phone;
                            $trade_name = $address->name;
                        }
                        $tradesTwo[] = [
                            "time" => $v2->created_at->format('Y-m-d H:i:s'),
                            "tradeid" => $v2->out_trade_no,
                            "charge" => $v2->total_fee,
                            "body" => $v2->body,
                            "trade_name" => $trade_name,
                            "trade_phone" => $trade_phone,
                            "trade_addr" => $trade_addr,
                            "share_name" => $share_name,
                            "num" => $childtrades[0]->num
                        ];
                    }
                }
            }

            $result_data = [
                'code' => 0,
                'msg' => '返回成功',
                'one_flag' => $one_flag,
                'count' => $share_count,
                'First_count' => count($tradesOne),
                'Second_count' => count($tradesTwo),
                'tradesOne' => $tradesOne,
                'tradesTwo' => $tradesTwo
            ];
            return $result_data;

        }else {
            return [
                'code' => 1,
                'msg' => '该分销商暂未绑定'
            ];
        }
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
        $vip_flag = $req->get('vip_flag');
        $retail_price = $shopping->price;
        if ($vip_flag == 1){
            $retail_price = $shopping->vip_price;
        }
        $childtrade = [
            'shopping_id' => $shopping->id,
            'num' => $req->get('num'),
            'trade_id' => $trade->id,
            'retail_price' => $retail_price
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

    public function getTradesInfoByShopId(Request $req) {
        $params = [
            'tradeid' => $req->get('tradeid'),
            'shop_id' => $req->get('shop_id'),
            'name' => $req->get('name'),
            'status' => $req->get('status')
            ];

        $trades = Trade::getTradesInfoByShopId($params);
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
                            "num" => $v1->num
                        ]; 
                    }
                }

                if ($count){
                    $tradesTmp[] = [
                        "time" => $v->updated_at->format('Y-m-d H:i:s'),
                        "tradeid" => $v->out_trade_no,
                        "charge" => $v->total_fee,
                        "detail" => $childtradesTmp,
                        "address" => SendAddress::GetAddressEx($v->id),
                        "status" => $this->getNewStatus($v->finish_status,$v->post_refund_status,$v->finish_refund_status),
                        "statusex" => $this->getNewStatusEx($v->send_status,$v->finishstatus,$v->post_refund_status,$v->finish_refund_status),
                        "body" => $v->body,
                        "id" => $v->id,
                        "use_royalty" => $v->use_royalty,
                        "name" => $v->name             
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

    protected function getNewStatus($finishstatus,$post_refund_status,$finish_refund_status) {
        if ($post_refund_status == 1){
            if ($finish_refund_status == 1){
                return 5;//完成退货
            }else{
                return 4;//申请退货
            }
        }else{
            if ($finishstatus == 0){
                return 1;//已付款
            }else {
                return 3;//'已完成'
            }
        }
    }

    protected function getNewStatusEx($sendstatus,$finishstatus,$post_refund_status,$finish_refund_status) {
        if ($post_refund_status == 1){
            if ($finish_refund_status == 1){
                return 5;
            }else{
                return 4;
            }
        }else{
            if ($sendstatus == 0){
                return 1;
            }else {
                if ($finishstatus == 0){
                    return 2;//已发货
                }else{
                    return 3;
                }
            }
        }
    }

    public function updateStatus(Request $req) {
        return Trade::updateStatus($req->get('id'),$req->get('status'));
    }
}