<?php

Route::group(['middleware' => ['web', 'admin']], function () {

    Route::get('/admin/bitrixuploader', 'Aniart\BitrixUploader\Http\Controllers\Admin\BitrixUploaderController@index')->defaults('_config', [
        'view' => 'bitrixuploader::admin.index',
    ])->name('bitrixuploader.admin.index');

});