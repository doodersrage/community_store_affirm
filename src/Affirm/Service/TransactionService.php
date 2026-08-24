<?php

namespace Concrete\Package\CommunityStoreAffirm\Affirm\Service;

use Concrete\Core\Support\Facade\Config as ConfigFacade;
use Concrete\Core\Support\Facade\Log;
use Concrete\Package\CommunityStore\Src\CommunityStore\Order\Order as StoreOrder;
use Concrete\Package\CommunityStoreAffirm\Affirm\Config;

class TransactionService
{
    /**
     * @return array{success: bool, transactionId: ?string, error: ?string}
     */
    public function authorize(string $checkoutToken, StoreOrder $order): array
    {
        $testMode = (bool) ConfigFacade::get(Config::MODE);
        $apiBase = $testMode ? Config::SANDBOX_API_BASE : Config::LIVE_API_BASE;
        $affirmUrl = $apiBase . '/api/v1/transactions';

        $payload = json_encode([
            'transaction_id' => $checkoutToken,
            'order_id' => (string) $order->getOrderID(),
        ]);

        $ch = curl_init($affirmUrl);
        if ($ch === false) {
            return [
                'success' => false,
                'transactionId' => null,
                'error' => 'Unable to initialize cURL.',
            ];
        }

        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
        curl_setopt($ch, CURLOPT_USERPWD, ConfigFacade::get(Config::PUBLIC_API_KEY) . ':' . ConfigFacade::get(Config::PRIVATE_API_KEY));
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
            return [
                'success' => false,
                'transactionId' => null,
                'error' => $curlError,
            ];
        }

        $json = json_decode((string) $response, true);
        if ($httpCode < 200 || $httpCode >= 300 || !is_array($json)) {
            Log::addError('Affirm payment authorization failed: ' . $response);

            return [
                'success' => false,
                'transactionId' => null,
                'error' => 'Affirm authorization request failed.',
            ];
        }

        $authorizedAmount = isset($json['amount']) ? (int) $json['amount'] : null;
        $expectedAmount = (int) round(((float) $order->getTotal()) * 100);

        if ($authorizedAmount !== $expectedAmount) {
            Log::addError(
                'Invalid Affirm payment amount. Expected ' . $expectedAmount . ' cents, received ' . $authorizedAmount . '. Response: ' . $response
            );

            return [
                'success' => false,
                'transactionId' => null,
                'error' => 'Authorized amount did not match order total.',
            ];
        }

        return [
            'success' => true,
            'transactionId' => $json['id'] ?? $checkoutToken,
            'error' => null,
        ];
    }
}
