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

Route::post('/onGetUpdateResult', 'UserController@getUpdateResult');

Route::post('/onPay', 'PayController@onPay');

Route::post('/onPayShop', 'PayController@onPayShop');

Route::post('/onPayBack', 'PayController@onPayBack');

Route::post('/onPayShopBack', 'PayController@onPayShopBack');

Route::post('/getCard', 'PayController@getCard');

Route::post('/getShop', 'PayController@getShop');

Route::post('/resetPass', 'UserController@resetPass');

Route::post('/flashShop', 'PayController@flashShop');

Route::post('/getShopNopass', 'PayController@getShopNopass');
