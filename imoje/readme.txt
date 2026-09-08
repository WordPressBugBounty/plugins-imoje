=== imoje ===
Contributors: imoje
Tags: imoje, woocommerce, payments, payment gateway, checkout
Tested up to: 7.1.0
Requires PHP: 5.6.0
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Stable tag: 4.16.1

Add payment via ING Pay (imoje) to WooCommerce

== Description ==
**Official ING Pay (imoje) payment plugin for WooCommerce**

Plugin adds usage of online payments via ING Pay (imoje) payment gateway to WooCommerce with following methods:

* ING Pay - redirects payer to ING Pay (imoje) paywall with every available payment method
* ING Pay BLIK - redirects payer to BLIK payment page
* ING Pay cards - redirects payer to ING Pay (imoje) paywall with card form (VISA, MASTERCARD, Google Pay, Apple Pay, VISA MOBILE)
* ING Pay electronic wallet - redirects payer to Apple Pay or Google Pay payment
* ING Pay PBL - redirects payer to fast online transfer of chosen bank
* ING Pay pay later - redirects payer to Twisto, PayPo, BLIK Paylater or PragmGO payment page
* ING Pay Visa Mobile - redirects payer to paywall with displayed VISA MOBILE payment method
* ING Pay installments - a widget on the checkout page that allows you to configure the number of instalments before proceeding to payment
* ING Pay wire transfer - redirects payer to ING Pay (imoje) paywall with data for wire transfer
* ING Pay Lease Now - allows payer to use ING Pay (imoje) Lease payment from product, cart or checkout page

Additional info about ING Pay can be found [here](https://www.ing.pl/bramka-platnicza-ing-pay).

Sandbox environment can be found [here](https://sandbox.pay.ing.pl/).

Technical documentation is available here:
[RESTful API](https://cdn.pay.ing.pl/documentation/pl/api/)
[FRONT API](https://cdn.pay.ing.pl/documentation/pl/paywall/)

Please send any questions or bug reports to us by e-mail or telephone.

Technical Support:
tech@pay.ing.pl
+48 32 319 35 70

Availability: Mon – Fri, between 9 AM and 4 PM.

== Installation ==

The module requires configuration in the ING Pay (imoje) administration panel.

* go to [pay.ing.pl](https://pay.ing.pl/) and log into the administration panel,
* go to the "Stores" > your store > "Details" > "Data for integration" tab, copy the configuration keys (merchant id, service id, service key and authorization token) and insert them into plugin configuration,
* select the currency activated in the ING Pay (imoje) service.

You do not need to specify a notification address in the ING Pay (imoje) panel – it will be sent automatically.

Detailed integration instruction for the module is available [here](https://www.ing.pl/bramka-platnicza-ing-pay/integracje)

== Frequently Asked Questions ==
= After choosing BLIK payment, the payer gets redirected to main shop site. How can I fix it? =
This situation usually occurs due to entering an incorrect authorization token or Service Key in the BLIK payment settings.

Token and service Key can be found in the ING Pay (imoje) panel "Stores" > store name > "Details" > "Data for integration".

= Payment method doesn't show up on checkout after configuration is done. =
This situation is usually caused by not choosing currency in the plugin configuration page. 

== Screenshots ==

1. Payment method channels available for configuration
2. Configuration window of chosen payment channel
3. Payment channels on checkout
4. Payment methods available on the paywall

== Changelog ==
= 4.16.1 =
* remove redundant calls to ING Pay API
* improve notifications - support for surcharges
* support for legacy endpoints
= 4.16.0 =
* updated the logos, text and instructions relating to the rebranding of imoje to ING Pay
* optimised the plugin’s internal mechanisms
* made improvements to the collection of order information required to issue an invoice in ING Księgowość
* added caching of payment methods
* added new logs
* for BLIK payments with a code, added a message informing the user that the transaction value limit has been exceeded
* enhanced the module’s security
* optimised the code and introduced an idempotent mechanism for refunds
= 4.15.3 =
* minor fixes 
= 4.15.2 =
* minor fixes
= 4.15.1 =
* revert ipn source amount to compare
= 4.15.0 =
* added optional support for notifications with "cancelled" status
* added the prefix imoje to functions related to Lease Now
= 4.14.0 =
* added Lease Now
= 4.13.0 =
* added wire transfer payment method
= 4.12.0 =
* Changes made in line with WordPress plugin guidelines
= 4.11.0 =
* added error logging to WooCommerce logs
* minor fixes
= 4.10.1 =
* modify response on checkout when something went wrong
= 4.10.0 =
* added support for basises of vat exemption in ING Księgowość invoices
* minor fixes 
= 4.9.1 =
* minor fixes
= 4.9.0 =
* added Electronic wallet payment method
* added option to update payment method in order details via notifiaction
* minor fixes
= 4.8.2 =
* fixed an issue with repayment of unpaid orders
= 4.8.1 =
* minor fixes for WooCommerce block checkout
= 4.8.0 =
* added support for WooCommerce block checkout
= 4.7.2 =
* minor fixes for imoje installments payment method
= 4.7.1 =
* minor fixes for imoje installments payment method
= 4.7.0 =
* added imoje installments payment method as widget
= 4.6.0 =
* added support for minimum and maximum transaction amounts for BLIK 0 level
* added full support for return to the shop button on the payment gateway
* fixed compatibility issue with WooCommerce Multilingual & Multicurrency module
* fixed compatibility issue with WooCommerce PayPal Payments module
= 4.5.0 =
* added missing Polish translation
* added functionality to automatically send notification address in HTTP requests
* BLIK channel logo automatically selects itself on the checkout in the shop 
= 4.4.0 =
* added full support for BLIK płacę później payment channel
= 4.3.0 =
* added support for tax exemption within ING Księgowość
= 4.2.1 =
* minor fixes 
= 4.2.0 =
* changed the way the contents of the shopping cart are removed 
= 4.1.1 =
* minor changes
= 4.1.0 =
* added VISA Mobile Payment channel
= 4.0.0 =
* the source code has been refactored 
* improved appearance and functionality of payment channels
* added PayPo and PragmaGO payment channels
= 3.3.3 =
* added aliases to function names
* minor changes
= 3.3.2 =
* minor fixes
= 3.3.1 =
* minor fixes of BLIK payment method
= 3.3.0 =
* added the ability to retrieve values for the company's VAT id based on the VAT id field meta name entered in the configuration
* added refund notifications support
= 3.2.8 =
* minor fixes of ING Lease Now
= 3.2.7 =
* added support for ING Lease Now
= 3.2.6 =
* minor fixes of ING Księgowość
= 3.2.5 =
* fixed an issue with some PBL redirections 
= 3.2.4 =
* fixed data sent in the invoice object for ING Księgowość
= 3.2.3 =
* fixed an issue related to case-sensitive folder names
= 3.2.2 =
* fixed automatic translation of payment method names for English and Polish versions
= 3.2.1 =
* fixed an issue preventing the completion of an order payment from the logged-in customer panel
= 3.2.0 =
* added support for ING Księgowość
* changed integration of the Apple Pay and Google Pay SDK 
= 3.1.1 =
* removed warning when redirecting to a payment gateway
= 3.1.0 =
* notice will not be logged if there is no tax
= 2.1.0 =
* added refund option
= 2.0.3 =
* added support for different currencies
= 1.3.2 =
* added separated card payment channel
* added Twisto's purchase history
= 1.3.0 =
* added separated BLIK payment channel 
* added separated Twisto payment channel
= 1.2.5 =
* added debug mode
= 1.0.1 =
* new Twisto payment method has been added
= 1.0.0 =
* added support for imoje Sandbox environment