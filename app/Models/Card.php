<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class  Card extends Model {

    protected $table = "card";
        
    public $timestamps = false;

    public static function getCard($cardid) {
        $card = Card::where("ID", $cardid)->first();
        if ($card)
        {
            return $card;
        }
    }

    public static function getCards($netflag) {
        $cards = Card::where("NETFLAG", $netflag)->get();
        return $cards;
    }
}