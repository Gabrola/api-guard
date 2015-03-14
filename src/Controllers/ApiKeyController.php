<?php namespace Chrisbjr\ApiGuard\Controllers;

use Chrisbjr\ApiGuard\Models\ApiKey;
use Chrisbjr\ApiGuard\Transformers\ApiKeyTransformer;
use Illuminate\Support\Facades\Input;

class ApiKeyController extends ApiGuardController
{
    public function create()
    {
        $apiKey = new ApiKey;
        $apiKey->key = $apiKey->generateKey();
        $apiKey->user_id = Input::json('user_id', 0);
        $apiKey->level = Input::json('level', 10);
        $apiKey->ignore_limits = Input::json('ignore_limits', 1);

        if ($apiKey->save() === false) {
            return $this->response->errorInternalError("Failed to save API key to the database.");
        }

        $this->response->setStatusCode(201);

        return $this->response->withItem($apiKey, new ApiKeyTransformer);
    }
} 