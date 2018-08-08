<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class  Trade extends Model {
        
    public static function payInsert($params) {

        $trade = new self;
        $trade->out_trade_no = array_get($params,"out_trade_no");
        $trade->body = array_get($params,"body");
        $trade->detail_id = array_get($params,"detail_id");
        $trade->total_fee = array_get($params,"total_fee");
        $trade->phone = array_get($params,"phone");
        $trade->save();
        return $trade;
    }

    public static function payUpdate($params) {
        $trade = Trade::where("phone", array_get($params,"phone"))->first();
        if ($trade) {
            $trade->pay_status = array_get($params,"pay_status");
            $trade->update();
            return $trade;
        }
    }

    public static function paySelect($params) {
        $trade = Trade::where("phone", array_get($params,"phone"))->first();
        if ($trade) {
            return $trade;
        }
    }
}