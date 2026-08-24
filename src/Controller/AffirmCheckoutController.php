<?php

namespace Concrete\Package\CommunityStoreAffirm\Controller;

use Concrete\Core\Controller\AbstractController;
use Concrete\Core\Http\Request;
use Concrete\Core\Routing\RedirectResponse;
use Concrete\Core\Support\Facade\Log;
use Concrete\Core\Support\Facade\Session;
use Concrete\Core\Support\Facade\Url;
use Concrete\Package\CommunityStore\Src\CommunityStore\Order\Order as StoreOrder;
use Concrete\Package\CommunityStore\Src\CommunityStore\Order\OrderStatus\OrderStatus as StoreOrderStatus;
use Concrete\Package\CommunityStoreAffirm\Affirm\Service\TransactionService;
use Concrete\Package\CommunityStoreAffirm\Affirm\Util\LanguagePath;

class AffirmCheckoutController extends AbstractController
{
    public function complete(TransactionService $transactionService): RedirectResponse
    {
        /** @var Request $request */
        $request = $this->app->make(Request::class);
        $checkoutToken = $request->request->get('checkout_token');
        $order = StoreOrder::getByID(Session::get('orderID'));

        if (!$checkoutToken || !$order) {
            Log::addError('Affirm payment failed: missing checkout token or order.');

            return $this->buildRedirect('/checkout/failed#payment');
        }

        $result = $transactionService->authorize((string) $checkoutToken, $order);
        if (!$result['success']) {
            if (!empty($result['error'])) {
                Log::addError('Affirm payment failed: ' . $result['error']);
            }

            return $this->buildRedirect('/checkout/failed#payment');
        }

        $order->completeOrder($result['transactionId']);
        $order->updateStatus(StoreOrderStatus::getStartingStatus()->getHandle());

        $langpath = LanguagePath::getCurrent();

        return $this->buildRedirect($langpath . '/checkout/complete');
    }

    protected function buildRedirect(string $path): RedirectResponse
    {
        return new RedirectResponse(Url::to($path));
    }
}
