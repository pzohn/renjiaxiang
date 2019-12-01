<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class  Shoppingtype extends Model {
        
    public $timestamps = false;

    public static function GetTypeById($id) {
        $shoppingtype = Shoppingtype::where("id", $id)->first();
        if ($shoppingtype) {
            return $shoppingtype;
        }
    }
}