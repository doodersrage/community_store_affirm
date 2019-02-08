<?php

namespace Concrete\Package\CommunityStoreAffirm\Src\CommunityStore\Payment\Methods\CommunityStoreAffirm;

use Concrete\Package\CommunityStore\Controller\SinglePage\Dashboard\Store;
use Core;
use Config;
use Exception;
use URL;
use Session;
use Log;
use Redirect;

use \Concrete\Package\CommunityStore\Src\CommunityStore\Payment\Method as StorePaymentMethod;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Utilities\Calculator as StoreCalculator;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Customer\Customer as StoreCustomer;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Shipping\Method\ShippingMethod as StoreShippingMethod;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Cart\Cart as StoreCart;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Product\ProductOption\ProductOption as StoreProductOption;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Product\ProductOption\ProductOptionItem as StoreProductOptionItem;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Order\Order as StoreOrder;
use \Concrete\Package\CommunityStore\Src\CommunityStore\Order\OrderStatus\OrderStatus as StoreOrderStatus;


class CommunityStoreAffirmPaymentMethod extends StorePaymentMethod
{

    public function dashboardForm()
    {
        $this->set('affirmMode', Config::get('community_store_affirm.mode'));
        $this->set('affirmPublicApiKey', Config::get('community_store_affirm.publicApiKey'));
        $this->set('affirmPrivateApiKey', Config::get('community_store_affirm.privateApiKey'));
        $this->set('affirmFinancialProductKey', Config::get('community_store_affirm.financialProductKey'));
        $this->set('form', Core::make("helper/form"));

    }

    public function save(array $data = [])
    {
        Config::save('community_store_affirm.mode', $data['affirmMode']);
        Config::save('community_store_affirm.publicApiKey', $data['affirmPublicApiKey']);
        Config::save('community_store_affirm.privateApiKey', $data['affirmPrivateApiKey']);
        Config::save('community_store_affirm.financialProductKey', $data['affirmFinancialProductKey']);
    }

    public function validate($args, $e)
    {
        return $e;
    }
    
