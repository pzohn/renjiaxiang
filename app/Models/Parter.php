<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
        if ($parter) {
            $id = $parter->id;
            
        }
        else
        {
            return 0;
        }

    }
}