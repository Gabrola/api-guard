<?php
if (Config::get('apiguard.generateApiKeyRoute')) {
    Route::get('apiguard/api_key', [
        'uses'  =>  'Chrisbjr\ApiGuard\Controllers\ApiKeyController@create',
        'apiMethods'    =>  [
            'create' => [
                'keyAuthentication' => false
            ]
        ]
    ]);
}