<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Libs\GuzzleHttp;

class PayController extends Controller
{
    public function onPay(Request $req) {
        $urlLogin = "https://api.weixin.qq.com/sns/jscode2session";
        $params = [
        	'appid' => $req->get('appid'),
            'secret' => $req->get('secret'),
            'js_code' => $req->get('js_code'),
            'grant_type' => $req->get('grant_type'),
        ];
        try {
            \Log::info("----------", [$params]);
            $resultLogin = GuzzleHttp::guzzleGet($urlLogin, $params);
            \Log::info("==========", $resultLogin);

            if (isset($resultLogin['errcode'])) {
                return $resultLogin['errcode'];
            }
            $openid = $resultLogin['openid'];
            $session_key = $resultLogin['session_key'];

            if ($openid && $session_key) {
                $urlPay = "https://api.mch.weixin.qq.com/pay/unifiedorder";
                $params = [
                    'appid' => $req->get('appid'),
                    'body' => "JSAPI支付测试",
                    'mch_id' => $req->get('mch_id'),
                    'nonce_str' => $this->createRand(32),
                    'notify_url' => "https://www.hattonstar.com/onPayBack",
                    'openid' => $openid,
                    'out_trade_no'=> $req->get('out_trade_no'),
                    'spbill_create_ip' => $req->getClientIp(),
                    'total_fee' => $req->get('total_fee'),
                    'trade_type' => "JSAPI",
                    ];

                    ksort($params);

                    $stringA = "";
                    foreach ($params as $k => $v) {
                        $stringA = $stringA . "&" . $k . "=" . $v;
                    }
                    $stringA = ltrim($stringA, "&");

                    $appid = $req->get('appid');
                    $mch_id = $req->get('mch_id');
                    $nonce_str = $params["nonce_str"];
                    $body = "JSAPI支付测试";
                    $out_trade_no = $req->get('out_trade_no');
                    $total_fee = $req->get('total_fee');
                    $spbill_create_ip = $req->getClientIp();
                    $notify_url = "https://www.hattonstar.com/onPayBack";
                    $trade_type = "JSAPI";
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
                 $resultPay = GuzzleHttp:: postXml($urlPay, $data);
                 $decode = $this->decodeXml($resultPay);
                 $resign = $this->createReSign($decode);
                 return $this->wxBack($decode,$resign);
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
        // return [
            
        // ];
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

    protected function createReSign($req) {

        $params = [
            'appId' => $req['appid'],
            'nonceStr' => $req['nonce_str'],
            'package' => "prepay_id=" . $req['prepay_id'],
            'signType' => "MD5",
            'timeStamp' => "1533390092",
            ];

            ksort($params);

        $stringA = "";
        foreach ($params as $k => $v) {
            $stringA = $stringA . "&" . $k . "=" . $v;
        }

        $StringTmp = ltrim($stringA, "&");
        /*
        $params1 = [
            'appId' => $req['appid'],
            'nonceStr' => $req['nonce_str'],
            ];
        ksort($params1);
        $stringA = "";
        foreach ($params1 as $k => $v) {
            $stringA = $stringA . "&" . $k . "=" . $v;
        }
        $StringTmp = $stringA . "&" . "package=prepay_id=";
        $StringTmp = $StringTmp . $req['prepay_id']. "&";

        $params2 = [
            'signType' => "MD5",
            'timeStamp' => "1533390092",
            'key' => "renzheng840728chengboren15081900",
            ];
        //ksort($params2);
        $stringB = "";
        foreach ($params2 as $k => $v) {
            $stringB = $stringB . "&" . $k . "=" . $v;
        }

        $stringB=ltrim($stringB, "&");

        $StringTmp = $StringTmp . $stringB;
        */
        $resign = $this->createSign($StringTmp);
        return $resign;
    }

    public function onPayBack(Request $req) {
        return $req;
    }

    public function wxBack($decode,$resign) {
        return [
            "timeStamp" => "1533390092",
            "nonceStr"  => $decode['nonce_str'],
            "package" => "prepay_id=" . $decode['prepay_id'],
            "signType" => "MD5",
            "paySign" => $resign,
        ];
    }
}