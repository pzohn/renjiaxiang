<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class  Signature extends Model {
    
    public $timestamps = false;

    public static function DelImageUrl($id,$file,$shop_id) {
        Signature::where("id", $id))->delete();
    }

    public static function urlInsert($params) {
        $signature = new self;
        $signature->wx_id = array_get($params,"wx_id");
        $signature->url = array_get($params,"url");
        $signature->file = array_get($params,"file");
        $signature->shop_id = array_get($params,"shop_id");
        $signature->save();
        return $signature;
    }

    public static function getWxUrl($params) {
        $signature = Signature::where("wx_id", array_get($params,"wx_id"))->where("shop_id", array_get($params,"shop_id"))->first();
        if ($signature){
            return $signature;
        }
    }
}