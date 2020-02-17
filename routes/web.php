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

Route::post('/onPayShopping', 'PayController@onPayShopping');

Route::post('/onPayShoppingFix', 'PayController@onPayShoppingFix');

Route::post('/onPayForCertFix', 'PayController@onPayForCertFix');

Route::post('/onPayForCert', 'PayController@onPayForCert');

Route::post('/onRePay', 'PayController@onRePay');

Route::post('/upload', 'FileController@upload');

Route::post('/getPostcard', 'FileController@getPostcard');

Route::post('/getPostcardById', 'FileController@getPostcardById');

Route::post('/loginByPhone', 'UserController@loginByPhone');

Route::post('/certInsert', 'CertController@certInsert');

Route::post('/certsSelect', 'CertController@certsSelect');

Route::post('/certdelete', 'CertController@certdelete');

Route::post('/certupdate', 'CertController@certupdate');

Route::post('/getCertsNum', 'CertController@getCertsNum');

Route::post('/certStock', 'CertController@certStock');

Route::post('/getOrderAllForPerson', 'PayController@getOrderAllForPerson');

Route::post('/getOrderUnPayForPerson', 'PayController@getOrderUnPayForPerson');

Route::post('/getOrderUnsendForPerson', 'PayController@getOrderUnsendForPerson');

Route::post('/getOrderUnreceiveForPerson', 'PayController@getOrderUnreceiveForPerson');

Route::post('/getOrderFinishForPerson', 'PayController@getOrderFinishForPerson');

Route::post('/getOrderRefundForPerson', 'PayController@getOrderRefundForPerson');

Route::post('/getOrderUnPay', 'PayController@getOrderUnPay');

Route::post('/getOrderUnsend', 'PayController@getOrderUnsend');

Route::post('/getOrderSend', 'PayController@getOrderSend');

Route::post('/mangerLogin', 'UserController@mangerLogin');

Route::post('/geTrades', 'PayController@geTrades');

Route::post('/hideOrder', 'PayController@hideOrder');

Route::post('/postRefund', 'PayController@postRefund');

Route::post('/getShareForPerson', 'PayController@getShareForPerson');

Route::post('/onPayShoppingFree', 'PayController@onPayShoppingFree');

Route::post('/onPayrCertFree', 'PayController@onPayrCertFree');

Route::post('/repayStock', 'PayController@repayStock');

Route::post('/getCards', 'PayController@getCards');

Route::post('/uploadOne', 'FileController@uploadOne');

Route::post('/uploadOneEx', 'FileController@uploadOneEx');

Route::post('/editOne', 'FileController@editOne');

Route::post('/deleteAll', 'FileController@deleteAll');

Route::post('/uploadOneRepeat', 'FileController@uploadOneRepeat');

Route::post('/shoppingInsert', 'ShoppingController@shoppingInsert');

Route::post('/shoppingGetByType', 'ShoppingController@shoppingGetByType');

Route::post('/shoppingGetById', 'ShoppingController@shoppingGetById');

Route::post('/shoppingGetByCollect', 'ShoppingController@shoppingGetByCollect');

Route::post('/makeTrades', 'ShoppingController@makeTrades');

Route::post('/getIndexset', 'ShoppingController@getIndexset');

Route::post('/getInfoByName', 'ShoppingController@getInfoByName');

Route::post('/shoppingGet', 'ShoppingController@shoppingGet');

Route::post('/shoppingUpdatePart', 'ShoppingController@shoppingUpdatePart');

Route::post('/shoppingOff', 'ShoppingController@shoppingOff');

Route::post('/shoppingsOff', 'ShoppingController@shoppingsOff');

Route::post('/getFixedAddresses', 'ShoppingController@getFixedAddresses');

Route::post('/getFixedAddress', 'ShoppingController@getFixedAddress');

Route::post('/updateShoppingType', 'ShoppingController@updateShoppingType');

Route::post('/downGet', 'ShoppingController@downGet');

Route::post('/shoppingUp', 'ShoppingController@shoppingUp');

Route::post('/shoppingsUp', 'ShoppingController@shoppingsUp');

Route::post('/updateStockEx', 'ShoppingController@updateStockEx');

Route::post('/getAddress', 'UserController@getAddress');

Route::post('/getAddressById', 'UserController@getAddressById');

Route::post('/insertAddress', 'UserController@insertAddress');

Route::post('/getAddressByLoginId', 'UserController@getAddressByLoginId');

Route::post('/updateAddress', 'UserController@updateAddress');

Route::post('/delAddress', 'UserController@delAddress');

Route::post('/collect', 'UserController@collect');

Route::post('/iscollect', 'UserController@iscollect');

Route::post('/getCollect', 'UserController@getCollect');

Route::post('/getWxUser', 'UserController@getWxUser');

Route::post('/updateWxBaseInfo', 'UserController@updateWxBaseInfo');

Route::post('/memberSelect', 'UserController@memberSelect');

Route::post('/memberUpdate', 'UserController@memberUpdate');

Route::post('/getBaseInfo', 'UserController@getBaseInfo');

Route::post('/BaseInfoUpdate', 'UserController@BaseInfoUpdate');

Route::post('/getExpress', 'UserController@getExpress');

Route::post('/getExpressById', 'UserController@getExpressById');

Route::post('/getOcrResult', 'ImageController@getOcrResult');