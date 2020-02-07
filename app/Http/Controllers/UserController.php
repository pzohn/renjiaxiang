<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Information;
use Qcloud\Sms\SmsSingleSender;
use App\Models\Address;
use App\Models\Member;
use App\Models\Wxuser;
use App\Models\Zhang;
use App\Models\Express;
use App\Models\Childexpress;
use App\Libs\GuzzleHttp;

class UserController extends Controller
{
    public function setUser(Request $req) {
       $return_data = Information::getAndSet($req->all());
       return $return_data;
    }

    public function mangerLogin(Request $req) {
        $return_data = Information::mangerLogin($req->all());
        return $return_data;
     }

    public function getUpdateResult(Request $req) {
        $return_data = Information::getInformation($req->all());
        return $return_data;
    }

    public function resetPass(Request $req) {
        $return_data = Information::resetPass($req->all());
        return $return_data;
    }

    public function loginByPhone(Request $req) {
        $appid = 1400184176;
        $appkey = 'c5f98a9fd6a8828dea964516fc98e574';
        $phone = $req->get('phone');
        $templateId = 295943;
        $smsSign = '';
        $code = $this->createRand(4);
        try {
            $sender = new SmsSingleSender($appid, $appkey);
            $params = [$code];
            $result = $sender->sendWithParam("86", $phone, $templateId, $params, $smsSign, "", "");
            $res = json_decode($result, true);
            $data = $res["result"];
            if ($data == 0){
                return $code;
            }
            return "0000";
        } catch (\Exception $e) {
            var_dump($e);
        }	
    }

    protected function createRand($length) {
        $str='0123456789';
        $len=strlen($str)-1;
        $randstr='';
        for($i=0;$i<$length;$i++){
        $num=mt_rand(0,$len);
        $randstr .= $str[$num];
        }
        return $randstr;
    }

    public function getAddress(Request $req) {
        $address = Address::GetAddress($req->get('login_id'));
        if ($address){
            return $address;
        }
    }

    public function getAddressById(Request $req) {
        $address = Address::GetAddressById($req->get('id'));
        if ($address){
            return $address;
        }
    }

    public function getAddressByLoginId(Request $req) {
        $address = Address::GetAddressByLoginId($req->get('login_id'));
        return $address;
    }

    public function insertAddress(Request $req) {
        $params = [
            "name" => $req->get('name'),
            "phone" => $req->get('phone'),
            "province" => $req->get('province'),
            "city" => $req->get('city'),
            "area" => $req->get('area'),
            "detail" => $req->get('detail'),
            "login_id" => $req->get('login_id')
        ];
        $address = Address::addressInsert($params);
        if ($address){
            return $address;
        }
    }

    public function updateAddress(Request $req) {
        $params = [
            "name" => $req->get('name'),
            "phone" => $req->get('phone'),
            "province" => $req->get('province'),
            "city" => $req->get('city'),
            "area" => $req->get('area'),
            "detail" => $req->get('detail'),
            "login_id" => $req->get('login_id'),
            "id" => $req->get('id')
        ];
        $address = Address::addressUpdate($params);
        if ($address){
            return $address;
        }
    }

    public function delAddress(Request $req) {
        Address::addressDel($req->get('id'));
        return 1;
    }

    public function collect(Request $req) {
        $collect_flag = $req->get('collect_flag');
        $wx_id = $req->get('wx_id');
        $detail_id = $req->get('detail_id');
        $iscollect = $this->iscollect($req);
        if ($iscollect == $collect_flag)
            return $iscollect;
        $collect_ids = Member::memberSelectById($wx_id)->collect_ids;
        $collect_idsTmp = "";
        if ($collect_flag){
            if ($collect_ids == ""){
                $collect_idsTmp = strval($detail_id);
            }else{
                $collect_idsTmp = $collect_ids . "@" . strval($detail_id);
            }
        }else{
            if (strpos($collect_ids, '@') !== false){
                $arry = preg_split("/@/",$collect_ids);
                $arryTmp = [];
                foreach ($arry as $k => $v) {
                    $id = intval($v);
                    if ($id != $detail_id){
                        $arryTmp[] = $v;
                    }
                    $collect_idsTmp = implode("@",$arryTmp);
                }
            }else{
                $collect_idsTmp = "";
            }
        }
        Member::CollectUpdateById($wx_id,$collect_idsTmp);
        return $this->iscollect($req);
    }

    public function iscollect(Request $req) {
        $wx_id = $req->get('wx_id');
        $detail_id = $req->get('detail_id');
        $member = Member::memberSelectById($wx_id);
        if (!$member)
            return 0;
        $collect_ids = $member->collect_ids;
        if ($collect_ids == "")
            return 0;
        if (strpos($collect_ids, '@') !== false){
            $arry = preg_split("/@/",$collect_ids);
            $flag = false;
            foreach ($arry as $k => $v) {
                $id = intval($v);
                if ($id == $detail_id){
                    $flag = true;
                }
            }
            if ($flag){
                return 1;
            }else{
                return 0;
            }
        }else{
            $id = intval($collect_ids);
            if ($id == $detail_id){
                return 1;
            }else{
                return 0;
            }
        }
    }

