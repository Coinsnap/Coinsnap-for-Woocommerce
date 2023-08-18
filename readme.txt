=== Coinsnap for WooCommerce 1.0 ===
Contributors: coinsnap
Tags: SATS, Satoshi sats, bitcoin, btcpay, BTCPay Server, btcpayserver, Wordpress, WooCommerce, payment gateway, accept bitcoin, bitcoin plugin, bitcoin payment processor, bitcoin e-commerce, Lightning Network, Litecoin, cryptocurrency
Requires at least: 5.2
Tested up to: 6.2
Requires PHP: 7.4
Stable tag: 1.0
License: MIT
License URI: https://github.com/Coinsnap/coinsnap-woocommerce/blob/master/license.txt

Coinsnap is a free and open-source bitcoin payment processor which allows you to receive payments in Bitcoin and Satoshi sats directly, with no fees, transaction cost or a middleman.

== Description ==

If you run an online store based on WooCommerce or a WordPress plugin that accesses WooCommerce for payment processing, then you can easily integrate payment processing via Bitcoin and Lightning with the Coinsnap WooCommerce plugin.

Just install the Coinsnap WooCommerce plugin, connect it to your Coinsnap account and your customers will be able to pay you with Bitcoin and Lightning.

Incoming Bitcoin payments are directly forwarded and credited to your Lightning Wallet.


== Installation ==


The Coinsnap WooCommerce plugin can be searched and installed in the WordPress plugin directory.

In your WordPress instance, go to the Plugins > Add New section.
In the search you enter Coinsnap and get as a result the Coinsnap WooCommerce plugin displayed.

Then click Install.

After successful installation, click Activate and then you can start setting up the plugin.

<img src="https://github.com/btcpayserver/btcpayserver-doc/blob/master/img/BTCPayWooCommerceInfoggraphic.png" alt="Infographic" />

To integrate BTCPay Server into an existing WooCommerce store, follow the steps below or check our official [installation instructions](https://docs.btcpayserver.org/WooCommerce/).

### 1. Deploy BTCPay Server (optional) ###

This step is optional, if you already have a BTCPay Server instance setup you can skip to section 2. below. To launch your BTCPay server, you can self-host it, or use a third party host.

#### 1.1 Self-hosted BTCPay ####

There are various ways to [launch a self-hosted BTCPay](https://github.com/btcpayserver/btcpayserver-doc#deployment). If you do not have technical knowledge, use the [web-wizard method](https://launchbtcpay.lunanode.com) and follow the video below.

https://www.youtube.com/watch?v=NjslXYvp8bk

For the self-hosted solutions, you will have to wait for your node to sync fully before proceeding to step 3.

#### 1.2 Third-party host ####

Those who want to test BTCPay out, or are okay with the limitations of a third-party hosting (dependency and privacy, as well as lack of some features) can use a one of many [third-party hosts](ThirdPartyHosting.md).

The video below shows you how to connect your store to such a host.

https://www.youtube.com/watch?v=IT2K8It3S3o

### 2. Install BTCPay WooCommerce Plugin ###

BTCPay WooCommerce plugin is a bridge between your BTCPay Server (payment processor) and your e-commerce store. No matter if you are using a self-hosted or third-party solution from step 1., the connection process is identical.

You can find detailed installation instructions on our [WooCommerce documentation](https://docs.btcpayserver.org/WooCommerce/).

Here is a quick walk through if you prefer a video:

https://www.youtube.com/watch?v=ULcocDKZ1Mw

###  3. Connecting your wallet ###

No matter if you're using self-hosted or server hosted by a third-party, the process of configuring your wallet is the same.

https://www.youtube.com/watch?v=xX6LyQej0NQ

### 4. Testing the checkout ###

Making a small test-purchase from your own store, will give you a piece of mind. Always make sure that everything is set up correctly before going live. The final video, guides you through the steps of setting a gap limit in your Electrum wallet and testing the checkout process.

Depending on your business model and store settings, you may want to fine tune [your order statuses](https://docs.btcpayserver.org/WooCommerce/#41-global-settings).

== Frequently Asked Questions ==

You'll find extensive documentation and answers to many of your questions on [BTCPay for WooCommerce V2 docs](https://docs.btcpayserver.org/WooCommerce) and on [BTCPay for WooCommerce integrations FAQ](https://docs.btcpayserver.org/FAQ/Integrations/#woocommerce-faq).

== Screenshots ==

1. The BTCPay Server invoice. Your customers will see this at the checkout. They can pay from their wallet by scanning a QR or copy/pasting it manually into the wallet.
2. Customizable plugin interface allows store owners to adjust store statuses according to their needs.
3. Customer will see the pay with Bitcoin button at the checkout.Text can be customized.
4. Example of successfully paid invoice.
5. Example of an easy-embeddable HTML donation payment button.
6. Example of the PoS app you can launch.

== Changelog ==
= 1.0 :: 2023-08-03 =
* First public release for testing.
