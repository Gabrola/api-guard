<?php
namespace Chrisbjr\ApiGuard\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;

/**
 * An Eloquent Model: 'ApiKey'
 *
 * @property integer $id
 * @property integer $user_id
 * @property string $key
 * @property integer $level
 * @property boolean $ignore_limits
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $deleted_at
 */
class ApiKey extends Model
{
    protected $table = 'api_keys';

    public function user()
    {
        return $this->belongsTo(Config::get('auth.model'));
    }

    public function generateKey()
    {
        do {
            $salt = sha1(time() . mt_rand());
            $newKey = substr($salt, 0, 40);
        } // Already in the DB? Fail. Try again
        while ($this->keyExists($newKey));

        return $newKey;
    }

    private function keyExists($key)
    {
        $apiKeyCount = ApiKey::where('key', '=', $key)->limit(1)->count();

        if ($apiKeyCount > 0) return true;

        return false;
    }
}