    public function getCollect(Request $req) {
        $wx_id = $req->get('wx_id');
        $member = Member::memberSelectById($wx_id);
        if (!$member)
            return 0;
        $collect_ids = $member->collect_ids;
        if ($collect_ids == "")
            return 0;
        return $collect_ids;
    }

    public function getWxUser(Request $req) {
        $zhang = Zhang::getZhang($req->get('shop_id'));
        if ($zhang){
            $urlLogin = "https://api.weixin.qq.com/sns/jscode2session";
            $paramsLogin = [
                'appid' => $zhang->a,
                'secret' => $zhang->b,
                'js_code' => $req->get('js_code'),
                'grant_type' => "authorization_code",
            ];
            $resultLogin = GuzzleHttp::guzzleGet($urlLogin, $paramsLogin);
            $openId = $resultLogin["openid"];
            $wxuser = Wxuser::getInfo($openId,$req->get('shop_id'));
            if (!$wxuser){
                $wxuser = Wxuser::insertInfo($openId,$req->get('shop_id'));
            }
            $member = Member::memberSelectById($wxuser->id);
            if (!$member) {
                Member::memberInsertId($wxuser->id);
            }
            return $wxuser;
        } else {
            return 0;
        }

    }

    public function updateWxBaseInfo(Request $req) {
        $params = [
            'id' => $req->get('id'),
            'nikename' => $req->get('nikename'),
            'url' => $req->get('url')
        ];
        $wxuser = Wxuser::updateBaseInfo($params);
        return $wxuser;
    }

    public function memberSelect(Request $req) {
        $member = Member::memberSelect($req->get('wx_id'));
        if ($member){
            return $member;
        }
    }

    public function memberUpdate(Request $req) {
        $params = [
            'phone' => $req->get('phone'),
            'name' => $req->get('name'),
            'email' => $req->get('email'),
            'sex' => $req->get('sex'),
            'age' => $req->get('age'),
            'wx_id' => $req->get('wx_id')
            ];
        $member = Member::memberUpdateWxId($params);
        if ($member){
            return $member;
        }
    }

    public function getBaseInfo(Request $req) {
        $member = Member::memberSelect($req->get('wx_id'));
        if ($member){
            $name = $member->name;
            $phone = $member->phone;
            $b1 = (!ctype_space($name) && !empty($name));
            $b2 = (!ctype_space($phone) && !empty($phone));
            $b = 1;
            if ($b1 && $b2){
                $b = 0;
            }
            return $b;
        } else {
            return 1;
        }
    }

    public function BaseInfoUpdate(Request $req) {
        $params = [
            'phone' => $req->get('phone'),
            'name' => $req->get('name'),
            'wx_id' => $req->get('wx_id')
            ];
        $member = Member::baseInfoUpdateWxId($params);
        if ($member){
            return $member;
        }
    }

    public function getExpress(Request $req) {
        $number = $req->get('number');
        $exprss = Express::getExpressByNum($number);
        if ($exprss){
            if (($exprss->deliverystatus >= 3) || ($exprss->issign == 1)){
                return $exprss->id;       
            }
            $zhang = Zhang::getZhang($req->get('shop_id'));
            $appcode = $zhang->e;
            if ($appcode){
                $host = "https://wuliu.market.alicloudapi.com";//api访问链接
                $path = "/kdi";//API访问后缀
                $method = "GET";
                $headers = array();
                array_push($headers, "Authorization:APPCODE " . $appcode);
                $querys = "no=" . $number;  //参数写在这里
                $bodys = "";
                $url = $host . $path . "?" . $querys;//url拼接
            
                $curl = curl_init();
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
                curl_setopt($curl, CURLOPT_URL, $url);
                curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($curl, CURLOPT_FAILONERROR, false);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_HEADER, false);
                //curl_setopt($curl, CURLOPT_HEADER, true); 如不输出json, 请打开这行代码，打印调试头部状态码。
                //状态码: 200 正常；400 URL无效；401 appCode错误； 403 次数用完； 500 API网管错误
                if (1 == strpos("$".$host, "https://"))
                {
                    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
                }
                $result = curl_exec($curl);
                if ($result->status == 0){
                    $param = [
                        'id' => $exprss->id,
                        'deliverystatus' => intval($result->deliverystatus),
                        'issign' => intval($result->issign),
                        'expName' => $result->expName,
                        'expSite' => $result->expSite,
                        'expPhone' => $result->expPhone,
                        'updateTime' => $result->updateTime,
                        'takeTime' => $result->takeTime,
                        'logo' => $result->logo
                    ];
                    Express::expressUpdate($param);
                    foreach ($result->result->list as $k => $v) {
                        if (Childexpress::getChildFlag( $exprss->id,$v->time) == 0){
                            $paramList = [
                                'parent_id' => $exprss->id,
                                'time_desc' => $v->time,
                                'status_desc' => $v->time_desc
                            ];
                            Childexpress::childexpressInsert($paramList);
                        }
                    }
                    return $exprss->id;
                }
            }
        }
        return -1;
    }
}