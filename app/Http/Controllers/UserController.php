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
use App\Models\Parter;
use App\Models\Childexpress;
use App\Libs\GuzzleHttp;
use App\Models\Usermanager;
use App\Models\Submitlocation;
use App\Models\Shopping;

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

    public function IsShareForZhaobo(Request $req) {
        $parter = Parter::getParterForWx($req->get('wx_id'));
        $parter1 = Parter::getParterForWxNoSig($req->get('wx_id'));
        $usermanager = Usermanager::getMangerForWx($req->get('wx_id'),$req->get('shop_id'),1);
        $usermanager1 = Usermanager::getMangerForWx($req->get('wx_id'),$req->get('shop_id'),2);
        if ($usermanager){
            return 2;
        }
        if ($usermanager1){
            return 3;
        }
        if ($parter){
            return 1;
        }
        if ($parter1){
            return 4;
        }
        return 0;
    }

    public function GetShareForUser(Request $req) {
        $parter = Parter::getParterForIdNoDel($req->get('share_id'));
        if ($parter){
            return 1;
        }
        return 0;
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
            'school' => $req->get('school'),
            'area' => $req->get('area'),
            'class' => $req->get('class'),
            'grade' => $req->get('grade'),
            'card' => $req->get('card'),
            'nation' => $req->get('nation'),
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
                $host = "https://wuliu.market.alicloudapi.com";//访问链接
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
                //curl_setopt($curl, CURLOPT_HEADER, true);如不输出json, 请打开这行代码，打印调试头部状态码
                //状态码: 200 正常 400 URL无效 401 appCode错误 403 次数用完  500 API网管错误
                if (1 == strpos("$".$host, "https://"))
                {
                    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
                }
                $jsonStr = curl_exec($curl);
                $result = json_decode($jsonStr);     
                if ($result->status == 0){
                    $param = [
                        'id' => $exprss->id,
                        'deliverystatus' => intval($result->result->deliverystatus),
                        'issign' => intval($result->result->issign),
                        'expName' => $result->result->expName,
                        'expSite' => $result->result->expSite,
                        'expPhone' => $result->result->expPhone,
                        'updateTime' => $result->result->updateTime,
                        'takeTime' => $result->result->takeTime,
                        'logo' => $result->result->logo
                    ];
                    Express::expressUpdate($param);
                    Childexpress::childdelete($exprss->id);
                    foreach ($result->result->list as $k => $v) {
                        if (Childexpress::getChildFlag( $exprss->id,$v->time) == 0){
                            $paramList = [
                                'parent_id' => $exprss->id,
                                'time_desc' => $v->time,
                                'status_desc' => $v->status
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

    public function getExpressById(Request $req) {
        $exprss = Express::getExpressById($req->get('id'));
        $childexpresses = Childexpress::getchildexpresses($exprss->id);
        $list = [];
        if (count($childexpresses)){
            foreach ($childexpresses as $k => $v) {
                $list[] = [
                    "status" => $v->status_desc,
                    "time" => $v->time_desc
                ];
            }
        }
        return [
            'number' => $exprss->number,
            'expName' => $exprss->expName,
            'expSite' => $exprss->expSite,
            'expPhone' => $exprss->expPhone,
            'updateTime' => $exprss->updateTime,
            'takeTime' => $exprss->takeTime,
            'logo' => $exprss->logo,
            'deliverystatus' => $exprss->deliverystatus,
            'issign' => $exprss->issign,
            'list' => $list
        ];
    }

    public function getAreasFirst(Request $req) {
        $second = false;
        $parters = Parter::getAreasFirst();
        if ($req->get('wx_id') != 0){
            $parters = Parter::getAreasFirstEx($req->get('wx_id'));
        }
        $shoppings = Shopping::shoppingGetByType($req->get('type_id'),$req->get('shop_id'));
        $shoppingsTmp = [];
        if ($shoppings){
            foreach ($shoppings as $k3 => $v3) {
                $shoppingsTmp[] = [
                "id" => $v3->id,
                "name" => $v3->name,
                ];
            }
        }
        if ($parters){
            $partersFirst = [];
            foreach ($parters as $k => $v) {
                if ($v->share_parent_id == 1){
                    $parters2 = Parter::getPartersForParent($v->id);
                    $partersSecond = [];
                    foreach ($parters2 as $k1 => $v1) {
                        $partersSecond[] = [
                            "name" => $v1->name,
                            "id" => $v1->id
                        ]; 
                    }
                    $partersFirst[] = [
                        "name" => $v->name,
                        "id" => $v->id,
                        "count" => count($partersSecond),
                        "Second" => $partersSecond   
                    ];
                }else{
                    $second = true;
                    $parter1 =  Parter::getParterForId($v->id);
                    $parter2 =  Parter::getParterForId($parter1->share_parent_id);
                    $partersSecond[] = [
                        "name" => $v->name,
                        "id" => $v->id
                    ];
                    $partersFirst[] = [
                        "name" => $parter2->name,
                        "id" => $parter2->id,
                        "count" => 1,
                        "Second" => $partersSecond
                    ]; 
                }
            }
            return [
                'code' => 0,
                'msg' => '查询成功',
                'count' => count($partersFirst),
                'data' => $partersFirst,
                'second_flag' => $second,
                'shoppings' => $shoppingsTmp
            ];
        }
        else{
            return [
                'code' => 1,
                'msg' => '查询失败'
            ];
        }
    }

    public function Submitlocation(Request $req) {
        \Log::info("-----------Submitlocation", [$req]);
        if ( $req->get('action') == 'submitlocation'){
            $params = [
                "deviceid" => $req->get('deviceid'),
                "longitude" => $req->get('longitude'),
                "latitude" => $req->get('latitude'),
                "lbsinfo" => $req->get('lbsinfo'),
                "address" => $req->get('address'),
                "mt" => $req->get('mt'),
                "addresstype" => $req->get('addresstype')
            ];
            $submitlocation = Submitlocation::submitlocationInsert($params);
            if ($submitlocation){
                return 1;
            }
        }
        return 0;
    }
}