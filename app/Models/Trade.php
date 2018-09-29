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
        $trade->shop_id = array_get($params,"shop_id");
        $trade->name = array_get($params,"name");
        $trade->save();
        return $trade;
    }

    public static function payUpdate($out_trade_no) {
        $trade = Trade::where("out_trade_no", $out_trade_no)->first();
        if ($trade) {
            if($trade->pay_status == 1)
            {
                return $trade;
            }
            $trade->pay_status = 1;
            $trade->edit_flag = 1;
            $trade->update();
            return $trade;
        }
    }

    public static function paySelect($out_trade_no) {
        $trade = Trade::where("out_trade_no", $out_trade_no)->first();
        if ($trade) {
            return $trade;
        }
    }

    public static function getShopTotal($id) {
        $trade = Trade::where("shop_id", $id)->get();
        if ($trade) {
            return count($trade);
        }
    }

    public static function getShopDay($id) {
        $trade = Trade::where("shop_id", $id)
            ->whereBetween("updated_at", date("Y-m-d 00:00:00"), date("Y-m-d 23:59:59"))
            ->get();
        if ($trade) {
            return count($trade);
        }
    }

    public static function getShopMonth($id) {
        $begin = date("Y-m-d H:i:s", mktime(0,0,0,date('m'),1,date('Y')));
        $ended = date("Y-m-d H:i:s", mktime(23,59,59,date('m'),date('t'),date('Y')));
        $trade = Trade::where("shop_id", $id)
            ->whereBetween("updated_at", $begin, $ended)
            ->get();
        if ($trade) {
            return count($trade);
        }
    }
}