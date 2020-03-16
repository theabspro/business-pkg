<?php

Route::group(['namespace' => 'Abs\BusinessPkg', 'middleware' => ['web', 'auth'], 'prefix' => 'business-pkg'], function () {

	//LOB
	Route::get('/lobs/get-list', 'LobController@getLobPkgList')->name('getLobPkgList');
	Route::get('/lob/get-form-data/', 'LobController@getLobPkgFormData')->name('getLobPkgFormData');
	Route::post('/lob/save', 'LobController@saveLobPkg')->name('saveLobPkg');
	Route::get('/lob/delete/', 'LobController@deleteLobPkg')->name('deleteLobPkg');

	//LOB
	Route::get('/sbus/get-list', 'SbuController@getSbuPkgList')->name('getSbuPkgList');
	Route::get('/sbu/get-form-data/', 'SbuController@getSbuPkgFormData')->name('getSbuPkgFormData');
	Route::post('/sbu/save', 'SbuController@saveSbuPkg')->name('saveSbuPkg');
	Route::get('/sbu/delete/', 'SbuController@deleteSbuPkg')->name('deleteSbuPkg');

});