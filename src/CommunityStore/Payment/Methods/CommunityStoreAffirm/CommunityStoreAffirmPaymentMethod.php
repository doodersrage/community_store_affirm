<?php

namespace Concrete\Package\CommunityStoreAffirm\Src\CommunityStore\Payment\Methods\CommunityStoreAffirm;

use Concrete\Core\Routing\Redirect;
use Concrete\Core\Support\Facade\Application;
use Concrete\Core\Support\Facade\Config as ConfigFacade;
use Concrete\Core\Support\Facade\Session;
use Concrete\Package\CommunityStore\Src\CommunityStore\Customer\Customer as StoreCustomer;
use Concrete\Package\CommunityStore\Src\CommunityStore\Order\Order as StoreOrder;
use Concrete\Package\CommunityStore\Src\CommunityStore\Payment\Method as StorePaymentMethod;
use Concrete\Package\CommunityStoreAffirm\Affirm\Config;
use Concrete\Package\CommunityStoreAffirm\Affirm\Service\CheckoutPayloadBuilder;

class CommunityStoreAffirmPaymentMethod extends StorePaymentMethod
{
    public function dashboardForm()
    {
        $app = Application::getFacadeApplication();

        $this->set('affirmMode', ConfigFacade::get(Config::MODE));
        $this->set('affirmPublicApiKey', ConfigFacade::get(Config::PUBLIC_API_KEY));
        $this->set('affirmPrivateApiKey', ConfigFacade::get(Config::PRIVATE_API_KEY));
        $this->set('affirmFinancialProductKey', ConfigFacade::get(Config::FINANCIAL_PRODUCT_KEY));
        $this->set('affirmMerchantName', ConfigFacade::get(Config::MERCHANT_NAME));
        $this->set('form', $app->make('helper/form'));
    }

    public function save(array $data = [])
    {
        ConfigFacade::save(Config::MODE, !empty($data['affirmMode']));
        ConfigFacade::save(Config::PUBLIC_API_KEY, $data['affirmPublicApiKey'] ?? '');
        ConfigFacade::save(Config::PRIVATE_API_KEY, $data['affirmPrivateApiKey'] ?? '');
        ConfigFacade::save(Config::FINANCIAL_PRODUCT_KEY, $data['affirmFinancialProductKey'] ?? '');
        ConfigFacade::save(Config::MERCHANT_NAME, $data['affirmMerchantName'] ?? '');
    }

    public function validate($args, $e)
    {
        $pm = StorePaymentMethod::getByHandle('community_store_affirm');
        if ($pm && !empty($args['paymentMethodEnabled'][$pm->getID()])) {
            if (empty($args['affirmPublicApiKey'])) {
                $e->add(t('Affirm Public API Key must be set'));
            }
            if (empty($args['affirmPrivateApiKey'])) {
                $e->add(t('Affirm Private API Key must be set'));
            }
        }

        return $e;
    }

    public function redirectForm()
    {
        $order = StoreOrder::getByID(Session::get('orderID'));
        if (!$order) {
            Redirect::to('/checkout/failed#payment')->send();
            exit;
        }

        $app = Application::getFacadeApplication();
        /** @var CheckoutPayloadBuilder $payloadBuilder */
        $payloadBuilder = $app->make(CheckoutPayloadBuilder::class);
        $payload = $payloadBuilder->build($order, new StoreCustomer());

        $this->set(
            'affJSON',
            json_encode($payload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)
        );
    }

    public function submitPayment()
    {
        return ['error' => 0, 'transactionReference' => ''];
    }

    public function getName()
    {
        return t('Affirm');
    }

    public function isExternal()
    {
        return true;
    }
}
