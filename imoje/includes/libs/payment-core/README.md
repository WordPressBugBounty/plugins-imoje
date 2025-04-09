# Payment Core Library

It is a simple PHP library that allow implement payment gateway methods out of the box.

## Requirements
* [PHP](http://php.net/) needs to be a minimum version of 5.4.0
* [cURL](http://php.net/manual/en/book.curl.php) needs to be enabled

### Installation 
1. `git clone`
2. `composer install`

## How to use:
Library provides few already created methods like generate form order or just generate form fields.

If you want to generate full form:
1. First you need to initialize Payment instance and provide environment, service key and service id. The last parameter `language` is optional, if if won't be provided the default value will be used (which is 'en')
2. Then simply run `$payment->buildOrderForm($orderData)` to build full form or `$payment->buildFormFields($orderData)` to build form fields.

## Examples:

##### Build full form:

Order data must have fields:
`amount, currency, orderId, customerFirstName, customerLastName`

Optional data:
`customerEmail, urlSuccess, urlFailure, urlReturn, urlNotification, cartData, version`
                 
Example `$orderData`:

```php
$orderData = [
    'amount' => (int) (1.33 * 100), //INT, price should be multipled by 100 so for example 1.33 will be 133 
    'currency' => 'PLN', // see suported currences in 'Supported variables' sectiom
    'orderId' => '123456790',
    'customerFirstName' => 'John',
    'customerLastName' => 'Doe',
    'customerEmail' => 'john@doe.com',
    'urlSuccess' => 'https://example.com/success.php',
    'urlFailure' => 'https://example.com/failure.php',
    'urlReturn' => 'https://example.com/return.php',
];
```

```php
use Imoje\Payment\Paywall;

public function yourFunction()
{
    // ...
    
    $payment = new Paywall(\Imoje\Payment\Util::ENVIRONMENT_PRODUCTION, 'b1c8bd6c7411455aab3295c48fcf26a7', 'e692d208484b4d5887755362d0587a00', 'pl');
    
    $form = $payment->buildOrderForm($orderData); // You can also pass second parameter `language` to generate form that will redirect to this language
}
```

The `$form` must contain $orderData and additional keys (signature must have which one hash method was use (for example sha256)), like:

`serviceId, merchantId, signature`

```php
<input type="hidden" value="133" name="serviceId">
<input type="hidden" value="133" name="merchantId">
<input type="hidden" value="133" name="amount">
<input type="hidden" value="PLN" name="currency">
<input type="hidden" value="123456790" name="orderId">
<input type="hidden" value="John" name="customerFirstName">
<input type="hidden" value="Doe" name="customerLastName">
<input type="hidden" value="john@doe.com" name="customerEmail">
<input type="hidden" value="https://example.com/success.php" name="urlSuccess">
<input type="hidden" value="https://example.com/failure.php" name="urlFailure">
<input type="hidden" value="https://example.com/return.php" name="urlReturn">
<input type="hidden" value="7c46b5c9836520a9a0552d31136a705d3f7a7ea3db13de87f41fb3ee31f6dbff;sha256" name="signature">
```

and then you can wrap it to your own `form` HTML tag

```php
<form method="POST" action="http://example.com/pl/payment">
    <input type="hidden" value="133" name="serviceId">
    <input type="hidden" value="133" name="merchantId">
    <input type="hidden" value="133" name="amount">
    <input type="hidden" value="PLN" name="currency">
    <input type="hidden" value="123456790" name="orderId">
    <input type="hidden" value="John" name="customerFirstName">
    <input type="hidden" value="Doe" name="customerLastName">
    <input type="hidden" value="john@doe.com" name="customerEmail">
    <input type="hidden" value="https://example.com/success.php" name="urlSuccess">
    <input type="hidden" value="https://example.com/failure.php" name="urlFailure">
    <input type="hidden" value="https://example.com/return.php" name="urlReturn">
    <input type="hidden" value="7c46b5c9836520a9a0552d31136a705d3f7a7ea3db13de87f41fb3ee31f6dbff;sha256" name="signature">
    
    <input class="button" type="submit" value="Continue" id="submit-payment-form">
</form>
```

## Additional features:
Library provides few additional methods that you can use.

First you need to add `use Imoje\Payment\Util;` then you can:
 
* `Util::getSupportedCurrencies()` - it will return an array with available currencies.
* `Util::getSupportedLanguages()` - it will return an array with available languages.
* `Util::canUseForCurrency($currencyCode)` - `$currencyCode` should be in format ISO4217. The method will return bool.

## Supported variables:

The supported currencies are:
`PLN`

The supported languages are:
`Polish, English`

The format you should use for it is ISO 639-1 (Alpha-2 code)
