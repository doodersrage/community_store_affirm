<?php

namespace Concrete\Package\CommunityStoreAffirm\Src\CommunityStore\Payment\Methods\CommunityStoreAffirm;

use Concrete\Core\Multilingual\Page\Section\Section;
use Concrete\Core\Support\Facade\Config;
use Concrete\Core\Support\Facade\Log;
use Concrete\Core\Support\Facade\Session;
use Concrete\Core\Routing\Redirect;
use Concrete\Core\Routing\RedirectResponse;
use URL;
use Concrete\Package\CommunityStore\Src\CommunityStore\Customer\Customer as StoreCustomer;
use Concrete\Package\CommunityStore\Src\CommunityStore\Order\Order as StoreOrder;
use Concrete\Package\CommunityStore\Src\CommunityStore\Order\OrderStatus\OrderStatus as StoreOrderStatus;
use Concrete\Package\CommunityStore\Src\CommunityStore\Payment\Method as StorePaymentMethod;

class CommunityStoreAffirmPaymentMethod extends StorePaymentMethod
{
    public function dashboardForm()
    {
        $this->set('affirmMode', Config::get('community_store_affirm.mode'));
        $this->set('affirmPublicApiKey', Config::get('community_store_affirm.publicApiKey'));
        $this->set('affirmPrivateApiKey', Config::get('community_store_affirm.privateApiKey'));
        $this->set('affirmFinancialProductKey', Config::get('community_store_affirm.financialProductKey'));
        $this->set('affirmMerchantName', Config::get('community_store_affirm.merchantName'));
        $this->set('form', app('helper/form'));
    }

    public function save(array $data = [])
    {
        Config::save('community_store_affirm.mode', !empty($data['affirmMode']));
        Config::save('community_store_affirm.publicApiKey', $data['affirmPublicApiKey'] ?? '');
        Config::save('community_store_affirm.privateApiKey', $data['affirmPrivateApiKey'] ?? '');
        Config::save('community_store_affirm.financialProductKey', $data['affirmFinancialProductKey'] ?? '');
        Config::save('community_store_affirm.merchantName', $data['affirmMerchantName'] ?? '');
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
            return Redirect::to('/checkout/failed#payment')->send();
        }

        $customer = new StoreCustomer();
        $th = app('helper/text');
        $langpath = $this->getLanguagePath();

        $totalCents = $this->amountToCents($order->getTotal());
        $shippingCents = $this->amountToCents($order->getShippingTotal());
        $taxCents = $this->amountToCents($order->getTaxTotal());

        $affJSON = [
            'merchant' => [
                'user_confirmation_url' => (string) URL::to($langpath . '/checkout/affirmresponse'),
                'user_cancel_url' => (string) URL::to($langpath . '/checkout'),
                'user_confirmation_url_action' => 'POST',
                'public_api_key' => (string) Config::get('community_store_affirm.publicApiKey'),
                'name' => $this->getMerchantName(),
            ],
            'billing' => $this->buildAffirmAddress(
                $customer->getValue('billing_first_name'),
                $customer->getValue('billing_last_name'),
                'billing_address',
                $customer,
                $customer->getEmail(),
                $customer->getValue('billing_phone')
            ),
            'order_id' => (string) $order->getOrderID(),
            'shipping_amount' => $shippingCents,
            'tax_amount' => $taxCents,
            'total' => $totalCents,
            'currency' => 'USD',
            'items' => [],
        ];

        if ($order->isShippable()) {
            $affJSON['shipping'] = $this->buildAffirmAddress(
                $customer->getValue('shipping_first_name'),
                $customer->getValue('shipping_last_name'),
                'shipping_address',
                $customer,
                $customer->getEmail(),
                $customer->getValue('billing_phone')
            );
        } else {
            $affJSON['shipping'] = $affJSON['billing'];
        }

        $financialProductKey = trim((string) Config::get('community_store_affirm.financialProductKey'));
        if ($financialProductKey !== '') {
            $affJSON['financing_program'] = $financialProductKey;
        }

        foreach ($order->getOrderItems() as $orderItem) {
            $affJSON['items'][] = [
                'display_name' => $th->shortText(trim((string) $orderItem->getProductName()), 31, ''),
                'sku' => (string) $orderItem->getSKU(),
                'unit_price' => $this->amountToCents($orderItem->getPricePaid()),
                'qty' => (int) $orderItem->getQuantity(),
                'item_image_url' => '',
                'item_url' => '',
            ];
        }

