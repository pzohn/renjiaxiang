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
        if ($netflag == 1){
            $cards = Card::where("NETFLAG", 1)->where("ONFLAG", 1)->get();
            return $cards; 
        }else if ($netflag == 2){
            $cards = Card::where("SHAREFLAG", 1)->where("ONFLAG", 1)->get();
            return $cards;
        }else if ($netflag == 3){
            $cards = Card::where("TEAMFLAG", 1)->where("ONFLAG", 1)->get();
            return $cards;
        }
    }
}