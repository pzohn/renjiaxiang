<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Submitlocation extends Model {
        
    public static function submitlocationInsert($params) {

        $submitlocation = new self;
        $submitlocation->deviceid = array_get($params,"deviceid");
        $submitlocation->longitude = array_get($params,"longitude");
        $submitlocation->latitude = array_get($params,"latitude");
        $submitlocation->lbsinfo = array_get($params,"lbsinfo");
        $submitlocation->address = array_get($params,"address");
        $submitlocation->addresstype = array_get($params,"addresstype");
        $submitlocation->save();
        return $shop;
    }
