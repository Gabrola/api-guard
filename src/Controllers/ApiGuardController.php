<?php namespace Chrisbjr\ApiGuard\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Input;
use EllipseSynergie\ApiResponse\Laravel\Response;
use League\Fractal\Manager;

class ApiGuardController extends Controller
{
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

        $this->manager = new Manager;
        $this->manager->parseIncludes(Input::get(Config::get('apiguard.includeKeyword', 'include'), array()));
        $this->response = new Response($this->manager);
    }
}
