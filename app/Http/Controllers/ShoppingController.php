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
            'flag' => $req->get('flag'),
            'price' => $req->get('price'),
            'type' => $req->get('type'),
            'oper' => $req->get('oper')
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

    public function getIndexset() {
        $title = 'title';
        $indexLunbos = Indexset::GetIndexByType(1);
        $indexGoods = Indexset::GetIndexByType(2);
        $indexWeeks = Indexset::GetIndexByType(3);
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
        $shoppings =  Shopping::shoppingsSelectByName($name);
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

    public function shoppingGet() {
        $file = 'title';
        $shoppings = Shopping::shoppingsSelect();
        if (count($shoppings)){
            $shoppingsTmp = [];
            foreach ($shoppings as $k => $v) {
                $arry = Image::GetImageUrlByParentId($v->id,$file,$v->type);
                $url = $arry[0];
                $shoppingsTmp[] = [
                "id" => $v->id,
                "name" => $v->name,
                "url" => $url,
                "type" => Shoppingtype::GetTypeById($v->type)->name,
                "price" => $v->price,
                "royalty" => $v->royalty,
                "time" => $v->updated_at->format('Y-m-d H:i:s')
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
}