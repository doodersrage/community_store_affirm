<?php

namespace Concrete\Package\CommunityStoreAffirm\Affirm;

final class Config
{
    public const NAMESPACE = 'community_store_affirm';

    public const MODE = self::NAMESPACE . '.mode';
    public const PUBLIC_API_KEY = self::NAMESPACE . '.publicApiKey';
    public const PRIVATE_API_KEY = self::NAMESPACE . '.privateApiKey';
    public const FINANCIAL_PRODUCT_KEY = self::NAMESPACE . '.financialProductKey';
    public const MERCHANT_NAME = self::NAMESPACE . '.merchantName';

    public const SANDBOX_SCRIPT_BASE = 'https://cdn1-sandbox.affirm.com';
    public const LIVE_SCRIPT_BASE = 'https://cdn1.affirm.com';
    public const SANDBOX_API_BASE = 'https://sandbox.affirm.com';
    public const LIVE_API_BASE = 'https://api.affirm.com';

    private function __construct()
    {
    }
}
