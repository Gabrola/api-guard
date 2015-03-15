<?php
namespace Chrisbjr\ApiGuard\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * An Eloquent Model: 'ApiLog'
 *
 * @property integer $id
 * @property integer $api_key_id
 * @property string $route
 * @property string $method
 * @property string $params
 * @property string $ip_address
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ApiLog extends Model
{
    protected $table = 'api_logs';

    public function user()
    {
        return $this->hasOne('ApiKey');
    }

}