<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Shopping;

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
                    'date' => [
                        'id' => $shopping->id
                    ]
                ];
                return $result_data;
            }            
        }else {
            $result_data = [
                'code' => 0,
                'msg' => '已保存，请继续上传商品信息',
                'date' => [
                    'id' => $shoppingId
                ]
            ];
            return $result_data;
        }
    }
}