    public function redirectForm()
    {
        // gather current domain and protocol
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $domainName = $_SERVER['HTTP_HOST'].'/';
                
        $customer = new StoreCustomer();

        $th = Core::make('helper/text');

        $total = number_format(StoreCalculator::getGrandTotal(), 2, '.', '');        
        $totals = StoreCalculator::getTotals();
        $subTotal = $totals['subTotal'];
        $tax = $totals['taxTotal'];
        $shippingTotal = $totals['shippingTotal'];

        $cart = StoreCart::getCart();
        
        $order = StoreOrder::getByID(Session::get('orderID'));
        
        // affirm JSON object gen
        $affJSON = [
                    'merchant' => [
                        'user_confirmation_url' => $protocol.$domainName.'checkout/affirmresponse',
                        'user_cancel_url' => $protocol.$domainName.'/checkout',
                        'user_confirmation_url_action' => 'POST',
                        'name' => 'Hearing Assist',
                    ],
                    'billing' => [
                        'name' => [
                            'first' => trim(h($customer->getValue('billing_first_name'))),
                            'last' => trim(h($customer->getValue('billing_last_name'))),
                        ],
                        'address' => [
                            'line1' => trim(h($customer->getAddressValue('billing_address', 'address1')) . ' ' . h($customer->getAddressValue('billing_address', 'address2'))),
                            'city' => trim(h($customer->getAddressValue('billing_address', 'city'))),
                            'state' => trim(h($customer->getAddressValue('billing_address', 'state_province'))),
                            'zipcode' => h($customer->getAddressValue('billing_address', 'postal_code')),
                            'country' => h($customer->getAddressValue('billing_address', 'country')),
                        ],
                    ],
                    'order_id' => $order->getOrderID(),
                    'shipping_amount' => $shippingTotal,
                    'tax_amount' => $tax,
                    'total' => ($total * 100),
                ];
        
        // add shipping address
        if ($shipping) {
            $affJSON['shipping'] = [
                        'name' => [
                            'first' => trim(h($customer->getValue('shipping_first_name'))),
                            'last' => trim(h($customer->getValue('shipping_last_name'))),
                        ],
                        'address' => [
                            'line1' => trim(h($customer->getAddressValue('shipping_address', 'address1')) . ' ' . h($customer->getAddressValue('billing_address', 'address2'))),
                            'city' => trim(h($customer->getAddressValue('shipping_address', 'city'))),
                            'state' => trim(h($customer->getAddressValue('shipping_address', 'state_province'))),
                            'zipcode' => h($customer->getAddressValue('shipping_address', 'postal_code')),
                            'country' => h($customer->getAddressValue('shipping_address', 'country')),
                        ],
                    ];
        } else {
            $affJSON['shipping'] = [
                        'name' => trim(h($customer->getValue('billing_first_name'))) . ' ' . trim(h($customer->getValue('billing_last_name'))),
                        'address' => [
                            'line1' => trim(h($customer->getAddressValue('billing_address', 'address1')) . ' ' . h($customer->getAddressValue('billing_address', 'address2'))),
                            'city' => trim(h($customer->getAddressValue('billing_address', 'city'))),
                            'state' => trim(h($customer->getAddressValue('billing_address', 'state_province'))),
                            'zipcode' => h($customer->getAddressValue('billing_address', 'postal_code')),
                            'country' => h($customer->getAddressValue('billing_address', 'country')),
                        ],
                    ];
        }
        
        // add products
        foreach ($cart as $k => $cartItem) {

            $qty = $cartItem['product']['qty'];
            $product = $cartItem['product']['object'];

            $productprice = $product->getActivePrice();

            if (!$productprice) {
                $productprice = (float)$cartItem['product']['customerPrice'];
            }
            
            $affJSON['items'][] = [
                'display_name' => $th->shortText(trim(h($product->getName())),31, ''),
                'sku' => $product->getSKU(),
                'unit_price' => ($productprice * 100),
                'qty' => $qty,
                'item_image_url' => '',
                'item_url' => '',
            ];
            
        }
        
        // send generaed checkout object to redirect form
        $this->set('affJSON',json_encode($affJSON));
    }
    
    public static function validateCompletion()
    {
        // read Affirm confirmation package
        $order = StoreOrder::getByID(Session::get('orderID'));
        $total = number_format(StoreCalculator::getGrandTotal(), 2, '.', '');        
        $totals = StoreCalculator::getTotals();
        $affirm_token = $_POST['checkout_token'];
        $mode = Config::get('community_store_affirm.mode');
        $script = ($mode ? 'https://sandbox.affirm.com' : 'https://api.affirm.com');
        $affirm_url = $script . '/api/v2/charges';
        $req = [
            'checkout_token' => $affirm_token,
            'order_id' => $order->getOrderID()
        ];
        $req = json_encode($req);
        
        $ch = curl_init($affirm_url);
        if ($ch == FALSE) {
            return FALSE;
        }
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
        curl_setopt($ch, CURLOPT_USERPWD, Config::get('community_store_affirm.publicApiKey').":".Config::get('community_store_affirm.privateApiKey'));
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
            'Content-Type: application/json',                                                                                
            'Content-Length: ' . strlen($req))                                                                       
        );  
        
        $res = curl_exec($ch);
        $json_res = json_decode($res, true);
        
        if($json_res['details']['total'] == ($total * 100)){
            $order->completeOrder($json_res['id']);
            $order->updateStatus(StoreOrderStatus::getStartingStatus()->getHandle());
            Redirect::to(URL::to('/checkout/complete'))->send();
        } else {
            // store auth token as error
            Log::addError("Invalid Affirm Payment: OTotal: " . ($total * 100) . " JSON_TOT: " . $json_res['details']['total'] . " " . $res);
        }

    }

    public function submitPayment()
    {        
        //nothing to do except return true
        return array('error'=>0, 'transactionReference'=>'');
    }

    public function getPaymentMethodName()
    {
        return 'Affirm';
    }

    public function getPaymentMethodDisplayName()
    {
        return $this->getPaymentMethodName();
    }
    
    public function isExternal() {
        return true;
    }

}

return __NAMESPACE__;