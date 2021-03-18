<?php
namespace Inensus\CalinMeter\Providers;

use App\Models\MainSettings;
use App\Models\Manufacturer;
use App\Models\Meter\MeterParameter;
use App\Models\Transaction\Transaction;
use GuzzleHttp\Client;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use Inensus\CalinMeter\CalinMeterApi;
use Inensus\CalinMeter\Console\Commands\InstallPackage;
use Inensus\CalinMeter\Helpers\ApiHelpers;
use Inensus\CalinMeter\Http\Requests\CalinMeterApiRequests;
use Inensus\CalinMeter\Models\CalinCredential;
use Inensus\CalinMeter\Models\CalinTransaction;



class CalinMeterServiceProvider extends ServiceProvider
{
    public function boot(Filesystem $filesystem)
    {
        $this->app->register(RouteServiceProvider::class);
        if ($this->app->runningInConsole()) {
            $this->publishConfigFiles();
            $this->publishVueFiles();
            $this->publishMigrations($filesystem);
            $this->commands([InstallPackage::class]);
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/calin-meter.php', 'calin-meter');
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
        $this->app->bind('CalinMeterApi', function () {
            $client = new Client();
            $meterParameter = new MeterParameter();
            $transaction = new Transaction();
            $calinTransaction = new CalinTransaction();
            $mainSettings = new MainSettings();
            $calinCredential = new CalinCredential();
            $manufacturer = new Manufacturer();
            $apiHelpers = new ApiHelpers($manufacturer);
            $apiRequests = new CalinMeterApiRequests($client, $apiHelpers, $calinCredential);
            return new CalinMeterApi($client, $meterParameter, $calinTransaction, $transaction, $mainSettings,
                $calinCredential, $apiRequests, $apiHelpers);
        });
    }

    public function publishConfigFiles()
    {
        $this->publishes([
            __DIR__ . '/../../config/calin-meter.php' => config_path('calin-meter.php'),
        ]);
    }

    public function publishVueFiles()
    {
        $this->publishes([
            __DIR__ . '/../resources/assets' => resource_path('assets/js/plugins/calin-meter'),
        ], 'vue-components');
    }

    public function publishMigrations($filesystem)
    {
        $this->publishes([
            __DIR__ . '/../../database/migrations/create_calin_tables.php.stub'
            => $this->getMigrationFileName($filesystem),
        ], 'migrations');
    }

    protected function getMigrationFileName(Filesystem $filesystem): string
    {
        $timestamp = date('Y_m_d_His');
        return Collection::make($this->app->databasePath() . DIRECTORY_SEPARATOR . 'migrations' . DIRECTORY_SEPARATOR)
            ->flatMap(function ($path) use ($filesystem) {
                return $filesystem->glob($path . '*_create_calin_tables.php');
            })->push($this->app->databasePath() . "/migrations/{$timestamp}_create_calin_tables.php")
            ->first();
    }
}