<?php
if (Config::get('api-guard.generateApiKeyRoute')) {
    Route::get('apiguard/api_key', 'Chrisbjr\ApiGuard\Controllers\ApiKeyController@create');
}