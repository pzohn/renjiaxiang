<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use Illuminate\Support\Facades\Storage;
use App\Models\Postcard;
use App\Models\Cardimg;


class FileController extends Controller
{
    public function upload(Request $req)
    {
         $file = $req->file('file');
         if($file->isValid()) {
            $originalName = $file->getClientOriginalName(); // 文件原名
            $ext = $file->getClientOriginalExtension();     // 扩展名
            $realPath = $file->getRealPath();   //临时文件的绝对路径
            $type = $file->getClientMimeType();

            $filename = date('Y-m-d-H-i-s') . '-' . uniqid() . '.' . $ext;
            $bool = Storage::disk('public')->put($filename, file_get_contents($realPath));
            //var_dump($bool);
            if ($bool){
                $audio_url = "https://www.hattonstar.com/storage/".$filename;
                $cardimgs = Cardimg::getCardimgs();
                $img_urls = [];
                foreach ($cardimgs as $k => $v) {
                    $img_urls[] = $v->img;
                }
                $img_url_key = array_rand($img_urls,1);
                $img_url = "https://www.hattonstar.com/card/" . $img_urls[$img_url_key];

                $params = [
                    "img_url" => $img_url,
                    "audio_url" => $audio_url,
                    "phone" => $req->get('phone')
                ];
                Postcard::InsertPostcard($params);
            }
        }
       
    }

    public function getPostcard(Request $req) {
        $postcard = Postcard::GetPostcard($req->get('phone'));
        return $postcard;
    }

    public function getPostcardById(Request $req) {
        $postcard = Postcard::GetPostcardById($req->get('id'));
        return $postcard;
    }

    public function uploadOne(Request $req)
    {
         $file = $req->file('file');
         if($file->isValid()) {
            $originalName = $file->getClientOriginalName(); // 文件原名
            $ext = $file->getClientOriginalExtension();     // 扩展名
            $realPath = $file->getRealPath();   //临时文件的绝对路径
            $type = $file->getClientMimeType();

            $filename = 'hubin/' . date('Y-m-d-H-i-s') . '-' . uniqid() . '.' . $ext;
            $bool = Storage::disk('public')->put($filename, file_get_contents($realPath));
            if ($bool){
                $url = "https://www.hattonstar.com/storage/".$filename;
                return [
                    "code" => 0,
                    "msg" => "",
                    "data" => [
                        "src" => $url,
                        "realPath" => $realPath
                    ]
                ];
            }
        }
        return [
            "code" => 1,
            "msg" => "",
            "data" => []
        ];
    }
}
