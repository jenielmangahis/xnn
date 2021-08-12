<?php


namespace Commissions\Providers;

use Commissions\Clients\IPayout;
use Commissions\Clients\PayQuicker as PayQuickerClient;
use Commissions\Contracts\PaymentInterface;
use Commissions\Contracts\Repositories\CountryRepositoryInterface;
use Commissions\Payments\PayQuicker;
use Commissions\Repositories\CountryRepository;
use Hyperwallet\Hyperwallet;
use Illuminate\Support\ServiceProvider;

class CommissionServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerPayQuickerClient();
        $this->registerHyperwalletClient();
        $this->registerIPayoutClient();

        $this->registerPaymentService();

        $this->app->bind(CountryRepositoryInterface::class, function ($app) {

            return new CountryRepository();

        });
    }

    protected function registerPaymentService()
    {
        $this->app->bind(PaymentInterface::class, function ($app) {

            switch (config('commission.payment')) {
                case 'payquicker':
                    return new PayQuicker($app->make(\Commissions\Clients\PayQuicker::class));
                case 'hyperwallet':
                    return new \Commissions\Payments\Hyperwallet($app->make(Hyperwallet::class));
                case 'ipayout':
                    return new \Commissions\Payments\IPayout($app->make(IPayout::class));
                default:
                    throw new \Exception("Payment not found");
            }

        });
    }

    protected function registerPayQuickerClient()
    {
        $this->app->bind(\Commissions\Clients\PayQuicker::class, function ($app) {

            return new PayQuickerClient(
                config('services.payquicker.client-id'),
                config('services.payquicker.client-secret'),
                config('services.payquicker.funding-account-public-id'),
                config('services.payquicker.tenant-login-uri')
            );

        });
    }

    protected function registerHyperwalletClient()
    {
        $this->app->bind(Hyperwallet::class, function ($app) {

            return new Hyperwallet(
                config('services.hyperwallet.username'),
                config('services.hyperwallet.password'),
                config('services.hyperwallet.program_token'),
                config('services.hyperwallet.server')
            );

        });
    }

    protected function registerIPayoutClient()
    {
        $this->app->bind(IPayout::class, function ($app) {

            return new IPayout(
                config('services.ipayout.merchant_guid'),
                config('services.ipayout.merchant_password'),
                config('services.ipayout.api_url')
            );

        });
    }
}
