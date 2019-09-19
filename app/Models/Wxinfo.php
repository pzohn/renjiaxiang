<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class  Wxinfo extends Model {
        
    public $timestamps = false;

    public static function GetWxinfoById($id) {
        $wxinfo = Wxinfo::where("id", $id)->first();
        if ($wxinfo) {
            return $wxinfo;
        }
    }
}