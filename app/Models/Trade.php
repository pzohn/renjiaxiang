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

    public static function payInsertFree($params,$type = 1) {
        $trade = new self;
        $trade->out_trade_no = array_get($params,"out_trade_no");
        $trade->body = array_get($params,"body");
        $trade->detail_id = array_get($params,"detail_id");
        $trade->total_fee = 0;
        $trade->wx_id = array_get($params,"wx_id");
        $trade->shop_id = array_get($params,"shop_id");
        $trade->name = array_get($params,"name");
        $trade->share_id = array_get($params,"share_id");
        $trade->use_royalty = array_get($params,"use_royalty");
        $trade->pay_status = 1;
        if ($type == 2) {
            $trade->send_status = 1;
        }
        if ($type == 3) {
            $trade->send_status = 1;
            $trade->finish_status = 1;
        }
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
        $trade->share_id = array_get($params,"share_id");
        $trade->use_royalty = array_get($params,"use_royalty");
        $trade->save();
        return $trade;
    }

    public static function payInsertForIdDown($params) {
        $trade = new self;
        $trade->out_trade_no = array_get($params,"out_trade_no");
        $trade->body = array_get($params,"body");
        $trade->detail_id = array_get($params,"detail_id");
        $trade->total_fee = array_get($params,"total_fee");
        $trade->wx_id = array_get($params,"wx_id");
        $trade->shop_id = array_get($params,"shop_id");
        $trade->name = array_get($params,"name");
        $trade->share_id = array_get($params,"share_id");
        $trade->use_royalty = array_get($params,"use_royalty");
        $trade->use_royalty = array_get($params,"use_royalty");
        $trade->pay_status = 1;
        $trade->send_status = 1;
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


    public static function payUpdate($out_trade_no,$type = 1) {
        $trade = Trade::where("out_trade_no", $out_trade_no)->first();
        if ($trade) {
            if($trade->pay_status == 1)
            {
                return 0;
            }
            $trade->pay_status = 1;
            if ($trade->wx_id == 0){
                $trade->edit_flag = 1;
            }
            if ($type == 2) {
                $trade->send_status = 1;
            }
            if ($type == 3) {
                $trade->send_status = 1;
                $trade->finish_status = 1;
            }
            $trade->update();
            return 1;
        }
    }

    public static function postRefund($id,$refund_status) {
        $trade = Trade::where("id", $id)->first();
        if ($trade) {
            $trade->post_refund_status = $refund_status;
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
            ->whereBetween("updated_at", [date("Y-m-d 00:00:00"), date("Y-m-d 23:59:59")])
            ->get();
        if ($trade) {
            return count($trade);
        }
    }

    public static function getShopMonth($id) {
        $begin = date("Y-m-d H:i:s", mktime(0,0,0,date('m'),1,date('Y')));
        $ended = date("Y-m-d H:i:s", mktime(23,59,59,date('m'),date('t'),date('Y')));
        $trade = Trade::where("shop_id", $id)
            ->whereBetween("updated_at", [$begin, $ended])
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

    public static function getShareUseForPerson($wx_id) {
        $trades = Trade::where("use_royalty",'!=',0)->where("wx_id", $wx_id)->where("finish_refund_status", 0)->where("pay_status", 1)->orderBy('updated_at', 'desc')->get();
        if ($trades) {
            return $trades;
        }
    }

    public static function getShareForPerson($share_id) {
        $trades = Trade::where("share_id", $share_id)->where("wx_id", '!=', $wx_id)->where("finish_refund_status", 0)->where("pay_status", 1)->orderBy('updated_at', 'desc')->get();
        if ($trades) {
            return $trades;
        }
    }

    public static function getShareForPersonEx1($share_id) {
        $trades = Trade::where("share_id", $share_id)->where("shop_id", 5)->where("send_status", 0)
        ->where("finish_refund_status", 0)->where("pay_status", 1)->orderBy('created_at', 'desc')->get();
        if ($trades) {
            return $trades;
        }
    }

    public static function getShareForPersonEx2($share_id) {
        $trades = Trade::where("share_id", $share_id)->where("shop_id", 5)->where("send_status", 1)
        ->where("finish_status", 1)->where("finish_refund_status", 0)->where("pay_status", 1)->orderBy('created_at', 'desc')->get();
        if ($trades) {
            return $trades;
        }
    }

    public static function getShareForPersonEx3($share_id,$date_begin,$date_after,$shop_id) {
        $trades = Trade::where("share_id", $share_id)->where("shop_id", $shop_id)->where("send_status", 0)
        ->whereBetween("created_at", [$date_begin, $date_after])
        ->where("finish_refund_status", 0)->where("pay_status", 1)->orderBy('created_at', 'desc')->get();
        if ($trades) {
            return $trades;
        }
    }

    public static function getShareForPersonEx31($share_id,$date_begin,$date_after,$shopping_id,$shop_id) {
        $trades = Trade::where("share_id", $share_id)->where("shop_id", $shop_id)->where("send_status", 0)
        ->whereBetween("created_at", [$date_begin, $date_after])->where("detail_id", $shopping_id)
        ->where("finish_refund_status", 0)->where("pay_status", 1)->orderBy('created_at', 'desc')->get();
        if ($trades) {
            return $trades;
        }
    }

    public static function getShareForPersonEx4($share_id,$date_begin,$date_after,$shop_id) {
        $trades = Trade::where("share_id", $share_id)->where("shop_id", $shop_id)->where("send_status", 1)
        ->whereBetween("created_at", [$date_begin, $date_after])
        ->where("finish_status", 1)->where("finish_refund_status", 0)->where("pay_status", 1)->orderBy('created_at', 'desc')->get();
        if ($trades) {
            return $trades;
        }
    }

    public static function getShareForPersonEx41($share_id,$date_begin,$date_after,$shopping_id,$shop_id) {
        $trades = Trade::where("share_id", $share_id)->where("shop_id", $shop_id)->where("send_status", 1)
        ->whereBetween("created_at", [$date_begin, $date_after])->where("detail_id", $shopping_id)
        ->where("finish_status", 1)->where("finish_refund_status", 0)->where("pay_status", 1)->orderBy('created_at', 'desc')->get();
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

    public static function getOrderAllForPerson($wx_id) {
        $trades = Trade::where("wx_id", $wx_id)->where("show_status", 1)->where("post_refund_status", 0)->where("finish_refund_status", 0)->orderBy('updated_at', 'desc')->get();
        if ($trades) {
            return $trades;
        }
    }

    public static function getOrderAllForShop($shop_id) {
        $trades = Trade::where("shop_id", $shop_id)->where("show_status", 1)->where("pay_status", 1)->where("post_refund_status", 0)->where("finish_refund_status", 0)->orderBy('updated_at', 'desc')->limit(100)->get();
        if ($trades) {
            return $trades;
        }
    }

    public static function getOrderAllForShopEx($shop_id) {
        $trades = Trade::where("shop_id", $shop_id)->where("show_status", 1)->where("pay_status", 1)->where("finish_status",'<>', 1)->where("post_refund_status", 0)->where("finish_refund_status", 0)->orderBy('updated_at', 'desc')->get();
        if ($trades) {
            return $trades;
        }
    }

    public static function getOrderAllForShopEx1($shop_id) {
        $trades = Trade::where("shop_id", $shop_id)->where("show_status", 1)->where("pay_status", 1)->where("finish_status", 1)->where("post_refund_status", 0)->where("finish_refund_status", 0)->orderBy('updated_at', 'desc')->limit(50)->get();
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

    public static function getOrderRefundForPerson($wx_id) {
        $trades = Trade::where("wx_id", $wx_id)->where("show_status", 1)->where("pay_status", 1)->where("post_refund_status", 1)->orderBy('updated_at', 'desc')->get();
        if ($trades) {
            return $trades;
        }
    }

    public static function getOrderUnsendForPerson($wx_id) {
        $trades = Trade::where("wx_id", $wx_id)->where("show_status", 1)->where("post_refund_status", 0)->where("finish_refund_status", 0)->where("pay_status", 1)->where("send_status", 0)->orderBy('updated_at', 'desc')->get();
        if ($trades) {
            return $trades;
        }
    }

    public static function getOrderUnreceiveForPerson($wx_id) {
        $trades = Trade::where("wx_id", $wx_id)->where("show_status", 1)->where("post_refund_status", 0)->where("finish_refund_status", 0)->where("pay_status", 1)->where("send_status", 1)->where("finish_status", 0)->orderBy('updated_at', 'desc')->get();
        if ($trades) {
            return $trades;
        }
    }

    public static function getOrderFinishForPerson($wx_id) {
        $trades = Trade::where("wx_id", $wx_id)->where("show_status", 1)->where("finish_refund_status", 0)->where("pay_status", 1)->where("send_status", 1)->where("finish_status", 1)->orderBy('updated_at', 'desc')->get();
        if ($trades) {
            return $trades;
        }
    }

    public static function getOrderFinishForPersonForZhaobo($wx_id) {
        $trades = Trade::where("wx_id", $wx_id)->where("show_status", 1)->where("finish_refund_status", 0)->where("pay_status", 1)->where("finish_status", 1)->orderBy('updated_at', 'desc')->get();
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

    public static function hideOrder($id,$wx_id) {
        $trade = Trade::where("id", $id)->first();
        if ($trade) {
            $trade->show_status = 0;
            $trade->oper = strval($wx_id);
            $trade->update();
            return $trade;
        }
    }

    public static function finishOrder($id,$wx_id) {
        $trade = Trade::where("id", $id)->first();
        if ($trade) {
            $trade->finish_status = 1;
            $trade->oper = strval($wx_id);
            $trade->update();
            return $trade;
        }
    }

    public static function getTradesInfoByShopId($params) {
        $tradeid = array_get($params,"tradeid");
        $shop_id = array_get($params,"shop_id");
        $name = array_get($params,"name");
        $status = array_get($params,"status");

        if ($tradeid){
            $trades = Trade::where("shop_id", $shop_id)->where("show_status", 1)->where("pay_status", 1)
            ->where("out_trade_no",'like','%'.$tradeid.'%')->orderBy('updated_at', 'desc')->get();
            if ($trades) {
                return $trades;
            }
        }

        if ($name){
            $trades = Trade::where("shop_id", $shop_id)->where("show_status", 1)->where("pay_status", 1)
            ->where("name",'like','%'.$name.'%')->orderBy('updated_at', 'desc')->get();
            if ($trades) {
                return $trades;
            }
        }

        if ($status){
            if ($status = 1){
                $trades = Trade::where("shop_id", $shop_id)->where("show_status", 1)->where("pay_status", 1)
                ->where("post_refund_status", 0)->where("finish_refund_status", 0)
                ->where("send_status", 0)->where("finish_status", 0)->orderBy('updated_at', 'desc')->get();
                if ($trades) {
                    return $trades;
                }
            }else if ($status = 2){
                $trades = Trade::where("shop_id", $shop_id)->where("show_status", 1)->where("pay_status", 1)
                ->where("post_refund_status", 0)->where("finish_refund_status", 0)
                ->where("send_status", 1)->where("finish_status", 0)->orderBy('updated_at', 'desc')->get();
                if ($trades) {
                    return $trades;
                }
            }else if ($status = 3){
                $trades = Trade::where("shop_id", $shop_id)->where("show_status", 1)->where("pay_status", 1)
                ->where("post_refund_status", 0)->where("finish_refund_status", 0)
                ->where("send_status", 1)->where("finish_status", 1)->orderBy('updated_at', 'desc')->get();
                if ($trades) {
                    return $trades;
                }
            }
            else if ($status = 4){
                $trades = Trade::where("shop_id", $shop_id)->where("show_status", 1)->where("pay_status", 1)
                ->where("post_refund_status", 1)->where("finish_refund_status", 0)
                ->orderBy('updated_at', 'desc')->get();
                if ($trades) {
                    return $trades;
                }
            }else if ($status = 5){
                $trades = Trade::where("shop_id", $shop_id)->where("show_status", 1)->where("pay_status", 1)
                ->where("post_refund_status", 1)->where("finish_refund_status", 1)
                ->orderBy('updated_at', 'desc')->get();
                if ($trades) {
                    return $trades;
                }
            }
        }
        $trades = Trade::where("shop_id", $shop_id)->where("show_status", 1)->where("pay_status", 1)->orderBy('updated_at', 'desc')->get();
        return $trades;
    }

    public static function updateStatus($id,$status) {
        $trade = Trade::where("id", $id)->first();
        if ($trade) {
            if ($status == 1){
                $trade->send_status = 0;
                $trade->finish_status = 0;
                $trade->post_refund_status = 0;
                $trade->finish_refund_status = 0;
            }
            else if($status == 2)
            {
                $trade->send_status = 1;
                $trade->finish_status = 0;
                $trade->post_refund_status = 0;
                $trade->finish_refund_status = 0;
            }
            else if($status == 3)
            {
                $trade->send_status = 1;
                $trade->finish_status = 1;
                $trade->post_refund_status = 0;
                $trade->finish_refund_status = 0;
            }
            else if($status == 4)
            {
                $trade->post_refund_status = 1;
                $trade->finish_refund_status = 0;
            }
            else if($status == 5)
            {
                $trade->post_refund_status = 1;
                $trade->finish_refund_status = 1;
            }
            $trade->update();
        }
        return $trade;
    }
}