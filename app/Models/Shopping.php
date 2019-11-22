<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class  Shopping extends Model {
        
    public static function shoppingsSelect($username) {
        $shoppings = Shopping::where("state", 1)->get();
        if ($shoppings) {
            return $shoppings;
        }
    }

    public static function shoppingSelect($id) {
        $shopping = Shopping::where("id", $id)->where("state", 1)->first();
        if ($shopping) {
            return $shopping;
        }else{
            return 0;
        }
    }

    public static function shoppingInsert($params) {
        $shopping = new self;
        $shopping->name = array_get($params,"name");
        $shopping->flag = array_get($params,"flag");
        $shopping->price = array_get($params,"price");
        $shopping->type = array_get($params,"type");
        $shopping->oper = array_get($params,"oper");
        $shopping->save();
        return $shopping;
    }

    public static function shoppingRepeat($params) {
        $shopping = Shopping::where("name", array_get($params,"name"))->where("flag", array_get($params,"flag"))->where("price", array_get($params,"price"))->where("type", array_get($params,"type"))->where("oper", array_get($params,"oper"))->first();
        if ($shopping) {
            return $shopping->id;
        }else{
            return 0;
        }
    }

    public static function shoppingGetByType($type) {
        $shopping = Shopping::where("type", $type)->get();
        return $shopping;
    }
}