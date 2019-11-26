<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class  Cert extends Model {
    
    public $timestamps = false;
    
    public static function certInsert($params) {

        $cert = new self;
        $cert->wx_id = array_get($params,"wx_id");
        $cert->shopping_id = array_get($params,"shopping_id");
        $cert->count = array_get($params,"count");
        $cert->save();
        return $cert;
    }

    public static function certsSelect($username) {
        $certs = Cert::where("wx_id", $wx_id)->get();
        if ($certs) {
            return $certs;
        }
    }

    public static function certSelect($shopping_id) {
        $cert = Cert::where("shopping_id", $shopping_id)->first();
        if ($cert) {
            return $cert;
        }
        return 0;
    }

    public static function certdelete($id) {
        Cert::where("id", $id)->delete();
    }

    public static function certupdate($id,$count) {
        $cert = Cert::where("id", $id)->first();
        if ($cert) {
            $cert->count = $count;
            $cert->update();
            return $cert;
        }
    }
}