<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Card;

class  Information extends Model {

    protected $table = "information";
        
    public $timestamps = false;
    /**
     *
     * @param $params 
     *        user_info:
     *        name
     *        id_number
     *        mobile
     */
    public static function getAndSet($params) {

        $type = array_get($params,"type");
        if ($type == "select")
        {
            $information = Information::where("CODE", array_get($params,"PHONE"))->first();
            if (!$information)
            {
                return 0;
            }
            else
            {
                $information = Information::where("CODE", array_get($params,"PHONE"))->where("PASSWORD", array_get($params,"PASSWORD"))->first();
                if(!$information)
                {
                    return 1;
                }
                $cardid = $information->CARDID;
                $carddesc = "";
                if($cardid != 0)
                {
                    $carddesc = Card::getCard($cardid)->DESC;
                }
                unset($information['cardid']);
                $result = [
                    "ID"=> $information->ID,
                    "NAME"=> $information->NAME,
                    "AGE"=> $information->AGE,
                    "SEX"=> $information->SEX,
                    "PHONE"=> $information->PHONE,
                    "ADDRESS"=> $information->ADDRESS,
                    "FATHER"=> $information->FATHER,
                    "MOTHER"=> $information->MOTHER,
                    "CARDDESC"=> $carddesc,
                    "CARDNUM"=> $information->CARDNUM,
                    "AUTHORITY" => $information->AUTHORITY
                ];
                return $result;
            }
        }
        else if($type == "insert")
        {
            $information = Information::where("CODE", array_get($params,"PHONE"))->first();
            if (!$information) {
                $information = new Information();
                $information->NAME = array_get($params,"NAME");
                $information->AGE = array_get($params,"AGE");
                $information->SEX = array_get($params,"SEX");
                $information->PHONE = array_get($params,"PHONE");
                $information->CODE = array_get($params,"PHONE");
                $information->ADDRESS = array_get($params,"ADDRESS");
                $information->FATHER = array_get($params,"FATHER");
                $information->MOTHER = array_get($params,"MOTHER");
                $information->CARDID = array_get($params,"CARDID");
                $information->CARDNUM = array_get($params,"CARDNUM");
                $information->PASSWORD = array_get($params,"PASSWORD");
                $information->EDITFLAG = 1;
                $information->save();
                return $information;
            }
            return 0;
            
        }
        else if($type == "update")
        {
            $information = Information::where("CODE", array_get($params,"PHONE"))->first();
            if ($information) {
                $information->NAME = array_get($params,"NAME");
                $information->AGE = array_get($params,"AGE");
                $information->ADDRESS = array_get($params,"ADDRESS");
                $information->FATHER = array_get($params,"FATHER");
                $information->MOTHER = array_get($params,"MOTHER");
                $information->EDITFLAG = 1;
                
                \DB::update('update information set NAME = ?, AGE = ?,ADDRESS = ?,FATHER = ?,
                MOTHER = ?, EDITFLAG = ? where CODE = ?', [$information->NAME,$information->AGE,$information->ADDRESS,
                $information->FATHER,$information->MOTHER,$information->EDITFLAG,$information->PHONE]);
                return $information;
            }
            return 0;   
        }  
    }

    public static function updateCard($params) {
        $information = Information::where("CODE", array_get($params,"PHONE"))->first();
        if ($information) {
            $information->CARDID = array_get($params,"CARDID");
            $information->CARDNUM = array_get($params,"CARDNUM");
            $information->EDITFLAG = 1;
            \DB::update('update information set CARDID = ?, CARDNUM = ?, EDITFLAG = ? where CODE = ?', [$information->CARDID,
            $information->CARDNUM,$information->EDITFLAG,$information->PHONE]);
            return $information;
        }
    }

    public static function getInformation($params) {
        $information = Information::where("CODE", array_get($params,"PHONE"))->first();
        $cardid = $information->CARDID;
        $carddesc = "";
        if($cardid != 0)
        {
            $carddesc = Card::getCard($cardid)->DESC;
        }
        $result = [
            "ID"=> $information->ID,
            "NAME"=> $information->NAME,
            "AGE"=> $information->AGE,
            "SEX"=> $information->SEX,
            "PHONE"=> $information->PHONE,
            "ADDRESS"=> $information->ADDRESS,
            "FATHER"=> $information->FATHER,
            "MOTHER"=> $information->MOTHER,
            "CARDDESC"=> $carddesc,
            "CARDNUM"=> $information->CARDNUM,
            "AUTHORITY" => $information->AUTHORITY
        ];
        return $result;
    }


    public static function resetPass($params) {
        $information = Information::where("CODE", array_get($params,"PHONE"))->where("NAME", array_get($params,"NAME"))->first();
        if($information)
        {
            $information->PASSWORD = '000000';
            $information->EDITFLAG = 1;
            \DB::update('update information set PASSWORD = ? , EDITFLAG = ? where CODE = ?', [$information->PASSWORD,
            $information->EDITFLAG,$information->PHONE]);
            return 1;
        };
        return 0;
    }
}