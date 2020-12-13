<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Usermanager;

class Usermanager extends Model {

    public $timestamps = false;
        
    public static function getMangerForWx($wx_id, $shop_id, $work_id) {
        $usermanager = Usermanager::where("shop_sub_id", $wx_id)->where("shop_id", $shop_id)->where("work_id", $work_id)->first();
        if ($usermanager)
        {
            return $usermanager;
        }
        else
        {
            return 0;
        }
    }
}