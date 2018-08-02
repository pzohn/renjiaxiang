<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
//use App\Models\User;
use App\Models\Information;

class UserController extends Controller
{
    public function setUser(Request $req) {
       // $return_data = User::getAndSet($req->all());
       $return_data = Information::getAndSet($req->all());
        return $return_data;
    }

    public function getUser(Request $req) {

    }
}