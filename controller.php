<?php

namespace Concrete\Package\CommunityStoreAffirm;

use Concrete\Core\Package\Package;
use Concrete\Core\Routing\Router;
use Concrete\Package\CommunityStore\Src\CommunityStore\Payment\Method as PaymentMethod;
use Concrete\Package\CommunityStoreAffirm\EventListener\AffirmAssetsListener;
use Concrete\Package\CommunityStoreAffirm\Routing\RouteList;
use Whoops\Exception\ErrorException;

class Controller extends Package
{
    protected $pkgHandle = 'community_store_affirm';
    protected $appVersionRequired = '9.0';
    protected $pkgVersion = '2.1.0';
    protected $packageDependencies = ['community_store' => '2.0'];

    protected $pkgAutoloaderRegistries = [
        'src/CommunityStore' => '\Concrete\Package\CommunityStoreAffirm\Src\CommunityStore',
        'src/Controller' => '\Concrete\Package\CommunityStoreAffirm\Controller',
        'src/Affirm' => '\Concrete\Package\CommunityStoreAffirm\Affirm',
        'src/EventListener' => '\Concrete\Package\CommunityStoreAffirm\EventListener',
        'src/Routing' => '\Concrete\Package\CommunityStoreAffirm\Routing',
    ];

    public function getPackageDescription()
    {
        return t('Affirm Payment Method for Community Store');
    }

    public function getPackageName()
    {
        return t('Affirm Payment Method');
    }

    public function install()
    {
        $installed = $this->app->make('Concrete\Core\Package\PackageService')->getInstalledHandles();

        if (!(is_array($installed) && in_array('community_store', $installed))) {
            throw new ErrorException(t('This package requires that Community Store be installed'));
        }

        $pkg = parent::install();
        $pm = new PaymentMethod();
        $pm->add('community_store_affirm', 'Affirm', $pkg);
    }

    public function on_start()
    {
        $this->registerRoutes();
        $this->registerEventListeners();
    }

    public function uninstall()
    {
        $pm = PaymentMethod::getByHandle('community_store_affirm');
        if ($pm) {
            $pm->delete();
        }

        parent::uninstall();
    }

    protected function registerRoutes(): void
    {
        /** @var Router $router */
        $router = $this->app->make(Router::class);
        $routeList = new RouteList();
        $routeList->loadRoutes($router);
    }

    protected function registerEventListeners(): void
    {
        $this->app->make('director')->addSubscriber(
            $this->app->make(AffirmAssetsListener::class)
        );
    }
}
