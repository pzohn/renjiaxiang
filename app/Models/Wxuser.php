<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class  Wxuser extends Model {
        
    public $timestamps = false;

    public static function getInfo($openid,$shop_id) {
        $wxuser = Wxuser::where("openid", $openid)->where("shop_id", $shop_id)->first();
        if ($wxuser) {
            return $wxuser;
        }
        return 0;
    }

    public static function getNameById($id) {
        $wxuser = Wxuser::where("id", $id)->first();
        if ($wxuser) {
            return $wxuser->nikename;
        }
        return "";
    }

    public static function updateBaseInfo($params) {
        $wxuser = Wxuser::where("id", array_get($params,"id"))->first();
        if ($wxuser) {
            $wxuser->nikename = array_get($params,"nikename");
            $wxuser->url = array_get($params,"url");
            $wxuser->update();
            return $wxuser;
        }
    }

    public static function insertInfo($openid,$shop_id) {
        $wxuser = new self;
        $wxuser->openid = $openid;
        $wxuser->shop_id = $shop_id;
        $wxuser->save();
        return $wxuser;
    }
}