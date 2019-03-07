<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class  Cardimg extends Model {
    
    public $timestamps = false;

    public static function getCardimgs() {
        $cardimgs = Cardimg::get();
        if ($cardimgs)
        {
            return $cardimgs;
        }
    }
}