<?php namespace Chrisbjr\ApiGuard\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ApiKey
 */
class ApiLog extends Model
{
    protected $table = 'api_logs';

    public function user()
    {
        return $this->hasOne('ApiKey');
    }

}