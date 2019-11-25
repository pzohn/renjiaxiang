<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class  Indexset extends Model {
        
    public $timestamps = false;

    public static function GetIndexByType($type) {
        $indexsets = Indexset::where("type", $type)->get()->limit(6);
        return $indexsets;
    }

}