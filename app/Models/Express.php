<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Express extends Model {
        
    public $timestamps = false;

    public static function getExpress($id) {
        $express = express::where("trade_id", $id)->first();
        if ($express) {
            return $express;
        }
    }

    public static function expressInsert($params) {
        $express = new self;
        $express->trade_id = array_get($params,"trade_id");
        $express->number = array_get($params,"number");
        $express->save();
        return $express;
    }

    public static function expressUpdate($params) {
        $express = Express::where("number", array_get($params,"number"))->first();
        if ($express) {
            $express->deliverystatus = array_get($params,"deliverystatus");
            $express->issign = array_get($params,"issign");
            $express->expName = array_get($params,"expName");
            $express->expSite = array_get($params,"expSite");
            $express->expPhone = array_get($params,"expPhone");
            $express->courier = array_get($params,"courier");
            $express->courierPhone = array_get($params,"courierPhone");
            $express->updateTime = array_get($params,"updateTime");
            $express->takeTime = array_get($params,"takeTime");
            $express->logo = array_get($params,"logo");
            $express->update();
            return $express;
        }
    }

}