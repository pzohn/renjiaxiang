<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Shopping;
use App\Models\Image;
use App\Models\Address;
use App\Models\Indexset;
use App\Models\Shoppingtype;

class ShoppingController extends Controller
{
    public function shoppingInsert(Request $req) {
        $params = [
            'name' => $req->get('name'),
            'price' => $req->get('price'),
            'type' => $req->get('type'),
            'oper' => $req->get('oper'),
            'royalty' => $req->get('royalty'),
            'integral' => $req->get('integral'),
            'shop_id' => $req->get('shop_id')
            ];
        $shoppingId = Shopping::shoppingRepeat($params);
        if ($shoppingId == 0) {
            $shopping = Shopping::shoppingInsert($params);
            if ($shopping) {
                $result_data = [
                    'code' => 0,
                    'msg' => '保存成功，请继续上传商品信息',
                    'data' => [
                        'id' => $shopping->id,
                        'type' =>  $shopping->type
                    ]
                ];
                return $result_data;
            }            
        }else {
            $result_data = [
                'code' => 0,
                'msg' => '已保存，请继续上传商品信息',
                'data' => [
                    'id' => $shoppingId,
                    'type' => Shopping::shoppingSelect($shoppingId)->type
                ]
            ];
            return $result_data;
        }
    }

    public function shoppingGetByType(Request $req) {
        $type = $req->get('type_id');
        $file = 'title';
        $shoppings = Shopping::shoppingGetByType($type);
        if (count($shoppings)){
            $shoppingsTmp = [];
            foreach ($shoppings as $k => $v) {
                $shoppingsTmp[] = [
                "id" => $v->id,
                "name" => $v->name,
                "url" => Image::GetImageUrlByParentId($v->id,$file,$v->type)
                ];
            }
            $result_data = [
                'code' => 0,
                'msg' => '获得商品信息成功',
                'data' => [
                    'shoppings' => $shoppingsTmp
                ]
            ];
            return $result_data;
        }else{
            $result_data = [
                'code' => 1,
                'msg' => '获得商品信息失败',
                'data' => []
            ];
            return $result_data;
        }
    }

    public function shoppingGetById(Request $req) {
        $id = $req->get('id');
        $lunbo = 'lunbo';
        $detail = 'detail';
        $video = 'video';
        $title = 'title';
        $shopping = Shopping::shoppingSelect($id);
        if ($shopping){
            $result_data = [
                'code' => 0,
                'msg' => '获得商品信息成功',
                'data' => [
                    'shopping' => [
                        "id" => $shopping->id,
                        "name" => $shopping->name,
                        "price" => $shopping->price,
                        "lunbo" => Image::GetImageUrlByParentId($shopping->id,$lunbo,$shopping->type),
                        "detail" => Image::GetImageUrlByParentId($shopping->id,$detail,$shopping->type),
                        "video" => Image::GetImageUrlByParentId($shopping->id,$video,$shopping->type),
                        "title" => Image::GetImageUrlByParentId($shopping->id,$title,$shopping->type)
                    ]
                ]
            ];
            return $result_data;
        }
        else{
            $result_data = [
                'code' => 1,
                'msg' => '获得商品信息失败',
                'data' => []
            ];
            return $result_data;
        }
    }

    public function makeTrades(Request $req) {
        $id = $req->get('id');
        $title = 'title';
        $shopping = Shopping::shoppingSelect($id);
        $address = Address::GetAddress($req->get('login_id'));
        return [
            "name" => $shopping->name,
            "charge" => $shopping->price,
            "title_pic" => Image::GetImageUrlByParentId($shopping->id,$title,$shopping->type),
            "address" => $address
        ];
    }

    public function shoppingOff(Request $req) {
        $id = $req->get('id');
        $shopping = Shopping::shoppingOff($id);
        $this->delFlag(1,$shopping->shop_id,$shopping->id);
        $this->delFlag(2,$shopping->shop_id,$shopping->id);
        $this->delFlag(3,$shopping->shop_id,$shopping->id);
        return $shopping;
    }