        $this->set('affJSON', json_encode($affJSON, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT));
    }

    public static function validateCompletion()
    {
        $checkoutToken = $_POST['checkout_token'] ?? null;
        $order = StoreOrder::getByID(Session::get('orderID'));

        if (!$checkoutToken || !$order) {
            Log::addError('Affirm payment failed: missing checkout token or order.');

            return self::redirectTo('/checkout/failed#payment');
        }

        $mode = (bool) Config::get('community_store_affirm.mode');
        $apiBase = $mode ? 'https://sandbox.affirm.com' : 'https://api.affirm.com';
        $affirmUrl = $apiBase . '/api/v1/transactions';

        $payload = json_encode([
            'transaction_id' => $checkoutToken,
            'order_id' => (string) $order->getOrderID(),
        ]);

        $ch = curl_init($affirmUrl);
        if ($ch === false) {
            Log::addError('Affirm payment failed: unable to initialize cURL.');

            return self::redirectTo('/checkout/failed#payment');
        }

        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
        curl_setopt($ch, CURLOPT_USERPWD, Config::get('community_store_affirm.publicApiKey') . ':' . Config::get('community_store_affirm.privateApiKey'));
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($payload),
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            Log::addError('Affirm payment failed: ' . $curlError);

            return self::redirectTo('/checkout/failed#payment');
        }

        $json = json_decode($response, true);
        if ($httpCode < 200 || $httpCode >= 300 || !is_array($json)) {
            Log::addError('Affirm payment authorization failed: ' . $response);

            return self::redirectTo('/checkout/failed#payment');
        }

        $authorizedAmount = isset($json['amount']) ? (int) $json['amount'] : null;
        $expectedAmount = self::amountToCentsStatic($order->getTotal());

        if ($authorizedAmount !== $expectedAmount) {
            Log::addError(
                'Invalid Affirm payment amount. Expected ' . $expectedAmount . ' cents, received ' . $authorizedAmount . '. Response: ' . $response
            );

            return self::redirectTo('/checkout/failed#payment');
        }

        $transactionId = $json['id'] ?? $checkoutToken;
        $order->completeOrder($transactionId);
        $order->updateStatus(StoreOrderStatus::getStartingStatus()->getHandle());

        $langpath = self::getLanguagePathStatic();

        return self::redirectTo($langpath . '/checkout/complete');
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

    protected function getMerchantName()
    {
        $merchantName = trim((string) Config::get('community_store_affirm.merchantName'));
        if ($merchantName !== '') {
            return $merchantName;
        }

        return (string) Config::get('concrete.site');
    }

    protected function buildAffirmAddress($firstName, $lastName, $addressHandle, StoreCustomer $customer, $email = null, $phone = null)
    {
        $address = [
            'name' => [
                'first' => trim((string) $firstName),
                'last' => trim((string) $lastName),
            ],
            'address' => [
                'line1' => trim($customer->getAddressValue($addressHandle, 'address1')),
                'line2' => trim($customer->getAddressValue($addressHandle, 'address2')),
                'city' => trim($customer->getAddressValue($addressHandle, 'city')),
                'state' => trim($customer->getAddressValue($addressHandle, 'state_province')),
                'zipcode' => (string) $customer->getAddressValue($addressHandle, 'postal_code'),
                'country' => $this->formatCountry($customer->getAddressValue($addressHandle, 'country')),
            ],
        ];

        if ($email) {
            $address['email'] = (string) $email;
        }

        if ($phone) {
            $address['phone_number'] = preg_replace('/\D+/', '', (string) $phone);
        }

        return $address;
    }

    protected function formatCountry($country)
    {
        $country = strtoupper(trim((string) $country));

        if ($country === 'US') {
            return 'USA';
        }

        return $country;
    }

    protected function amountToCents($amount)
    {
        return self::amountToCentsStatic($amount);
    }

    protected static function amountToCentsStatic($amount)
    {
        return (int) round(((float) $amount) * 100);
    }

    protected function getLanguagePath()
    {
        return self::getLanguagePathStatic();
    }

    protected static function getLanguagePathStatic()
    {
        $c = \Concrete\Core\Page\Page::getCurrentPage();
        if (!$c) {
            return '';
        }

        $section = Section::getBySectionOfSite($c);
        if ($section !== null) {
            return $section->getCollectionHandle();
        }

        return '';
    }

    protected static function redirectTo($path)
    {
        $response = new RedirectResponse(URL::to($path));

        return $response->send();
    }
}
