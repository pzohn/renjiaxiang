<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class  Image extends Model {
    
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
}