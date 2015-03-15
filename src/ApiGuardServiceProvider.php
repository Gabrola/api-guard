<?php
namespace Chrisbjr\ApiGuard;

use Illuminate\Support\ServiceProvider;
use Carbon\Carbon;

class ApiGuardServiceProvider extends ServiceProvider
{
	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->publishes([
			__DIR__ . '/../config/' => config_path(),

			__DIR__ . '/../migrations/2014_06_12_084423_create_api_keys_table.php' =>
				base_path('database/migrations/' . Carbon::now()->format('Y_m_d_His').'_create_api_keys_table.php')
		], 'apiguard');

        require_once __DIR__ . '/routes.php';
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->register('EllipseSynergie\ApiResponse\Laravel\ResponseServiceProvider');
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array();
	}

}
