<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class  Shopping extends Model {
        
    public static function shoppingsSelect($shop_id) {
        $shoppings = Shopping::where("state", 1)->where("shop_id", $shop_id)->get();
        if ($shoppings) {
            return $shoppings;
        }
    }

    public static function downsSelect($shop_id) {
        $shoppings = Shopping::where("state", 0)->where("shop_id", $shop_id)->get();
        if ($shoppings) {
            return $shoppings;
        }
    }

    public static function shoppingOff($id) {
        $shopping = Shopping::where("id", $id)->first();
        if ($shopping) {
            $shopping->state = 0;
            $shopping->update();
            return $shopping;
        }
    }

    public static function shoppingUp($id) {
        $shopping = Shopping::where("id", $id)->first();
        if ($shopping) {
            $shopping->state = 1;
            $shopping->update();
            return $shopping;
        }
    }

    public static function updateStock($id,$stock) {
        $shopping = Shopping::where("id", $id)->first();
        if ($shopping) {
            if ($shopping->stock)
            $shopping->stock -= $stock;
            $shopping->update();
            return $shopping->stock;
        }
        return 0;
    }

    public static function updateStockEx($id,$stock) {
        $shopping = Shopping::where("id", $id)->first();
        if ($shopping) {
            $shopping->stock = $stock;
            $shopping->update();
            return $shopping->stock;
        }
    }

    public static function shoppingsSelectByName($name,$shop_id) {
        $shoppings = Shopping::where('name','like', '%'.$name.'%')->where("state", 1)->where("shop_id", $shop_id)->get();
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
        $shopping->price = array_get($params,"price");
        $shopping->type = array_get($params,"type");
        $shopping->oper = array_get($params,"oper");
        $shopping->shop_id = array_get($params,"shop_id");
        $shopping->royalty = array_get($params,"royalty");
        $shopping->integral = array_get($params,"integral");
        $shopping->stock = array_get($params,"stock");
        $shopping->save();
        return $shopping;
    }

    public static function shoppingUpdatePart($params) {
        $shopping = Shopping::where("id", array_get($params,"id"))->first();
        if ($shopping) {
            $shopping->name = array_get($params,"name");
            $shopping->price = array_get($params,"price");
            $shopping->royalty = array_get($params,"royalty");
            $shopping->integral = array_get($params,"integral");
            $shopping->type = array_get($params,"type");
            $shopping->stock = array_get($params,"stock");
            $shopping->update();
            return $shopping;
        }
    }

    public static function updateShoppingType($id,$type) {
        $shopping = Shopping::where("id", $id)->first();
        if ($shopping) {
            $shopping->type = $type;
            $shopping->update();
            return $shopping;
        }
    }

    public static function shoppingRepeat($params) {
        $shopping = Shopping::where("name", array_get($params,"name"))->where("price", array_get($params,"price"))->where("type", array_get($params,"type"))->where("oper", array_get($params,"oper"))->where("shop_id", array_get($params,"shop_id"))->where("royalty", array_get($params,"royalty"))->where("integral", array_get($params,"integral"))->where("stock", array_get($params,"stock"))->first();
        if ($shopping) {
            return $shopping->id;
        }else{
            return 0;
        }
    }

    public static function shoppingGetByType($type,$shop_id) {
        $shopping = Shopping::where("type", $type)->where("state", 1)->where("shop_id", $shop_id)->get();
        return $shopping;
    }
}