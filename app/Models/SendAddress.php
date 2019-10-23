<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class  SendAddress extends Model {
        
    public $timestamps = false;

    public static function GetAddress($id) {
        $sendAddress = SendAddress::where("trade_id", $id)->first();
        if ($sendAddress) {
            return $sendAddress;
        }
    }

    public static function addressInsert($params) {
        $sendAddress = new self;
        $sendAddress->name = array_get($params,"name");
        $sendAddress->phone = array_get($params,"phone");
        $sendAddress->province = array_get($params,"province");
        $sendAddress->city = array_get($params,"city");
        $sendAddress->area = array_get($params,"area");
        $sendAddress->trade_id = array_get($params,"trade_id");
        $sendAddress->detail = array_get($params,"detail");
        $sendAddress->save();
        return $sendAddress;
    }
}