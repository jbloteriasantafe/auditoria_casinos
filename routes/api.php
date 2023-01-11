<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['prefix' => 'AE','middleware' => 'check_API_token'],function(){
  Route::get('/',function(){//Para probar el acceso
    return 1;
  });
  Route::get('fechas/{DNI}','Autoexclusion\APIAEController@fechas');
  Route::get('finalizar/{DNI}','Autoexclusion\APIAEController@finalizar');
  Route::post('agregar','Autoexclusion\APIAEController@agregar');
});
