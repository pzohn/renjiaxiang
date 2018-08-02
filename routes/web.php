<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return "hello world";
});

Route::get('/hh', function () {
    return "hi world";
});

Route::post('/user/save', 'UserController@setUser');

Route::get('/user/save', 'UserController@setUser');

Route::get('/onPay', 'PayController@onPay');

Route::post('/onPay', 'PayController@onPay');


Route::get('/onPayBack', 'PayController@onPayBack');

Route::post('/onPayBack', 'PayController@onPayBack');