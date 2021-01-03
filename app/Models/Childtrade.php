<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class  Childtrade extends Model {
    
    public $timestamps = false;

    public static function payInsert($params) {

        $childtrade = new self;
        $childtrade->shopping_id = array_get($params,"shopping_id");
        $childtrade->num = array_get($params,"num");
        $childtrade->trade_id = array_get($params,"trade_id");
        $childtrade->use_num = array_get($params,"num");
        $childtrade->retail_price = array_get($params,"retail_price");
        $childtrade->save();
        return $childtrade;
    }

    public static function useUpdate($id) {
        $childtrade = Childtrade::where("id", $id)->first();
        if ($childtrade) {
            $childtrade->use_status = 1;
            $childtrade->update();
            return $childtrade;
        }
    }

    public static function sendUpdate($id) {
        $childtrade = Childtrade::where("id", $id)->first();
        if ($childtrade) {
            $childtrade->send_status = 1;
            $childtrade->update();
            return $childtrade;
        }
    }

    public static function paySelectById($id) {
        $childtrades = Childtrade::where("trade_id", $id)->get();
        if ($childtrades) {
            return $childtrades;
        }
    }
}