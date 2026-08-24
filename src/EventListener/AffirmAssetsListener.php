<?php

namespace Concrete\Package\CommunityStoreAffirm\EventListener;

use Concrete\Core\Support\Facade\Config as ConfigFacade;
use Concrete\Core\View\View;
use Concrete\Package\CommunityStoreAffirm\Affirm\Config;
use Concrete\Package\CommunityStoreAffirm\Affirm\Service\AffirmScriptRenderer;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

class AffirmAssetsListener implements EventSubscriberInterface
{
    /**
     * @var AffirmScriptRenderer
     */
    protected $scriptRenderer;

    public function __construct(AffirmScriptRenderer $scriptRenderer)
    {
        $this->scriptRenderer = $scriptRenderer;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'on_header_required_ready' => 'registerAffirmScript',
        ];
    }

    public function registerAffirmScript(GenericEvent $event): void
    {
        if ((string) ConfigFacade::get(Config::PUBLIC_API_KEY) === '') {
            return;
        }

        $script = $this->scriptRenderer->render();
        if ($script === '') {
            return;
        }

        View::getInstance()->addHeaderItem($script);
    }
}
