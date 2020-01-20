<?php
Route::group(['namespace' => 'Abs\BusinessPkg\Api', 'middleware' => ['api']], function () {
	Route::group(['prefix' => 'business-pkg/api'], function () {
		Route::group(['middleware' => ['auth:api']], function () {
			// Route::get('taxes/get', 'TaxController@getTaxes');
		});
	});
});