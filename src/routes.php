<?php
if (Config::get('apiguard.generateApiKeyRoute')) {
    Route::get('apiguard/api_key', 'Chrisbjr\ApiGuard\Controllers\ApiKeyController@create');
}