<?php

namespace Concrete\Package\CommunityStoreAffirm\Affirm\Service;

use Concrete\Core\Support\Facade\Application;
use Concrete\Core\Support\Facade\Config as ConfigFacade;
use Concrete\Core\Support\Facade\Url;
use Concrete\Package\CommunityStore\Src\CommunityStore\Customer\Customer as StoreCustomer;
use Concrete\Package\CommunityStore\Src\CommunityStore\Order\Order as StoreOrder;
use Concrete\Package\CommunityStoreAffirm\Affirm\Config;
use Concrete\Package\CommunityStoreAffirm\Affirm\Util\LanguagePath;

class CheckoutPayloadBuilder
{
    public function build(StoreOrder $order, StoreCustomer $customer): array
    {
        $textHelper = Application::getFacadeApplication()->make('helper/text');
        $langpath = LanguagePath::getCurrent();

        $payload = [
            'merchant' => [
                'user_confirmation_url' => (string) Url::to($langpath . '/checkout/affirmresponse'),
                'user_cancel_url' => (string) Url::to($langpath . '/checkout'),
                'user_confirmation_url_action' => 'POST',
                'public_api_key' => (string) ConfigFacade::get(Config::PUBLIC_API_KEY),
                'name' => $this->getMerchantName(),
            ],
            'billing' => $this->buildAddress(
                $customer,
                'billing_address',
                $customer->getValue('billing_first_name'),
                $customer->getValue('billing_last_name'),
                $customer->getEmail(),
                $customer->getValue('billing_phone')
            ),
            'order_id' => (string) $order->getOrderID(),
            'shipping_amount' => $this->amountToCents($order->getShippingTotal()),
            'tax_amount' => $this->amountToCents($order->getTaxTotal()),
            'total' => $this->amountToCents($order->getTotal()),
            'currency' => 'USD',
            'items' => [],
        ];

        if ($order->isShippable()) {
            $payload['shipping'] = $this->buildAddress(
                $customer,
                'shipping_address',
                $customer->getValue('shipping_first_name'),
                $customer->getValue('shipping_last_name'),
                $customer->getEmail(),
                $customer->getValue('billing_phone')
            );
        } else {
            $payload['shipping'] = $payload['billing'];
        }

        $financialProductKey = trim((string) ConfigFacade::get(Config::FINANCIAL_PRODUCT_KEY));
        if ($financialProductKey !== '') {
            $payload['financing_program'] = $financialProductKey;
        }

        foreach ($order->getOrderItems() as $orderItem) {
            $payload['items'][] = [
                'display_name' => $textHelper->shortText(trim((string) $orderItem->getProductName()), 31, ''),
                'sku' => (string) $orderItem->getSKU(),
                'unit_price' => $this->amountToCents($orderItem->getPricePaid()),
                'qty' => (int) $orderItem->getQuantity(),
                'item_image_url' => '',
                'item_url' => '',
            ];
        }

        return $payload;
    }

    protected function buildAddress(
        StoreCustomer $customer,
        string $addressHandle,
        $firstName,
        $lastName,
        $email = null,
        $phone = null
    ): array {
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

    protected function getMerchantName(): string
    {
        $merchantName = trim((string) ConfigFacade::get(Config::MERCHANT_NAME));
        if ($merchantName !== '') {
            return $merchantName;
        }

        return (string) ConfigFacade::get('concrete.site');
    }

    protected function formatCountry($country): string
    {
        $country = strtoupper(trim((string) $country));

        if ($country === 'US') {
            return 'USA';
        }

        return $country;
    }

    protected function amountToCents($amount): int
    {
        return (int) round(((float) $amount) * 100);
    }
}
