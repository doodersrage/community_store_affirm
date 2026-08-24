# Community Store Affirm Payment Method

Affirm payment integration for [Community Store](https://github.com/concretecms-community-store/community_store) on [Concrete CMS](https://www.concretecms.org/) 9.

## Requirements

- Concrete CMS 9.x
- Community Store 2.0+
- An [Affirm merchant account](https://www.affirm.com/)

Install Community Store before installing this package.

## Installation

1. Download or clone this repository into your Concrete CMS `packages` directory as `community_store_affirm`.
2. Install the package from **Dashboard → Extend Concrete**.

## Configuration

Configure the payment method from **Dashboard → Store → Settings → Payment Methods → Affirm**:

- **Mode** — Live or Test (sandbox)
- **Public API Key** — From your Affirm merchant dashboard
- **Private API Key** — From your Affirm merchant dashboard
- **Merchant Name** — Optional; shown to customers during Affirm checkout
- **Financial Product Key** — Optional financing program key

## Affirm API

This package uses Affirm's current **v1 Transactions API** for authorization after checkout, and the **Affirm.js v2** library for the checkout modal.

- [Affirm Direct API documentation](https://docs.affirm.com/payments/docs/transactions-web)
- [Promotional messaging](https://docs.affirm.com/Integrate_Affirm/Promotional_Messaging)

The Affirm.js library is loaded site-wide when the package is installed, which enables promotional messaging on product and cart pages.

## Architecture

This package follows Concrete CMS 9 conventions:

- **RouteList** — routes are registered through `src/Routing/RouteList.php`
- **Controller** — checkout callbacks are handled by `AffirmCheckoutController`
- **Services** — Affirm API and checkout payload logic live under `src/Affirm/Service/`
- **Event listener** — Affirm.js is injected on `on_header_required_ready`

## Changelog

### 2.1.0

- Refactored to Concrete CMS 9 design patterns (RouteList, controllers, services, event listeners)
- Replaced legacy global header injection with `on_header_required_ready` event subscriber
- Moved Affirm authorization callback out of the payment method into a dedicated controller

### 2.0.0

- Updated for Concrete CMS 9 and Community Store 2.x
- Migrated from deprecated Affirm v2/charges API to v1/transactions API
- Fixed shipping address handling and order total validation
- Added merchant name configuration
- Improved error handling on failed authorizations
