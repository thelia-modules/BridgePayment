# Bridge Payment

This module allows you to offer your customers a payment system via Bridge.

## Installation

### Manually

* Copy the module into ```<thelia_root>/local/modules/``` directory and be sure that the name of the module is BridgePayment.
* Activate it in your thelia administration panel

### Composer

Add it in your main thelia composer.json file

```
composer require thelia/bridge-payment-module:~1.0
```

## Usage

- Create an account here https://dashboard.bridgeapi.io/signin.
- Create your WebApp, then copy your client id and client secret into BridgePayment Thelia module configuration
- On your WebApp go to Webhooks > Add a webhook.
- On your webhook configuration in Callback URL enter `https://<your_domain>/bridge/notification` 
and check `payment.transaction.created` and `payment.transaction.updated`.
- Copy your webhook secret key in BridgePayment Thelia module configuration.
- Finish your configuration on Thelia by selecting a bank and enter your IBAN.

You can check Bridge documentation for help https://docs.bridgeapi.io/docs/quickstart

