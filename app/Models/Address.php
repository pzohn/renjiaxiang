<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class  Address extends Model {
        
    public $timestamps = false;

    public static function GetAddress($id) {
        $address = Address::where("login_id", $id)->first();
        if ($address) {
            return $address;
        }
    }

    public static function GetAddressByLoginId($login_id) {
        $address = Address::where("login_id", $login_id)->first();
        if ($address) {
            return $address;
        }else {
            return 0;
        }
    }

    public static function GetAddressByLoginIdEx($login_id) {
        $address = Address::where("login_id", $login_id)->get();
        return $address;
    }

    public static function GetAddressById($id) {
        $address = Address::where("id", $id)->first();
        if ($address) {
            return $address;
        }
    }

    public static function addressInsert($params) {
        $address = new self;
        $address->name = array_get($params,"name");
        $address->phone = array_get($params,"phone");
        $address->province = array_get($params,"province");
        $address->city = array_get($params,"city");
        $address->area = array_get($params,"area");
        $address->login_id = array_get($params,"login_id");
        $address->detail = array_get($params,"detail");
        $address->save();
        return $address;
    }

    public static function addressUpdate($params) {
        $address = Address::where("id", array_get($params,"id"))->first();
        if ($address) {
            $address->name = array_get($params,"name");
            $address->phone = array_get($params,"phone");
            $address->province = array_get($params,"province");
            $address->city = array_get($params,"city");
            $address->area = array_get($params,"area");
            $address->detail = array_get($params,"detail");
            $address->update();
            return $address;
        }
    }

    public static function updateAddressDefault($params) {
        Address::where("login_id", array_get($params,"login_id"))->update(['default_flag'=>0]);
        $address = Address::where("id", array_get($params,"id"))->first();
        if ($address) {
            $address->default_flag = 1;
            $address->update();
            return $address;
        }
    }

    public static function addressDel($id) {
        $address = Address::where("id", $id)->first();
        if ($address){
            Address::where("id", $id)->delete();
        }
    }

}