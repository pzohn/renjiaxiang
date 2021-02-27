<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class  Zhang extends Model {

    public $timestamps = false;
    
    public static function getZhang($id) {
        $zhang = Zhang::where("parent_id", $id)->first();
        return $zhang;
    }
}