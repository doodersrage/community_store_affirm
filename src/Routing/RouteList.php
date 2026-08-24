<?php

namespace Concrete\Package\CommunityStoreAffirm\Routing;

use Concrete\Core\Routing\RouteListInterface;
use Concrete\Core\Routing\Router;
use Concrete\Package\CommunityStoreAffirm\Controller\AffirmCheckoutController;

class RouteList implements RouteListInterface
{
    public function loadRoutes(Router $router): void
    {
        $router->post(
            '/checkout/affirmresponse',
            AffirmCheckoutController::class . '::complete'
        );
    }
}
