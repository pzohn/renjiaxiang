<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Express extends Model {
        
    public $timestamps = false;

    public static function getExpressNum($id) {
        $express = Express::where("trade_id", $id)->first();
        if ($express) {
            return $express->number;
        }else{
            return '';
        }
    }
    
    public static function getExpressByNum($number) {
        $express = Express::where("number", $number)->first();
        if ($express) {
            return $express;
        }else{
            return 0;
        }
    }

    public static function getExpressById($id) {
        $express = Express::where("id", $id)->first();
        if ($express) {
            return $express;
        }else{
            return 0;
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
        $express = Express::where("id", array_get($params,"id"))->first();
        if ($express) {
            $express->deliverystatus = array_get($params,"deliverystatus");
            $express->issign = array_get($params,"issign");
            $express->expName = array_get($params,"expName");
            $express->expSite = array_get($params,"expSite");
            $express->expPhone = array_get($params,"expPhone");
            $express->updateTime = array_get($params,"updateTime");
            $express->takeTime = array_get($params,"takeTime");
            $express->logo = array_get($params,"logo");
            $express->update();
            return $express;
        }
    }

}