    public function shoppingsOff(Request $req) {
        $ids = $req->get('ids');
        $pos = strpos($ids, '@');
        if ($pos == false){
            $shopping = Shopping::shoppingOff($ids);
            $this->delFlag(1,$shopping->shop_id,$shopping->id);
            $this->delFlag(2,$shopping->shop_id,$shopping->id);
            $this->delFlag(3,$shopping->shop_id,$shopping->id);
            return 0;
        }else{
            $arry = preg_split("/@/",$ids);
            foreach ($arry as $v) {
                $shopping = Shopping::shoppingOff($v);
                $this->delFlag(1,$shopping->shop_id,$shopping->id);
                $this->delFlag(2,$shopping->shop_id,$shopping->id);
                $this->delFlag(3,$shopping->shop_id,$shopping->id);
            }
            return 0;
        }
        return 1;
    }

    public function shoppingUpdatePart(Request $req) {
        $params = [
            "id" => $req->get('id'),
            "name" => $req->get('name'),
            "price" => $req->get('price'),
            "royalty" => $req->get('royalty'),
            "integral" => $req->get('integral')
        ];
        $shopping = Shopping::shoppingUpdatePart($params);
        if ($this->switchToflag($req->get('post_switch'))){
            $this->insertFlag(1,$shopping->shop_id,$shopping->id);
        }else {
            $this->delFlag(1,$shopping->shop_id,$shopping->id);
        }

        if ($this->switchToflag($req->get('one_switch'))){
            $this->insertFlag(2,$shopping->shop_id,$shopping->id);
        }else {
            $this->delFlag(2,$shopping->shop_id,$shopping->id);
        }

        if ($this->switchToflag($req->get('good_switch'))){
            $this->insertFlag(3,$shopping->shop_id,$shopping->id);
        }else {
            $this->delFlag(3,$shopping->shop_id,$shopping->id);
        }
        return  $shopping;
    }

    public function shoppingGetByCollect(Request $req) {
        $ids = $req->get('ids');
        $pos = strpos($ids, '@');
        $title = 'title';
        if ($pos == false){
            $shopping = Shopping::shoppingSelect($ids);
            if ($shopping){
                $shoppings[] = [
                    "id" => $ids,
                    "name" => $shopping->name,
                    "title_pic" => Image::GetImageUrlByParentId($shopping->id,$title,$shopping->type),
                    "shopping_id" => $shopping->id
                    ];
                return $shoppings;
            }
        }else{
            $arry = preg_split("/@/",$ids);
            $shoppings = [];
            foreach ($arry as $v) {
                $shopping = Shopping::shoppingSelect($v);
                if ($shopping){
                    $shoppings[] = [
                        "id" => $v,
                        "name" => $shopping->name,
                        "title_pic" => Image::GetImageUrlByParentId($shopping->id,$title,$shopping->type),
                        "shopping_id" => $shopping->id
                   ];
                }
            }
            return $shoppings;
        }
    }

    public function getIndexset(Request $req) {
        $title = 'title';
        $indexLunbos = Indexset::GetIndexByType(1,$req->get('shop_id'));
        $indexGoods = Indexset::GetIndexByType(2,$req->get('shop_id'));
        $indexWeeks = Indexset::GetIndexByType(3,$req->get('shop_id'));
        $indexLunbosTmp = [];
        $indexGoodsTmp = [];
        $indexWeeksTmp = [];
        $index = 0;
        if (count($indexLunbos)){
            foreach ($indexLunbos as $k => $v) {
                $shopping = Shopping::shoppingSelect($v->object_id);
                $index ++;
                $indexLunbosTmp[] = [
                "id" => $v->object_id,
                "title_pic" => Image::GetImageUrlByParentId($shopping->id,$title,$shopping->type),
                ];
                if ($index == 3){
                    break;
                }
            }
        }
        $index = 0;
        if (count($indexGoods)){
            foreach ($indexGoods as $k => $v) {
                $shopping = Shopping::shoppingSelect($v->object_id);
                $indexGoodsTmp[] = [
                "id" => $v->object_id,
                "name" => $shopping->name,
                "price" => $shopping->price,
                "title_pic" => Image::GetImageUrlByParentId($shopping->id,$title,$shopping->type),
                ];
                if ($index == 6){
                    break;
                }
            }
        }
        $index = 0;
        if (count($indexWeeks)){
            foreach ($indexWeeks as $k => $v) {
                $shopping = Shopping::shoppingSelect($v->object_id);
                $indexWeeksTmp[] = [
                "id" => $v->object_id,
                "name" => $shopping->name,
                "price" => $shopping->price,
                "title_pic" => Image::GetImageUrlByParentId($shopping->id,$title,$shopping->type),
                ];
                if ($index == 6){
                    break;
                }
            }
        }
        return [
            "lunbo" => $indexLunbosTmp,
            "good" => $indexGoodsTmp,
            "week" => $indexWeeksTmp
        ];
    }

