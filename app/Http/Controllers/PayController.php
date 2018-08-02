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
            $resultLogin = GuzzleHttp::guzzleGet($urlLogin, $params);
            $errMsg = $resultLogin->get('errcode');
            if($errMsg)
            {
                return 0;
            }
            $openid = $resultLogin->get('openid');
            $session_key = $resultLogin->get('session_key');
            if($openid && $session_key)
            {
                $urlPay = "https://api.mch.weixin.qq.com/pay/unifiedorder";
                $params = [
                    'appid' => $req->get('appid'),
                    'body' => "JSAPI支付测试",
                    'mch_id' => $req->get('mch_id'),
                    'nonce_str' => createRand(32),
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
                    $nonce_str = createRand(32);
                    $body = "JSAPI支付测试";
                    $out_trade_no = $req->get('out_trade_no');
                    $total_fee = $req->get('total_fee');
                    $spbill_create_ip = $req->getClientIp();
                    $notify_url = "https://www.hattonstar.com/onPayBack";
                    $trade_type = "JSAPI";
                    $sign = createSign($stringA);


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

                 $resultPay = GuzzleHttp:: postXml($urlPay, $data);
            }

        } catch (\Exception $e) {
            // 异常处理
        }
        return $resultPay;
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
        $stringSignTemp = $stringA . "&key=renzheng840728chengboren15081900";
        $sign = strtoupper(md5($stringSignTemp));
        return $sign;
    }

    public function onPayBack(Request $req) {
        return $req;
    }
}