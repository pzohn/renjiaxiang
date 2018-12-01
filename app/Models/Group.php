<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model {

    //public $timestamps = false;
        
    public static function groupInsert($params) {

        $group = new self;
        $group->name = array_get($params,"name");
        $group->phone = array_get($params,"phone");
        $group->parent_num = array_get($params,"parent_num");
        $group->food_num = array_get($params,"food_num");
        $group->car_num = array_get($params,"car_num");
        $group->group_id = array_get($params,"group_id");
        $group->save();
        return $group;
    }

    public static function getGroup($phone) {
        $group = Group::where("phone", $phone)->first();
        if ($group) {
            return $group;
        }
        else
        {
            return 0;
        }
    }
}