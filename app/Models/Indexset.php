<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class  Indexset extends Model {
        
    public $timestamps = false;

    public static function GetIndexByType($type,$shop_id) {
        $indexsets = Indexset::where("type", $type)->where("shop_id", $shop_id)->get();
        return $indexsets;
    }

    public static function GetIndexId($type,$shop_id,$object_id) {
        $indexset = Indexset::where("type", $type)->where("shop_id", $shop_id)->where("object_id", $object_id)->first();
        if ($indexset) {
            return $indexset->id;
        }else {
            return 0;
        }
    }

    public static function DelIndex($id) {
        Indexset::where("id", $id)->delete();
    }

    public static function InsertIndex($type,$shop_id,$object_id) {
        $indexset = new self;
        $indexset->type = $type;
        $indexset->shop_id = $shop_id;
        $indexset->object_id = $object_id;
        $indexset->save();
        return $indexset;
    }

}