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

    public static function payInsertForId($params) {

        $trade = new self;
        $trade->out_trade_no = array_get($params,"out_trade_no");
        $trade->body = array_get($params,"body");
        $trade->detail_id = array_get($params,"detail_id");
        $trade->total_fee = array_get($params,"total_fee");
        $trade->wx_id = array_get($params,"wx_id");
        $trade->shop_id = array_get($params,"shop_id");
        $trade->name = array_get($params,"name");
        $trade->save();
        return $trade;
    }

    public static function payInsertGroup($params) {

        $trade = new self;
        $trade->out_trade_no = array_get($params,"out_trade_no");
        $trade->body = array_get($params,"body");
        $trade->detail_id = array_get($params,"detail_id");
        $trade->total_fee = array_get($params,"total_fee");
        $trade->phone = array_get($params,"phone");
        $trade->group_id = array_get($params,"shop_id");
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

    public static function paySelectById($id) {
        $trade = Trade::where("id", $id)->first();
        if ($trade) {
            return $trade;
        }
    }

    public static function getShopDay($id) {
        $trade = Trade::where("shop_id", $id)
            ->whereBetween("updated_at", [date("Y-m-d 00:00:00"), date("Y-m-d 23:59:59")])
            ->get();
        if ($trade) {
            return count($trade);
        }
    }

    public static function getShopMonth($id) {
        $begin = date("Y-m-d H:i:s", mktime(0,0,0,date('m'),1,date('Y')));
        $ended = date("Y-m-d H:i:s", mktime(23,59,59,date('m'),date('t'),date('Y')));
        $trade = Trade::where("shop_id", $id)
            ->whereBetween("updated_at", [$begin, $ended])
            ->get();
        if ($trade) {
            return count($trade);
        }
    }

    public static function getTrades() {
        $trades = Trade::get();
        if ($trades) {
            return $trades;
        }
    }

    public static function getOrderAll() {
        $trades = Trade::orderBy('updated_at', 'desc')->get();
        if ($trades) {
            return $trades;
        }
    }

    public static function getOrderAllForPerson($wx_id) {
        $trades = Trade::where("wx_id", $wx_id)->where("show_status", 1)->orderBy('updated_at', 'desc')->get();
        if ($trades) {
            return $trades;
        }
    }

    public static function getOrderUnPay() {
        $trades = Trade::where("pay_status", 0)->orderBy('updated_at', 'desc')->get();
        if ($trades) {
            return $trades;
        }
    }

    public static function getOrderUnPayForPerson($wx_id) {
        $trades = Trade::where("wx_id", $wx_id)->where("show_status", 1)->where("pay_status", 0)->orderBy('updated_at', 'desc')->get();
        if ($trades) {
            return $trades;
        }
    }

    public static function getOrderUnsendForPerson($wx_id) {
        $trades = Trade::where("wx_id", $wx_id)->where("show_status", 1)->where("pay_status", 1)->where("send_status", 0)->orderBy('updated_at', 'desc')->get();
        if ($trades) {
            return $trades;
        }
    }

    public static function getOrderUnreceiveForPerson($phone) {
        $trades = Trade::where("phone", $phone)->where("show_status", 1)->where("pay_status", 1)->where("send_status", 1)->where("finish_status", 0)->orderBy('updated_at', 'desc')->get();
        if ($trades) {
            return $trades;
        }
    }

    public static function getOrderFinishForPerson($wx_id) {
        $trades = Trade::where("wx_id", $wx_id)->where("show_status", 1)->where("pay_status", 1)->where("send_status", 1)->where("finish_status", 1)->orderBy('updated_at', 'desc')->get();
        if ($trades) {
            return $trades;
        }
    }

    public static function getOrderUnUse() {
        $trades = Trade::where("pay_status", 1)->where("use_status", 0)->orderBy('updated_at', 'desc')->get();
        if ($trades) {
            return $trades;
        }
    }

    public static function getOrderUnUseForPerson($wx_id) {
        $trades = Trade::where("wx_id", $wx_id)->where("show_status", 1)->where("pay_status", 1)->where("use_status", 0)->orderBy('updated_at', 'desc')->get();
        if ($trades) {
            return $trades;
        }
    }

    public static function getOrderUse() {
        $trades = Trade::where("pay_status", 1)->where("use_status", 1)->orderBy('updated_at', 'desc')->get();
        if ($trades) {
            return $trades;
        }
    }

    public static function getOrderUseForPerson($wx_id) {
        $trades = Trade::where("wx_id", $wx_id)->where("show_status", 1)->where("pay_status", 1)->where("use_status", 1)->orderBy('updated_at', 'desc')->get();
        if ($trades) {
            return $trades;
        }
    }

    public static function hideOrder($id) {
        $trade = Trade::where("id", $id)->first();
        if ($trade) {
            $trade->show_status = 0;
            $trade->update();
            return $trade;
        }
    }
}