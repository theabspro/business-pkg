<?php

Route::group(['namespace' => 'Abs\BusinessPkg', 'middleware' => ['web', 'auth'], 'prefix' => 'business-pkg'], function () {

	//LOB
	Route::get('/lobs/get-list', 'LobController@getLobPkgList')->name('getLobPkgList');
	Route::get('/lob/get-form-data/', 'LobController@getLobPkgFormData')->name('getLobPkgFormData');
	Route::post('/lob/save', 'LobController@saveLob')->name('saveLob');
	Route::get('/lob/delete/{id}', 'LobController@deleteLob')->name('deleteLob');

	//LOB
	Route::get('/sbus/get-list', 'SbuController@getSbuList')->name('getSbuList');
	Route::get('/sbu/get-form-data/{id?}', 'SbuController@getSbuFormData')->name('getSbuFormData');
	Route::post('/sbu/save', 'SbuController@saveSbu')->name('saveSbu');
	Route::get('/sbu/delete/{id}', 'SbuController@deleteSbu')->name('deleteSbu');

});