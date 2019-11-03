<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class  Image extends Model {
    
    public $timestamps = false;

    public static function GetImage($id) {
        $image = Image::where("id", $id)->first();
        if ($image) {
            return $image;
        }
    }

    public static function GetImageUrl($id) {
        $image = Image::where("id", $id)->first();
        if ($image) {
            return $image->file . "/" . $image->url;
        }
    }

    public static function urlInsert($params) {
        $image = new self;
        $image->parent_id = array_get($params,"parent_id");
        $image->url = array_get($params,"url");
        $image->file = array_get($params,"file");
        $image->type = array_get($params,"type");
        $image->save();
        return $image;
    }

    public static function urlUpdate($params) {
        $image = Image::where("id", array_get($params,"id"))->first();
        if ($image) {
            $image->url = array_get($params,"url");
            $image->update();
            return $image;
        }
    }
}