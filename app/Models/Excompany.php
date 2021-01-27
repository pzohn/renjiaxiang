<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class  Excompany extends Model {

    public static function GetExcompanyById($id) {
        $excompany = Excompany::where("id", $id)->first();
        if ($excompany) {
            return $excompany;
        }
    }
}