<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
            }
            return $information;
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
                $information->PASSWORD = array_get($params,"PASSWORD");;
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
                $information->CARDID = array_get($params,"CARDID");
                $information->CARDNUM = array_get($params,"CARDNUM");
                
                \DB::update('update information set NAME = ?, AGE = ?,ADDRESS = ?,FATHER = ?,
                MOTHER = ?,CARDID = ?, CARDNUM = ? where CODE = ?', [$information->NAME,$information->AGE,$information->ADDRESS,
                $information->FATHER,$information->MOTHER,$information->CARDID,$information->CARDNUM,$information->PHONE]);
                return $information;
            }
            return 0;   
        }  
    }
}