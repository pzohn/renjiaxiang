<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shop extends Model {

    public $timestamps = false;
        
    public static function shopInsert($params) {

        $shop = new self;
        $shop->name = array_get($params,"name");
        $shop->phone = array_get($params,"phone");
        $shop->address = array_get($params,"address");
        $shop->user = array_get($params,"user");
        $shop->pass = array_get($params,"pass");
        $shop->save();
        return $shop;
    }

    public static function balanceUpdate($phone,$balance) {
        $shop = Shop::where("phone", $phone)->first();
        if ($shop) {
            $shop->balance = $balance;
            $shop->update();
            return $shop;
        }
    }
    
    public static function shopSelect($phone,$pass) {
        $shop = Shop::where("phone", $phone)->first();
        if ($shop) {
            $shop = Shop::where("phone", $phone)->where("pass", $pass)->first();
            if( $shop)
            {
                return $shop;
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

    public static function getShop($phone) {
        $shop = Shop::where("phone", $phone)->first();
        if ($shop) {
            return $shop;
        }
        else
        {
            return 0;
        }
    }

    public static function getShopById($id) {
        $shop = Shop::where("id", $id)->first();
        if ($shop) {
            return $shop;
        }
        else
        {
            return 0;
        }
    }

    public static function updateFoodandCar($id,$food,$car,$date) {
        $shop = Shop::where("id", $id)->first();
        if ($shop) {
            $shop->food = $food;
            $shop->car = $car;
            $shop->share_date = $date;
            $shop->update();
            return $shop;
        }
    }
}