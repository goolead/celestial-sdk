<?php

namespace Celestial\Laravel;

use Celestial\Api\ApiProvider;
use Celestial\Contracts\Services\Billing\BillingServiceContract;
use Celestial\Contracts\Services\Payments\PaymentsServiceContract;
use Celestial\Services\Billing\BillingService;
use Celestial\Services\Payments\PaymentsService;
use Illuminate\Support\ServiceProvider;

class CelestialServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->registerBillingService();
        $this->registerPaymentsService();
    }

    protected function registerBillingService()
    {
        $this->app->singleton(BillingService::class, function ($app) {
            $url = $app['config']->get('celestial.billing.url');
            $token = $app['config']->get('celestial.billing.token');

            $apiProvider = new ApiProvider($url, $token);

            return new BillingService($apiProvider);
        });

        $this->app->bind(BillingServiceContract::class, BillingService::class);
    }

    protected function registerPaymentsService()
    {
        $this->app->singleton(PaymentsService::class, function ($app) {
            $url = $app['config']->get('celestial.payments.url');
            $token = $app['config']->get('celestial.payments.token');

            $apiProvider = new ApiProvider($url, $token);

            return new PaymentsService($apiProvider);
        });

        $this->app->bind(PaymentsServiceContract::class, PaymentsService::class);
    }
}
