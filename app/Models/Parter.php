<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Trade;

class Parter extends Model {

    public $timestamps = false;
        
    public static function parterInsert($params) {

        $parter = new self;
        $parter->name = array_get($params,"name");
        $parter->phone = array_get($params,"phone");
        $parter->address = array_get($params,"address");
        $parter->user = array_get($params,"user");
        $parter->pass = array_get($params,"pass");
        $shop->save();
        return $shop;
    }

    public static function numUpdate($params) {
        $parter = Parter::where("id", array_get($params,"id"))->first();
        if ($parter) {
            $parter->day_num = array_get($params,"day_num");
            $parter->month_num = array_get($params,"month_num");
            $parter->total_num = array_get($params,"total_num");
            $parter->update();
            return $parter;
        }
    }

    public static function getParter($phone) {
        $parter = Parter::where("phone", $phone)->first();
        \Log::debug("----- model get parter ------", [$phone, $parter]);
        if ($parter) {
            $id = $parter->id;
            $params = [
                'id' => $id,
                'day_num' => Trade::getShopDay($id),
                'month_num' => Trade::getShopMonth($id),
                'total_num' => Trade::getShopTotal($id),
                'phone' => $phone
             ];
             $parterUpdate = Parter::numUpdate($params);
             return $parterUpdate;
        } else {
            return 0;
        }
    }

    public static function getParterForWx($wx_id) {
        $parter = Parter::where("wx_id", $wx_id)->where("is_delete", 0)->where("enable", 1)->first();
        return $parter;
    }

    public static function getParterForWxNoSig($wx_id) {
        $parter = Parter::where("wx_id", $wx_id)->where("is_delete", 0)->first();
        return $parter;
    }

    public static function getParterForId($id) {
        $parter = Parter::where("id", $id)->first();
        return $parter;
    }
    public static function getParterForIdNoDel($id) {
        $parter = Parter::where("id", $id)->where("is_delete", 0)->where("enable", 1)->first();
        return $parter;
    }

    public static function getParterForWxEx() {
        $parters = Parter::where("type_id", 2)->where("is_delete", 0)->where("enable", 1)->get();
        return $parters;
    }

    public static function getPartersForParent($parent_id) {
        $parters = Parter::where("share_parent_id", $parent_id)->where("is_delete", 0)->where("enable", 1)->get();
        return $parters;
    }

    public static function getParentParters($id) {
        $parters = Parter::where("share_parent_id", $parent_id)->where("is_delete", 0)->where("enable", 1)->get();
        return $parters;
    }

    public static function getAreasFirst() {
        $parters = Parter::where("share_parent_id", 1)->where("type_id", 2)->where("is_delete", 0)->where("enable", 1)->get();
        return $parters;
    }

    public static function getAreasFirstEx($id) {
        $parters = Parter::where("wx_id", $id)->where("is_delete", 0)->where("enable", 1)>get();
        return $parters;
    }


    public static function getParterInfo($phone,$pass) {
        $parter = Parter::where("phone", $phone)->first();
        if ($parter) {
            $parter = Parter::where("phone", $phone)->where("pass", $pass)->first();
            if( $parter)
            {
                $parterUpdate = Parter::getParter($phone);
                return $parterUpdate;
            }
            else
            {
                return 1;
            }
        }
        else
        {
            return 0;
        }
    }
}