<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class  Member extends Model {
        
    public static function memberInsert($params) {

        $member = new self;
        $member->name = array_get($params,"name");
        $member->email = array_get($params,"email");
        $member->phone = array_get($params,"phone");
        $member->save();
        return $member;
    }

    public static function memberSelect($wx_id) {
        $member = Member::where("wx_id", $wx_id)->first();
        if ($member) {
            return $member;
        }
    }

    public static function memberSelectById($id) {
        $member = Member::where("wx_id", $id)->first();
        if ($member) {
            return $member;
        }
    }

    public static function memberInsertId($id) {
        $member = new self;
        $member->wx_id = $id;
        $member->save();
        return $member;
    }

    public static function memberInsertPhone($phone) {
        $member = new self;
        $member->phone = $phone;
        $member->save();
        return $member;
    }

    public static function CollectUpdate($phone,$collect_ids) {
        $member = Member::where("phone", $phone)->first();
        if ($member) {
            $member->collect_ids = $collect_ids;
            $member->update();
            return $member;
        }
    }

    public static function CollectUpdateById($id,$collect_ids) {
        $member = Member::where("wx_id", $id)->first();
        if ($member) {
            $member->collect_ids = $collect_ids;
            $member->update();
            return $member;
        }
    }

    public static function memberUpdateWxId($params) {
        $member = Member::where("wx_id", array_get($params,"wx_id"))->first();
        if ($member) {
            $member->name = array_get($params,"name");
            $member->email = array_get($params,"email");
            $member->sex = array_get($params,"sex");
            $member->age = array_get($params,"age");
            $member->phone = array_get($params,"phone");
            $member->update();
            return $member;
        }
    }
}