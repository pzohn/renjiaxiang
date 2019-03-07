<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class  Postcard extends Model {

    public static function GetPostcard($phone) {
        $postcard = Postcard::where("phone", $phone)->latest()->first();
        if ($postcard) {
            return $postcard;
        }
    }

    public static function InsertPostcard($params) {
        $postcard = new self;
        $postcard->phone = array_get($params,"phone");
        $postcard->img_url = array_get($params,"img_url");
        $postcard->audio_url = array_get($params,"audio_url");
        $postcard->save();
        return $postcard;
    }
}