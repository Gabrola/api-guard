<?php namespace Chrisbjr\ApiGuard\Middleware;

use Chrisbjr\ApiGuard\Models\ApiKey;
use Closure;
use Illuminate\Support\Facades\Config;
use Chrisbjr\ApiGuard\Models\ApiLog;
use EllipseSynergie\ApiResponse\Laravel\Response;
use Illuminate\Contracts\Routing\Middleware;
use Illuminate\Support\Facades\Log;
use League\Fractal\Manager;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Input;

class ApiGuardMiddleware implements Middleware
{
    /**
     * @var ApiKey|null|static
     */
    protected $apiKey = null;

    /**
     * @var \EllipseSynergie\ApiResponse\Laravel\Response
     */
    protected $response;

    /**
     * @var \League\Fractal\Manager
     */
    protected $manager;

    /**
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $action = $request->route()->getAction();

        // Let's instantiate the response class first
        $this->manager = new Manager;

        $this->manager->parseIncludes(Input::get(Config::get('apiguard.includeKeyword', 'include'), array()));

        $this->response = new Response($this->manager);

        //Get route method
        $routeArray = Str::parseCallback($action['controller'], null);

        if (last($routeArray) == null) {
            // There is no method?
            return $this->response->errorMethodNotAllowed();
        }

        $controller = $routeArray[0];
        $method = $routeArray[1];

        //Get apiMethods from config
        $apiMethods = Config::get('apimethods.' . $controller . '.' . $method, array());

        // We should check if key authentication is enabled for this method
        $keyAuthentication = true;
        if (isset($apiMethods['keyAuthentication']) && $apiMethods['keyAuthentication'] === false) {
            $keyAuthentication = false;
        }

        if ($keyAuthentication === true) {

            $key = $request->header(Config::get('apiguard.keyName'));

            if (empty($key)) {
                // Try getting the key from elsewhere
                $key = Input::get(Config::get('apiguard.keyName'));
            }

            if (empty($key)) {
                // It's still empty!
                return $this->response->errorUnauthorized();
            }

            $this->apiKey = ApiKey::where('key', '=', $key)->first();

            if (!isset($this->apiKey->id)) {
                // ApiKey not found
                return $this->response->errorUnauthorized();
            }

            // API key exists
            // Check level of API
            if (!empty($apiMethods['level'])) {
                if ($this->apiKey->level < $apiMethods['level']) {
                    return $this->response->errorForbidden();
                }
            }
        }

        // Then check the limits of this method
        if (!empty($apiMethods['limits'])) {

            if (Config::get('apiguard.logging') === false) {
                Log::warning("[Chrisbjr/ApiGuard] You specified a limit in the $method method but API logging needs to be enabled in the configuration for this to work.");
            }

            $limits = $apiMethods['limits'];

            // We get key level limits first
            if ($this->apiKey != null && !empty($limits['key'])) {

                Log::info("key limits found");

                $keyLimit = (!empty($limits['key']['limit'])) ? $limits['key']['limit'] : 0;
                if ($keyLimit == 0 || is_integer($keyLimit) == false) {
                    Log::warning("[Chrisbjr/ApiGuard] You defined a key limit to the " . $action['controller'] . " route but you did not set a valid number for the limit variable.");
                } else {
                    if (!$this->apiKey->ignore_limits) {
                        // This means the apikey is not ignoring the limits

                        $keyIncrement = (!empty($limits['key']['increment'])) ? $limits['key']['increment'] : Config::get('apiguard.keyLimitIncrement');

                        $keyIncrementTime = strtotime('-' . $keyIncrement);

                        if ($keyIncrementTime == false) {
                            Log::warning("[Chrisbjr/ApiGuard] You have specified an invalid key increment time. This value can be any value accepted by PHP's strtotime() method");
                        } else {
                            // Count the number of requests for this method using this api key
                            $apiLogCount = ApiLog::where('api_key_id', '=', $this->apiKey->id)
                                ->where('route', '=', $action['controller'])
                                ->where('method', '=', $request->getMethod())
                                ->where('created_at', '>=', date('Y-m-d H:i:s', $keyIncrementTime))
                                ->where('created_at', '<=', date('Y-m-d H:i:s'))
                                ->count();

                            if ($apiLogCount >= $keyLimit) {
                                Log::warning("[Chrisbjr/ApiGuard] The API key ID#{$this->apiKey->id} has reached the limit of {$keyLimit} in the following route: " . $action['controller']);
                                return $this->response->errorUnwillingToProcess('You have reached the limit for using this API.');
                            }
                        }
                    }
                }
            }

            // Then the overall method limits
            if (!empty($limits['method'])) {
                $methodLimit = (!empty($limits['method']['limit'])) ? $limits['method']['limit'] : 0;
                if ($methodLimit == 0 || is_integer($methodLimit) == false) {
                    Log::warning("[Chrisbjr/ApiGuard] You defined a method limit to the " . $action['controller'] . " route but you did not set a valid number for the limit variable.");
                } else {
                    if ($this->apiKey != null && $this->apiKey->ignore_limits == true) {
                        // then we skip this
                    } else {
                        $methodIncrement = (!empty($limits['method']['increment'])) ? $limits['method']['increment'] : Config::get('apiguard.keyLimitIncrement');

                        $methodIncrementTime = strtotime('-' . $methodIncrement);

                        if ($methodIncrementTime == false) {
                            Log::warning("[Chrisbjr/ApiGuard] You have specified an invalid method increment time. This value can be any value accepted by PHP's strtotime() method");
                        } else {
                            // Count the number of requests for this method
                            $apiLogCount = ApiLog::where('route', '=', $action['controller'])
                                ->where('method', '=', $request->getMethod())
                                ->where('created_at', '>=', date('Y-m-d H:i:s', $methodIncrementTime))
                                ->where('created_at', '<=', date('Y-m-d H:i:s'))
                                ->count();

                            if ($apiLogCount >= $methodLimit) {
                                Log::warning("[Chrisbjr/ApiGuard] The API has reached the method limit of {$methodLimit} in the following route: " . $action['controller']);
                                return $this->response->setStatusCode(429)->withError('The limit for using this API method has been reached', 'GEN-LIMIT-REACHED');
                            }
                        }
                    }
                }
            }
        }
        // End of cheking limits

        if (Config::get('apiguard.logging') && $keyAuthentication == true) {
            // Log this API request
            $apiLog = new ApiLog;
            $apiLog->api_key_id = $this->apiKey->id;
            $apiLog->route = $action['controller'];
            $apiLog->method = $request->getMethod();
            $apiLog->params = http_build_query(Input::all());
            $apiLog->ip_address = $request->getClientIp();
            $apiLog->save();
        }

        return $next($request);
    }
}