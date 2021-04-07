<?php


namespace Inensus\CalinMeter\Console\Commands;
use Illuminate\Console\Command;
use Inensus\CalinMeter\Helpers\ApiHelpers;
use Inensus\CalinMeter\Services\CalinCredentialService;
use Inensus\CalinMeter\Services\MenuItemService;

class UpdatePackage extends Command
{
    protected $signature = 'calin-meter:update';
    protected $description = 'Update CalinMeter Package';

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
        $this->info('Calin Meter Integration Updating Started\n');
        $this->info('Removing former version of package\n');
        echo shell_exec('COMPOSER_MEMORY_LIMIT=-1 ../composer.phar  remove inensus/calin-meter');
        $this->info('Installing last version of package\n');
        echo shell_exec('COMPOSER_MEMORY_LIMIT=-1 ../composer.phar  require inensus/calin-meter');


        $this->info('Copying migrations\n');
        $this->call('vendor:publish', [
            '--provider' => "Inensus\CalinMeter\Providers\ServiceProvider",
            '--tag' => "migrations"
        ]);

        $this->info('Updating database tables\n');
        $this->call('migrate');



        $this->info('Copying vue files\n');
        $this->call('vendor:publish', [
            '--provider' => "Inensus\CalinMeter\Providers\ServiceProvider",
            '--tag' => "vue-components"
        ]);

        $this->call('routes:generate');

        $menuItems = $this->menuItemService->createMenuItems();
        $this->call('menu-items:generate', [
            'menuItem' => $menuItems['menuItem'],
            'subMenuItems' => $menuItems['subMenuItems'],
        ]);

        $this->call('sidebar:generate');

        $this->info('Package updated successfully..');
    }
}