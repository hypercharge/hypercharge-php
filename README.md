# hypercharge-php

PHP SDK for the Hypercharge payment gateway.

[![Latest Stable Version](https://poser.pugx.org/hypercharge/hypercharge-php/v/stable.png)](https://packagist.org/packages/hypercharge/hypercharge-php)
[![Build Status](https://travis-ci.org/hypercharge/hypercharge-php.png)](https://travis-ci.org/hypercharge/hypercharge-php)

tested with PHP > 5.3 on OSX/Linux

Matches [hypercharge-API-doc](https://github.com/hypercharge/hypercharge-api-doc) version 2.24 2014/09/16

## Installation

Do not download the Hypercharge PHP SKD files manually.
The php package manger [composer](http://getcomposer.org/) takes care of that.

Let's say  `MY_PROJECT/` is your project root folder.


Do the following three steps:

1) [Download composer.phar](http://getcomposer.org/download/) to `MY_PROJECT/composer.phar`

2) With your text editor create a `MY_PROJECT/composer.json` containing
```json
{
  "require": {
    "php": ">=5.3",
    "hypercharge/hypercharge-php": "*"
  }
}
```
This will tell composer to install the most recent Hypercharge PHP SDK version.

3) in the shell (DOS console or terminal) go to `MY_PROJECT/` and run the command
```sh
$ php composer.phar install
```
This downloads and installs Hypercharge PHP SDK and its dependencies into `MY_PROJECT/vendor/`.


Show what has been installed
```sh
$ php composer.phar show --installed
hypercharge/hypercharge-php    1.25.5 Hypercharge PHP Library / SDK
hypercharge/hypercharge-schema 1.25.2 Hypercharge API JSON Schema
hypercharge/json-schema-php    1.3.3  A library to validate a json schema.
```
nice.


Notice: Later, when deploying your project to your server, upload the complete `MY_PROJECT/vendor/` directory as well. `MY_PROJECT/vendor/` contains the installed packages and autoload files.


## Configuration

To get started, add one of the following snippets to your global configuration file.

### Sandbox (for testing)
Hypercharge support creates a test-account and sends you login and password.
For development and testing. No real money is transfered.

```php
// config.php
require_once dirname(__DIR__).'/vendor/autoload.php';
Hypercharge\Config::set(
   'YOUR-MERCHANT-TEST-LOGIN'
  ,'YOUR-MERCHANT-TEST-PASSWORD'
  ,Hypercharge\Config::ENV_SANDBOX
);
```

### Live
When your Hypercharge boarding is complete our support team will send you your live login and password.
Real money transactions here!

```php
// config.php
require_once dirname(__DIR__).'/vendor/autoload.php';
Hypercharge\Config::set(
   'YOUR-MERCHANT-LOGIN'
  ,'YOUR-MERCHANT-PASSWORD'
  ,Hypercharge\Config::ENV_LIVE
);
```
If there is no `MY_PROJECT/vendor/autoload.php` in your project follow the [installation instructions](#installation).

## Credit Card sale Transaction

Submit 77.00 USD as a credit card sale to hypercharge channel.

Note: You have to be [PCI compliant](https://en.wikipedia.org/wiki/Payment_Card_Industry_Data_Security_Standard) to handle Credit Card data.
Most likely you are not PCI compliant, in that case you use our [Web Payment Form](#web-payment-form-wpf-session) or our [Mobile Payment](#create-mobile-payment-session).

```php
require_once 'config.php';

// see field 'currency'
$channelToken = 'YOUR-USD-CHANNEL-TOKEN';

$sale = Hypercharge\Transaction::sale($channelToken, array(
  'currency'          => 'USD'
  ,'amount'           => 7700 // in cents, must be int
  ,'transaction_id'   => 'YOUR-GENERATED-UNIQUE-ID' // TODO your order-id here
  ,'usage'            => 'Appears in the customers bank statement'
  ,'card_holder'      => 'Max Mustermann'
  ,'expiration_month' => '07'
  ,'expiration_year'  => '2018'
  ,'card_number'      => '4200000000000000'
  ,'cvv'              => '123'
  ,'customer_email'   => 'max@mustermann.de'
  ,'customer_phone'   => '+403012345678'

  // TODO: dummy for cli run only. remove!
  ,'remote_ip'        => '127.0.0.1'
  // TODO: uncomment
  //,'remote_ip'        => $_SERVER['REMOTE_ADDR']

  ,"billing_address" => array(
      "first_name" =>"Max",
      "last_name"  =>"Mustermann",
      "address1"   =>"Muster Str. 12",
      "zip_code"   =>"10178",
      "city"       =>"Berlin",
      "country"    =>"DE"
  )
));

if($sale->isApproved()) {
  // cc transaction successfull
} else {
  //
}
```

To see local validation errors wrap the `Hypercharge\Transaction::sale` call in a `try catch`.
```php
require_once 'config.php';

$channelToken = 'YOUR-USD-CHANNEL-TOKEN';
try {
  $sale = Hypercharge\Transaction::sale($channelToken, array(
    'currency'          => 'USD'
    ,'amount'           => 7700 // in cents, must be int
    ,'transaction_id'   => 'YOUR-GENERATED-UNIQUE-ID' // TODO your order-id here
    ,'usage'            => 'Appears in the customers bank statement'
    ,'card_holder'      => 'Max Mustermann'
    ,'expiration_month' => '07'
    ,'expiration_year'  => '2018'
    ,'card_number'      => '4200000000000000'
    ,'cvv'              => '123'
    ,'customer_email'   => 'max@mustermann.de'
    ,'customer_phone'   => '+403012345678'
    ,'remote_ip'        => $_SERVER['REMOTE_ADDR']
    ,"billing_address" => array(
        "first_name" =>"Max",
        "last_name"  =>"Mustermann",
        "address1"   =>"Muster Str. 12",
        "zip_code"   =>"10178",
        "city"       =>"Berlin",
        "country"    =>"DE"
    )
  ));

  if($sale->isApproved()) {
    echo "cc transaction successfull\n";
    print_r($sale);
  } else {
    echo "cc transaction FAILED\n";
    print_r($sale);
  }
} catch(Hypercharge\Errors\ValidationError $e) {
  echo "local validation errors\n";
  print_r( $e->errors );

} catch(Exception $e) {
  echo "severe error\n";
  print_r($e);
}
```

## Web Payment Form (WPF) session

The following example is more complex.

- create a WPF session.
- redirect customer browser to WPF url provided
- customer submits WPF and is redirected to return_success_url you provided.
    In the mean time hypercharge notifies your backend by calling `notification_url`, providing the payment status.

```php
require_once 'config.php';

try {
  // create the WPF payment session
  $payment = Hypercharge\Payment::wpf(array(
    'currency' => 'EUR'
    ,'amount' => 1000 // in cents
    ,'transaction_id' => 'YOUR-GENERATED-UNIQUE-ID' // TODO replace with your e.g. order id
    ,'description' => 'Appears as intro text in the WPF form'
    ,'usage' => 'Appears in the customers bank statement'

    // TODO: set your PaymentNotification handler url here
    ,'notification_url' => 'https://your-server.com/hypercharge-wpf-notifications.php'

    // TODO: set your return pages for the user here. These are the pages he is shown after leaving the WPF
    ,'return_success_url' => 'http://your-server.com/payment-return-page.php?status=success'
    ,'return_failure_url' => 'http://your-server.com/payment-return-page.php?status=failure'
    ,'return_cancel_url'  => 'http://your-server.com/payment-return-page.php?status=cancel'

    ,'billing_address' => array(
        'first_name' =>'Max',
        'last_name'  =>'Mustermann',
        'address1'   =>'Muster Str. 12',
        'zip_code'   =>'10178',
        'city'       =>'Berlin',
        'country'    =>'DE'
    )
  ));

  if($payment->shouldRedirect()) {
    // ok, WPF session created.

    // TODO: pseudocode, comment or replace with your own business logic!
    store_hypercharge_payment_unique_id_to_your_order( $payment->unique_id );

    // redirect user to WPF
    header('Location: '. $payment->getRedirectUrl());

  // handle errors...
  } elseif($payment->isPersistentInHypercharge()) {
    // payment has been created in hypercharge but something went wrong.

    // TODO: pseudocode, comment or replace with your own business logic!
    store_hypercharge_payment_unique_id_to_your_order( $payment->unique_id );

    // 1.) check $payment->error (a subclass of Hypercharge\Errors\Error)
    //     and show error message to customer
    // 2.) manually login to hypercharge merchant backend.
    //     Go to "Payments", search by unique_id and analize the log messages.

  } else {
    // TODO handle error
    // authentication error? check $login, $password
    // inputdata error? check your php code for missing or misspelled fields.

  }

} catch(Hypercharge\Errors\ValidationError $e) {
  // no payment created in hypercharge because of local pre-validation errors

  // show validation errors to customer
  // $e->errors is an Array of Hash, format: [ { "property": String , "message" : String }, ... ]

} catch(Exception $e) {
  // severe error
  // log $e
  // display apologies to customer
}
```

The WPF is displayed in English by default (`'en'`). If you want a German WPF simply change the redirection line to:

```php
    header('Location: '. $payment->getRedirectUrl('de'));
```


## WPF PaymentNotification

With a PaymentNotification Hypercharge notifies your server about a Payment status change e.g. when a Payment was successfull (status `approved`) or has failed in some way. A PaymentNotification is a server to server request in the background. Neither webbrowser nor user interaction is involved.

```
hypercharge                 your-server.com/hypercharge-wpf-notifications.php
    |   -> http POST request: notification ->   |
    |                                           |     -> store notification status to your DB
    |                                           |     -> e.g. trigger shipping (sucess) or send failure email to user (NOT success)
    |   <- http response: ack              <-   |

```

You place the php script under the url you specify as `notification_url` (`https://your-server.com/hypercharge-wpf-notifications.php` in the "Web Payment Form (WPF) session" example abough)

A scelleton:

```php
require_once 'config.php';

// $notification is an instance of Hypercharge\PaymentNotification
$notification = Hypercharge\Payment::notification($_POST);
if($notification->isVerified()) {
  $payment = $notification->getPayment();
  if($notification->isApproved()) {

    ////////////////////////////////////////
    // payment successfull
    // implement your business logic here
    ////////////////////////////////////////

  } else {

    ////////////////////////////////////////
    // payment NOT successfull
    // check $payment->status
    // implement your business logic here
    ////////////////////////////////////////

  }

  // http response.
  // Tell hypercharge the notification has been successfully processed
  // and ensure output ends here
  die( $notification->ack() );

} else {
  // signature invalid or message does not come from hypercharge.
  // check your configuration or notificatoin request origin
}
```

See [PaymentNotification](https://github.com/hypercharge/hypercharge-php/blob/master/lib/Hypercharge/PaymentNotification.php) class definition for how to use `$notification` or `$payment`.

An example with symbolic busineslogic as pseudocode:
```php
require_once 'config.php';

$notification = Hypercharge\Payment::notification($_POST);
if($notification->isVerified()) {
  $payment = $notification->getPayment();
  if($notification->isApproved()) {

    ////////////////////////////////////////
    // payment successfull
    // implement your business logic here

    // example as pseudocode, replace with your own code...

    // store notification status to your database
    // Notice: to be 100% racecondition proof update status to 'payment_approved' has to be done atomically
    $updatedRows = update_order(array(
      'set'   => array('status'=> 'payment_approved'),
      'where' => array('status'=> 'waiting_for_payment_approval'
                      ,'hypercharge_unique_id' => $payment->unique_id
      )
    ));

    if($updatedRows == 1) {
      // ok, start shipping
      $order = find_order_where(array('status' => 'payment_approved'
                                      ,'hypercharge_unique_id' => $payment->unique_id
      ));
      $order->ship_goods_to_customer();

    } else {
      // hypercharge notification already received! ignore duplicate notification.
    }
    //
    // END of your business logic
    ////////////////////////////////////////

  } else {

    ////////////////////////////////////////
    // payment NOT successfull
    // check $payment->status and handle it

    // ...

    // END of your business logic here
    ////////////////////////////////////////

  }

  // Tell hypercharge the notification has been successfully processed
  // and ensure output ends here
  die( $notification->ack() );

} else {
  // signature invalid or message does not come from hypercharge.
  // check your configuration or notificatoin request origin
}
```

## Create Mobile Payment Session

Mobile Payments are quite similar to WPF Payments.
The Session creation has slightly different data.
The Notification code is the same as WPF Notification abough.

your_server -> POST XML -> hypercharge

```php
require_once 'config.php';

try {
  // create the mobile payment session
  $payment = Hypercharge\Payment::mobile(array(
    'currency' => 'EUR'
    ,'amount' => 1000 // in cents
    ,'transaction_id' => 'YOUR-GENERATED-UNIQUE-ID'
    ,'usage' => 'Appears in the customers bank statement'
    ,'notification_url' => 'https://your-server.com/hypercharge-wpf-notifications.php'
  ));

  if($payment->shouldContinueInMobileApp()) {
    // ok, mobile payment session created.

    save_payment_unique_id_to_order($payment->unique_id);

    // tell your mobile device where to
    // a) submit credit card xml data to (submit_url)
    // b) cancel the payment if user presses 'cancel' in your mobile app (cancel_url)
    // see example below.
    die(json_encode($payment));

  } else {

    // TODO handle error
    // vaildation
    // authentication error -> check $login, $password
    // inputdata error -> check your php code for missing
    // or misspelled fields in $paymentData

  }
} catch(Hypercharge\Errors\Error $e) {
  // no payment created in hypercharge because of local pre-validation errors

  // check your php code
  // display apologies to customer "Sorry, no payment possible at the moment."
}
```

## Submit Mobile Payment from mobile device

Example Mobile Submit XML your mobile application posts to `$payment->submit_url` to process the payment.

```xml
<payment>
  <payment_method>credit_card</payment_method>
  <card_holder>Manfred Mann</card_holder>
  <card_number>4200000000000000</card_number>
  <cvv>123</cvv>
  <expiration_year>2015</expiration_year>
  <expiration_month>12</expiration_month>
</payment>
```

If you're concerned of POSTing cc data via internet: The `$payment->submit_url` will look something like `https://testpayment.hypercharge.net/mobile/submit/eabcb7a41044e764746b0c7e32c1e9d1` so the xml will be transmitted encrypted.

## Tests

Running tests for hypercharge-php itself has to be done outside of `MY_PROJECT`.
Clone hypercharge-php into its own directory

```sh
git clone https://github.com/hypercharge/hypercharge-php.git
```

step into the directory
```sh
cd hypercharge-php
```

install composer (here 1.0.0-alpha7 feel free to use a more recent one)
```sh
curl -o composer.phar http://getcomposer.org/download/1.0.0-alpha7/composer.phar
```

install dev dependecies
```sh
php composer.phar update --dev
```

### Unit Tests
Run the unit tests
```sh
php test/all.php
```

You might wonder what the output means:
```sh
File does not exist /home/hans/hypercharge-php/test/credentials.json. See README.md chapter 'Remote Tests' how to setup credentials for testing.
```
Simply read the next chapter.

### Remote Tests

The remote tests make https calls to the hypercharge sandbox (testing gateway).

At first you have to setup your login and channel tokens:

Copy `test/credentials.json.example` to `test/credentials.json`.
You received credentials when hypercharge created your test-acount.
Add the credentials to `test/credentials.json`. See values marked with `TODO`.

Run the remote tests
```sh
php test/remote.php
```
this will take about a minute.

You can use environment variables:
```sh
DEBUG=1 CREDENTIALS=development php test/remote.php
```

 * `DEBUG=1` verbose output.
 * `CREDENTIALS=development` switch to "development" credentials. Default is `sandbox`

Note:

 * `test/credentials.json` should not be checked into your code repository. e.g. add it to `.gitignore`
 * Do not run the remote tests on your live credentials.

## Warranty

This software is provided "as is" and without any express or implied warranties, including, without limitation, the implied warranties of merchantibility and fitness for a particular purpose.