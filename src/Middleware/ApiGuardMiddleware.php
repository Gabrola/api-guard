<?php namespace Chrisbjr\ApiGuard\Middleware;

use Closure;
use Illuminate\Http\Request;
use EllipseSynergie\ApiResponse\Laravel\Response;
use Illuminate\Contracts\Routing\Middleware;

class ApiGuardMiddleware implements Middleware
{
    /**
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        /*
         * We cannot access options like in filters, so we may either pass parameters through
         * route actions, or from config.
         */
        $action = $request->route()->getAction();

        return $next($request);
    }
}