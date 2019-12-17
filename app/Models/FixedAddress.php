<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class  FixedAddress extends Model {
        
    public $timestamps = false;

    public static function GetAddress($id) {
        $fixedAddress = FixedAddress::where("id", $id)->first();
        if ($fixedAddress) {
            return $fixedAddress;
        }
    }

    public static function GetAddresses($shop_id) {
        $fixedAddresses = FixedAddress::where("shop_id", $shop_id)->get();
        if ($fixedAddresses) {
            return $fixedAddresses;
        }
    }
}