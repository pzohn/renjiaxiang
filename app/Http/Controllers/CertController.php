<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cert;
use App\Models\Shopping;
use App\Models\Image;

class CertController extends Controller
{
    public function certInsert(Request $req) {
       $cert = Cert::certSelect( $req->get('id'),$req->get('wx_id'));
       if ($cert){
            $count = $cert->count + $req->get('count');
            Cert::certupdate($cert->id,$count);
            return $cert;
       }
       $params = [
        'wx_id' => $req->get('wx_id'),
        'shopping_id' => $req->get('id'),
        'count' => $req->get('count')
        ];
        return Cert::certInsert($params);
    }

    public function certsSelect(Request $req) {
        $wx_id = $req->get('wx_id');
        $certs = Cert::certsSelect($wx_id);
        $title = 'title';
        $certsTmp = [];
        foreach ($certs as $k => $v) {
            $shopping = Shopping::shoppingSelect($v->shopping_id);
            if ($shopping) {
               $certsTmp[] = [
                  "shoppingid" => $shopping->id,
                  "name" => $shopping->name,
                  "title_pic" => Image::GetImageUrlByParentId($shopping->id,$title,$shopping->type),
                  "price" => $shopping->price,
                  "vip_price" => $shopping->vip_price,
                  "count" => $v->count,
                  "id" => $v->id
                  ];
            }
        }
        return  $certsTmp;
     }

     public function certdelete(Request $req) {
        $id = $req->get('id');
        Cert::certdelete($id);
        return 1;
     }

     public function certupdate(Request $req) {
        $id = $req->get('id');
        $count = $req->get('count');
        Cert::certupdate($id,$count);
     }

     public function certStock(Request $req) {
      $certInfo = $req->get('certInfo');
      $arryCert = preg_split("/@/",$certInfo);
      $str = '';
      $index = 0;
      foreach ($arryCert as $v) {
         $item = $v;
         $arryItem = preg_split("/,/",$item);
         $id = $arryItem[0];
         $num = $arryItem[1];
         $shopping = Shopping::shoppingSelect($id);
         if ($shopping){
            if (($shopping->stock == 0) || ($shopping->stock < $num)){
               $str = $str . $shopping->name;
               $str = $str . ',';
               $index ++;
            }
         }
      }
      if ($index){
         $str = rtrim($str, ",");
      }
      return [
         'result' => $index,
         'str' => $str
      ];
   }

     public function getCertsNum(Request $req) {
      $wx_id = $req->get('wx_id');
      $certs = Cert::certsSelect($wx_id);
      $count = 0;
      foreach ($certs as $k => $v) {
         $count += $v->count;
      }
      return $count;
   }
}