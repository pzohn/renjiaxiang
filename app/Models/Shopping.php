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
        $shopping = Cert::where("id", $id)->where("state", 1)->first();
        if ($shopping) {
            return $shopping;
        }
    }
}