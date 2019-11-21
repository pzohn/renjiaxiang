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

Route::post('/onPayGroup', 'PayController@onPayGroup');

Route::post('/onPayBack', 'PayController@onPayBack');

Route::post('/onPayShopBack', 'PayController@onPayShopBack');

Route::post('/onPayGroupBack', 'PayController@onPayGroupBack');

Route::post('/getCard', 'PayController@getCard');

Route::post('/getShop', 'PayController@getShop');

Route::post('/resetPass', 'UserController@resetPass');

Route::post('/flashShop', 'PayController@flashShop');

Route::post('/getShopNopass', 'PayController@getShopNopass');

Route::post('/getParter', 'PayController@getParter');

Route::post('/getParterInfo', 'PayController@getParterInfo');

Route::post('/updateFoodandCar', 'PayController@updateFoodandCar');

Route::post('/getShopById', 'PayController@getShopById');

Route::post('/getGroup', 'PayController@getGroup');

Route::post('/IsUnUse', 'PayController@IsUnUse');

Route::post('/upload', 'FileController@upload');

Route::post('/getPostcard', 'FileController@getPostcard');

Route::post('/getPostcardById', 'FileController@getPostcardById');

Route::post('/loginByPhone', 'UserController@loginByPhone');

Route::post('/certInsert', 'CertController@certInsert');

Route::post('/certsSelect', 'CertController@certsSelect');

Route::post('/certdelete', 'CertController@certdelete');

Route::post('/certupdate', 'CertController@certupdate');

Route::post('/getOrderAll', 'PayController@getOrderAll');

Route::post('/getOrderUnPay', 'PayController@getOrderUnPay');

Route::post('/getOrderUnsend', 'PayController@getOrderUnsend');

Route::post('/getOrderSend', 'PayController@getOrderSend');

Route::post('/mangerLogin', 'UserController@mangerLogin');

Route::post('/geTradeMessage', 'PayController@geTradeMessage');

Route::post('/getOrderAllMessage', 'PayController@getOrderAllMessage');

Route::post('/uploadOne', 'FileController@uploadOne');

Route::post('/uploadOneRepeat', 'FileController@uploadOneRepeat');

Route::post('/shoppingInsert', 'ShoppingController@shoppingInsert');

Route::post('/shoppingGetByType', 'ShoppingController@shoppingGetByType');

Route::post('/shoppingGetById', 'ShoppingController@shoppingGetById');

Route::post('/makeTrades', 'ShoppingController@makeTrades');

Route::post('/getAddress', 'UserController@getAddress');

Route::post('/getAddressById', 'UserController@getAddressById');

Route::post('/insertAddress', 'UserController@insertAddress');

Route::post('/updateAddress', 'UserController@updateAddress');

Route::post('/delAddress', 'UserController@delAddress');