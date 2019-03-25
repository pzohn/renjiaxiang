<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Campactivity;
use App\Models\Image;

class CampactivityController extends Controller
{
    public function getCampactivities() {
        $campactivities = Campactivity::GetCampactivities();
        $campactivitiesTmp = [];
        foreach ($campactivities as $k => $v) {
            $campactivitiesTmp[] = [
            "id" => $v->id,
            "name" => $v->name,
	        "title_pic" => Image::GetImage($v->title_pic_id)->url
            ];
        }
        return [
            $campactivitiesTmp
        ];
    }
}