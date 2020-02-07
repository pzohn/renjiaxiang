<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Childexpress extends Model {
        
    public $timestamps = false;

    public static function getchildexpresses($id) {
        $childexpresses = Childexpress::where("parent_id", $id)->get();
        if ($childexpresses) {
            return $childexpresses;
        }
    }

    public static function childexpressInsert($params) {
        $childexpress = new self;
        $childexpress->status_desc = array_get($params,"status_desc");
        $childexpress->time_desc = array_get($params,"time_desc");
        $childexpress->parent_id = array_get($params,"parent_id");
        $childexpress->save();
        return $childexpress;
    }
}