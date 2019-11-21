<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
//use App\Models\User;
use App\Models\Information;
use Qcloud\Sms\SmsSingleSender;
use App\Models\Address;

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
}