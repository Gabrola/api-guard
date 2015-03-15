<?php
namespace Chrisbjr\ApiGuard\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Route;
use EllipseSynergie\ApiResponse\Laravel\Response;
use League\Fractal\Manager;

class ApiGuardController extends Controller
{
    /**
     * @var array
     */
    protected $apiMethods;

    /**
     * @var \EllipseSynergie\ApiResponse\Laravel\Response
     */
    public $response;

    /**
     * @var \League\Fractal\Manager
     */
    public $manager;

    public function __construct()
    {
        $this->middleware('Chrisbjr\ApiGuard\Middleware\ApiGuardMiddleware');

        $this->beforeFilter(function() {
            $route = Route::getCurrentRoute();
            $action = $route->getAction();

            $beforeFilters = $this->getBeforeFilters();
            $apiMethods = array();
            foreach ($beforeFilters as $filter) {
                if (!empty($filter['options']['apiMethods'])) {
                    $apiMethods = $filter['options']['apiMethods'];
                }
            }

            $action['apiMethods'] = $apiMethods;

            $route->setAction($action);
        }, ['apiMethods' => $this->apiMethods]);

        $this->manager = new Manager;
        $this->manager->parseIncludes(Input::get(Config::get('api-guard.includeKeyword', 'include'), array()));
        $this->response = new Response($this->manager);
    }
}
