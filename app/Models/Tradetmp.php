<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Information;

class  Tradetmp extends Model {
        
    public static function payInsert($params) {

        $tradetmp = new self;
        $tradetmp->out_trade_no = array_get($params,"out_trade_no");
        $tradetmp->body = array_get($params,"body");
        $tradetmp->detail_id = array_get($params,"detail_id");
        $tradetmp->total_fee = array_get($params,"total_fee");
        $tradetmp->phone = array_get($params,"phone");
        $tradetmp->shop_id = array_get($params,"shop_id");
        $tradetmp->name = array_get($params,"name");
        if (($tradetmp->detail_id >= 51) && ($tradetmp->detail_id <= 59)){
            $info = Information::getInformationEx($tradetmp->phone);
            if ($info){
                $tradetmp->name = $info->SCHOOL . $info->CLASS . $tradetmp->name;
            }
        }
        $tradetmp->save();
        return $tradetmp;
    }

    public static function payInsertGroup($params) {

        $tradetmp = new self;
        $tradetmp->out_trade_no = array_get($params,"out_trade_no");
        $tradetmp->body = array_get($params,"body");
        $tradetmp->detail_id = array_get($params,"detail_id");
        $tradetmp->total_fee = array_get($params,"total_fee");
        $tradetmp->phone = array_get($params,"phone");
        $tradetmp->group_id = array_get($params,"shop_id");
        $tradetmp->name = array_get($params,"name");
        $tradetmp->save();
        return $tradetmp;
    }


    public static function payUpdate($out_trade_no,$type = 1) {
        $tradetmp = Tradetmp::where("out_trade_no", $out_trade_no)->first();
        if ($tradetmp) {
            if($tradetmp->pay_status == 1)
            {
                return 0;
            }
            $tradetmp->pay_status = 1;
            $tradetmp->edit_flag = 1;
            $tradetmp->update();
            return 1;
        }
    }

    public static function paySelect($out_trade_no) {
        $tradetmp = Tradetmp::where("out_trade_no", $out_trade_no)->first();
        if ($tradetmp) {
            return $tradetmp;
        }
    }

    public static function getShopTotal($id) {
        $tradetmp = Tradetmp::where("shop_id", $id)->get();
        if ($tradetmp) {
            return count($tradetmp);
        }
    }

    public static function paySelectById($id) {
        $tradetmp = Tradetmp::where("id", $id)->first();
        if ($tradetmp) {
            return $tradetmp;
        }
    }

    public static function getShopDay($id) {
        $tradetmp = Tradetmp::where("shop_id", $id)
            ->whereBetween("updated_at", [date("Y-m-d 00:00:00"), date("Y-m-d 23:59:59")])
            ->get();
        if ($tradetmp) {
            return count($tradetmp);
        }
    }

    public static function getShopMonth($id) {
        $begin = date("Y-m-d H:i:s", mktime(0,0,0,date('m'),1,date('Y')));
        $ended = date("Y-m-d H:i:s", mktime(23,59,59,date('m'),date('t'),date('Y')));
        $tradetmp = Tradetmp::where("shop_id", $id)
            ->whereBetween("updated_at", [$begin, $ended])
            ->get();
        if ($tradetmp) {
            return count($tradetmp);
        }
    }
}