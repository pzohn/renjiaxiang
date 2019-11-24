<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class  Wxuser extends Model {
        
    public $timestamps = false;

    public static function getInfo($openid) {
        $wxuser = Wxuser::where("openid", $openid)->first();
        if ($wxuser) {
            return $wxuser;
        }
        return 0;
    }

    public static function updateInfo($params) {
        $wxuser = Wxuser::where("openid", array_get($params,"openid"))->first();
        if ($wxuser) {
            $wxuser->nikename = array_get($params,"nikename");
            $wxuser->url = array_get($params,"url");
            $wxuser->update();
            return $wxuser;
        }
    }

    public static function insertInfo($params) {
        $wxuser = new self;
        $wxuser->openid = array_get($params,"openid");
        $wxuser->nikename = array_get($params,"nikename");
        $wxuser->url = array_get($params,"url");
        $wxuser->save();
        return $wxuser;
    }
}