    public function getInfoByName(Request $req) {
        $name = $req->get('name');
        $title = 'title';
        $shoppings =  Shopping::shoppingsSelectByName($name,$req->get('shop_id'));
        $shoppingsTmp = [];
        foreach ($shoppings as $k => $v) {
            $shopping = Shopping::shoppingSelect($v->id);
            $shoppingsTmp[] = [
            "id" => $shopping->id,
            "name" => $shopping->name,
            "title_pic" => Image::GetImageUrlByParentId($shopping->id,$title,$shopping->type),
            "shopping_id" => $shopping->id
            ];
        }
        return  $shoppingsTmp;
    }

    public function shoppingGet(Request $req) {
        $shoppings = Shopping::shoppingsSelect($req->get('shop_id'));
        if (count($shoppings)){
            $shoppingsTmp = [];
            foreach ($shoppings as $k => $v) {
                $shoppingsTmp[] = [
                "id" => $v->id,
                "name" => $v->name,
                "avatar" => 'https://www.hattonstar.com/storage/'. Image::GetTitleUrlByParentId($v->id,$v->type),
                "type" => Shoppingtype::GetTypeById($v->type)->name,
                "price" => $v->price,
                "royalty" => $v->royalty,
                "integral" => $v->integral,
                "time" => $v->updated_at->format('Y-m-d H:i:s'),
                "flag" => $this->flagToswitch($v->shop_id,$v->id)
                ];
            }
            $result_data = [
                'code' => 0,
                'msg' => '获得商品信息成功',
                'data' =>  $shoppingsTmp
            ];
            return $result_data;
        }else{
            $result_data = [
                'code' => 1,
                'msg' => '获得商品信息失败',
                'data' => []
            ];
            return $result_data;
        }
    }

    protected function switchToflag($switch) {
        if ($switch == 'on'){
            return true;
        }else {
            return false;
        }
    }

    protected function flagToswitch($shop_id,$object_id) {
        $indexset1 = Indexset::GetIndexId(1,$shop_id,$object_id);
        $indexset2 = Indexset::GetIndexId(2,$shop_id,$object_id);
        $indexset3 = Indexset::GetIndexId(3,$shop_id,$object_id);
        if (($indexset1 == 0) && ($indexset2 == 0) && ($indexset3 == 0)){
            return 0;
        }else if (($indexset1) && ($indexset2 == 0) && ($indexset3 == 0)){
            return 1;
        }else if (($indexset1 == 0) && ($indexset2) && ($indexset3 == 0)){
            return 2;
        }else if (($indexset1 == 0) && ($indexset2 == 0) && ($indexset3)){
            return 3;
        }else if (($indexset1) && ($indexset2) && ($indexset3 == 0)){
            return 4;
        }else if (($indexset1) && ($indexset2 == 0) && ($indexset3)){
            return 5;
        }else if (($indexset1 == 0) && ($indexset2) && ($indexset3)){
            return 6;
        }else {
            return 7;
        }    
    }

    protected function delFlag($type,$shop_id,$object_id) {
        $indexsetId = Indexset::GetIndexId($type,$shop_id,$object_id);
        if ($indexsetId) {
            Indexset::DelIndex($indexsetId);
        }
    }

    protected function insertFlag($type,$shop_id,$object_id) {
        $indexsetId = Indexset::GetIndexId($type,$shop_id,$object_id);
        if ($indexsetId == 0) {
            Indexset::InsertIndex($type,$shop_id,$object_id);
        }
    }
}