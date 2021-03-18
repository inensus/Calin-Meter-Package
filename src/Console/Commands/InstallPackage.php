<?php

namespace Inensus\CalinMeter\Console\Commands;

use Illuminate\Console\Command;
use Inensus\CalinMeter\Helpers\ApiHelpers;
use Inensus\CalinMeter\Services\MenuItemService;
use Inensus\CalinMeter\Services\CalinCredentialService;

class InstallPackage extends Command
{
    protected $signature = 'calin-meter:install';
    protected $description = 'Install CalinMeter Package';

    private $menuItemService;
    private $apiHelpers;
    private $credentialService;
    public function __construct(
        MenuItemService $menuItemService,
        ApiHelpers $apiHelpers,
        CalinCredentialService $credentialService
    ) {
        parent::__construct();
        $this->menuItemService = $menuItemService;
        $this->apiHelpers = $apiHelpers;
        $this->credentialService = $credentialService;
    }

    public function handle(): void
    {
        $this->info('Installing CalinMeter Integration Package\n');

        $this->info('Copying migrations\n');
        $this->call('vendor:publish', [
            '--provider' => "Inensus\CalinMeter\Providers\ServiceProvider",
            '--tag' => "migrations"
        ]);

        $this->info('Creating database tables\n');
        $this->call('migrate');

        $this->info('Copying vue files\n');

        $this->call('vendor:publish', [
            '--provider' => "Inensus\CalinMeter\Providers\ServiceProvider",
            '--tag' => "vue-components"
        ]);
        $this->apiHelpers->registerCalinMeterManufacturer();
        $this->credentialService->createCredentials();

        $this->call('plugin:add', [
            'name' => "CalinMeter",
            'composer_name' => "inensus/calin-meter",
            'description' => "CalinMeter integration package for MicroPowerManager",
        ]);
        $this->call('routes:generate');

        $menuItems = $this->menuItemService->createMenuItems();
        $this->call('menu-items:generate', [
            'menuItem' => $menuItems['menuItem'],
            'subMenuItems' => $menuItems['subMenuItems'],
        ]);

        $this->call('sidebar:generate');

        $this->info('Package installed successfully..');
    }
}