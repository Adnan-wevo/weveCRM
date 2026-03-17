<?php

namespace Modules\CRM\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
	protected string $name = 'CRM';

	public function boot(): void
	{
		parent::boot();
	}

	public function map(): void
	{
		$this->mapApiRoutes();
		$this->mapWebRoutes();
	}

	protected function mapWebRoutes(): void
	{
		$centralDomain = config('tenancy.mode') === 'subdomain'
			? (config('tenancy.central_domains')[0] ?? null)
			: null;

		if ($centralDomain) {
			Route::domain($centralDomain)
				->middleware('web')
				->group(module_path($this->name, '/routes/web.php'));
		} else {
			Route::middleware('web')->group(module_path($this->name, '/routes/web.php'));
		}
	}

	protected function mapApiRoutes(): void
	{
		Route::middleware('api')->prefix('api')->name('api.')->group(module_path($this->name, '/routes/api.php'));
	}
}
