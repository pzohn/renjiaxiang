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
                $information = Information::where("CODE", array_get($params,"PHONE"))->first();
                if(!$information)
                {
                    return 1;
                }
                return $information;
                $cardid = $information->CARDID;
                $carddesc = "";
                $cardnum = 0;
                if($cardid != 0)
                {
                    $cardnum = $information->CARDNUM;
                    if ($information->OTHER){
                        $cardid = $information->OTHER;
                        $cardnum = $information->OTHERNUM;
                    }
                    $cardtmp =  Card::getCard($cardid);
                    if($cardtmp){
                        $carddesc = $cardtmp->NAME;
                    }
                    
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
                    "CARDNUM"=> $cardnum,
                    "SCHOOL"=> $information->SCHOOL,
                    "CLASS"=> $information->CLASS,
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
                $information->SCHOOL = array_get($params,"SCHOOL");
                $information->CLASS = array_get($params,"CLASS");
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
                // $information->ADDRESS = array_get($params,"ADDRESS");
                // $information->FATHER = array_get($params,"FATHER");
                // $information->MOTHER = array_get($params,"MOTHER");
                $information->SCHOOL = array_get($params,"SCHOOL");
                $information->CLASS = array_get($params,"CLASS");
                $information->EDITFLAG = 1;
                
                // \DB::update('update information set NAME = ?, AGE = ?,ADDRESS = ?,FATHER = ?,
                // MOTHER = ?, EDITFLAG = ? where CODE = ?', [$information->NAME,$information->AGE,$information->ADDRESS,
                // $information->FATHER,$information->MOTHER,$information->EDITFLAG,$information->PHONE]);
                \DB::update('update information set NAME = ?, AGE = ?,SCHOOL = ?,CLASS = ?,
                EDITFLAG = ? where CODE = ?', [$information->NAME,$information->AGE,$information->SCHOOL,
                $information->CLASS,$information->EDITFLAG,$information->PHONE]);
                return $information;
            }
            return 0;   
        }  
    }

    public static function updateCard($params) {
        $information = Information::where("CODE", array_get($params,"PHONE"))->first();
        if ($information) {
            $CARDNUM = $information->CARDNUM;
            if ($CARDNUM > 0){
                $information->CARDNUM = array_get($params,"CARDNUM") + $CARDNUM;
                $information->EDITFLAG = 1;
                \DB::update('update information set CARDNUM = ?, EDITFLAG = ? where CODE = ?', [
                $information->CARDNUM,$information->EDITFLAG,$information->PHONE]);
            }else if($CARDNUM == 0){
                $information->CARDID = array_get($params,"CARDID");
                $information->CARDNUM = array_get($params,"CARDNUM");
                $information->EDITFLAG = 1;
                \DB::update('update information set CARDID = ?, CARDNUM = ?, EDITFLAG = ? where CODE = ?', [$information->CARDID,
                $information->CARDNUM,$information->EDITFLAG,$information->PHONE]);
            }
            return $information;
        }
    }

    public static function updateCardEx($params) {
        $information = Information::where("CODE", array_get($params,"PHONE"))->first();
        if ($information) {
            $OTHERNUM = $information->OTHERNUM;
            if ($OTHERNUM > 0){
                $information->OTHERNUM = array_get($params,"CARDNUM") + $OTHERNUM;
                $information->EDITFLAG = 1;
                \DB::update('update information set OTHERNUM = ?, EDITFLAG = ? where CODE = ?', [
                $information->OTHERNUM,$information->EDITFLAG,$information->PHONE]);
            }else if($OTHERNUM == 0){
                $information->OTHER = array_get($params,"CARDID");
                $information->OTHERNUM = array_get($params,"CARDNUM");
                $information->EDITFLAG = 1;
                \DB::update('update information set OTHER = ?, OTHERNUM = ?, EDITFLAG = ? where CODE = ?', [$information->OTHER,
                $information->OTHERNUM,$information->EDITFLAG,$information->PHONE]);
            }
            return $information;
        }
    }

    public static function getInformation($params) {
        $information = Information::where("CODE", array_get($params,"PHONE"))->first();
        if ($information){
            $cardid = $information->CARDID;
            $carddesc = "";
            $cardnum = 0;
            if($cardid != 0)
            {
                $cardnum = $information->CARDNUM;
                if ($information->OTHER){
                    $cardid = $information->OTHER;
                    $cardnum = $information->OTHERNUM;
                }
                $cardtmp =  Card::getCard($cardid);
                if($cardtmp){
                    $carddesc = $cardtmp->NAME;
                }
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
                "CARDNUM"=> $cardnum,
                "SCHOOL"=> $information->SCHOOL,
                "CLASS"=> $information->CLASS,
                "AUTHORITY" => $information->AUTHORITY
            ];
            return $result;
        }
        return -1;
    }

    public static function getInformationEx($phone) {
        $information = Information::where("CODE", $phone)->first();
        return $information;
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

    public static function mangerLogin($params) {
        $information = Information::where("CODE", array_get($params,"PHONE"))->first();
        if (!$information)
        {
            $result_data = [
                'code' => 1,
                'msg' => '用户未注册'
            ];
            return $result_data;
        }
        else
        {
            $information = Information::where("CODE", array_get($params,"PHONE"))->where("PASSWORD", array_get($params,"PASSWORD"))->first();
            if(!$information)
            {
                $result_data = [
                    'code' => 1,
                    'msg' => '用户名或密码不正确'
                ];
                return $result_data;
            } else {
                $result_data = [
                    'code' => 0,
                    'msg' => '登入成功',
                    'data' => $information
                ];
                return $result_data;
            }
        }
    }
}