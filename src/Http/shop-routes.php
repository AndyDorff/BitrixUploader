<?php

Route::group(['middleware' => ['web', 'theme', 'locale', 'currency']], function () {

    Route::get('/bitrixuploader', 'Aniart\BitrixUploader\Http\Controllers\Shop\BitrixUploaderController@index')->defaults('_config', [
        'view' => 'bitrixuploader::shop.index',
    ])->name('bitrixuploader.shop.index');

});