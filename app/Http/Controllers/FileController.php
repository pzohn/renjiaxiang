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
        $cardimgs = Cardimg::getCardimgs();
        $img_urls = [];
        foreach ($cardimgs as $k => $v) {
            $img_urls[] = [
            $v->img
            ];
        }
        $img_url = $img_urls[array_rand($img_urls,1)];
        return $img_url;
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
                $audio_url = "https://www.hatton.com/storage/".$filename;
                $cardimgs = Cardimg::getCardimgs();
                $img_urls = [];
                foreach ($cardimgs as $k => $v) {
                    $img_urls[] = [
                    $v->img
                    ];
                }
                $params = [
                    "url" => $url,
                    "parent_id" => $req->get('parent_id')
                ];
                Uploadpic::InsertPic($params);
            }
        }
       
    }
}
