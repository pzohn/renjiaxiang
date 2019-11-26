<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Information;
use Qcloud\Sms\SmsSingleSender;
use App\Models\Address;
use App\Models\Member;
use App\Models\Wxuser;
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
        $member = Member::memberSelectById($phone);
        if (!$member)
            return 0;
        $collect_ids = $member->collect_ids;
        if ($collect_ids == "")
            return 0;
        return $collect_ids;
    }

    public function getWxUser(Request $req) {
        $urlLogin = "https://api.weixin.qq.com/sns/jscode2session";
        $paramsLogin = [
        	'appid' => "wx8d32477fdd368d9a",
            'secret' => "d46d773f17e3f483e0673ec5b22aaa10",
            'js_code' => $req->get('js_code'),
            'grant_type' => "authorization_code",
        ];
        $resultLogin = GuzzleHttp::guzzleGet($urlLogin, $paramsLogin);
        $openId = $resultLogin["openid"];
        $wxuser = Wxuser::getInfo($openId);
        if (!$wxuser){
            $wxuser = Wxuser::insertInfo($openId);
        }
        return $wxuser;
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
}