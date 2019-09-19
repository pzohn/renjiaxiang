<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cert;
use App\Models\Shopping;
use App\Models\Wxinfo;

class UserController extends Controller
{
    public function certInsert(Request $req) {
       $cert = Cert::certSelect( $req->get('id'));
       if ($cert){
            $count = $cert->count + $req->get('count');
            Cert::certupdate($id,$count);
            return $cert;
       }
       $params = [
        'username' => $req->get('username'),
        'shopping_id' => $req->get('id'),
        'count' => "count"
        ];
        return Cert::certInsert($params);
    }

    public function certsSelect(Request $req) {
        $username = $req->get('username');
        $certs = Cert::certsSelect($username);
        $certsTmp = [];
        foreach ($certs as $k => $v) {
            $shopping = Shopping::shoppingSelect($v->shopping_id);
            $wx_id = Wxinfo::GetWxinfoById($shopping->wx_id);
            $certsTmp[] = [
            "shoppingid" => $shopping->id,
            "name" => $shopping->name,
            "title_pic" => Image::GetImage($wx_id->title_id)->url,
            "price" => $shopping->price,
            "count" => $v->count,
            "id" => $v->id
            ];
        }
        return  $certsTmp;
     }

     public function certdelete(Request $req) {
        $id = $req->get('id');
        Cert::certdelete($id);
     }

     public function certupdate(Request $req) {
        $id = $req->get('id');
        $count = $req->get('count');
        Cert::certupdate($id,$count);
     }
}