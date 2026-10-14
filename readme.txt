=== Bitcoin payment for WooCommerce ===
Contributors: coinsnap
Tags: Lightning, SATS, bitcoin, WooCommerce, payment gateway
Tested up to: 6.9
Stable tag: 1.7.1
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Accept Bitcoin and Lightning payments in WooCommerce with Coinsnap or your own BTCPay Server. No own Lightning node required.

== Description ==

= Accept Bitcoin and Lightning payments in WooCommerce =

Coinsnap for WooCommerce is an open-source Bitcoin payment plugin that adds Bitcoin payments directly to the WooCommerce checkout.

Customers can pay with **Bitcoin via Lightning or on-chain**, while merchants keep their existing WooCommerce order and checkout workflow.

When using Coinsnap, merchants do not need to operate their own Bitcoin or Lightning payment infrastructure. Coinsnap follows a **non-custodial / self-custody model** and does not hold merchant funds. Payments are sent to the merchant's configured Bitcoin or Lightning wallet, or can be converted and settled to a bank account through a supported off-ramp.

Coinsnap is **Bitcoin payment software for merchants** and a brand of **Onlineshop24 d.o.o., Serbia**.

= Key features =

* Accept **Bitcoin and Lightning payments** in WooCommerce
* Support for **Lightning and Bitcoin on-chain**
* Native integration into the WooCommerce checkout
* Automatic WooCommerce order status updates after payment
* Customizable payment method name, checkout text, icon and redirect behavior
* No own Lightning node or Bitcoin payment server required when using Coinsnap
* **Non-custodial / self-custody** payment model
* Settlement to a configured Bitcoin or Lightning wallet
* Optional fiat settlement through supported off-ramp partners
* Support for **BTCPay Server** for merchants who prefer a self-hosted Bitcoin payment infrastructure
* Open source and free to install

= How it works =

1. Create a [Coinsnap merchant account](https://app.coinsnap.io/register).
2. Install and activate the Coinsnap for WooCommerce plugin.
3. Connect WooCommerce to your Coinsnap account using your Store ID and API key.
4. Enable Bitcoin as a payment method in WooCommerce.
5. Customers select Bitcoin at checkout and pay by scanning the displayed QR code.
6. Coinsnap detects the payment and updates the WooCommerce order automatically.

= Coinsnap or BTCPay Server =

The plugin supports two operating modes:

* **Coinsnap** – for merchants who want to accept Bitcoin without running their own payment infrastructure.
* **BTCPay Server** – for merchants who operate their own BTCPay Server and want to connect it to WooCommerce through the same plugin.

= Fees =

Coinsnap charges a **1% transaction fee**. The fee is charged separately against the merchant's Coinsnap credit because Coinsnap does not deduct fees from the Bitcoin payment itself.

= More information =

* Demo Store: [https://woocommerce.coinsnap.org/](https://woocommerce.coinsnap.org/)
* Product page: [https://coinsnap.io/modules/woocommerce/](https://coinsnap.io/modules/woocommerce/)
* Installation Guide: [https://coinsnap.io/modules/woocommerce/woocommerce-installation-guide/](https://coinsnap.io/modules/woocommerce/woocommerce-installation-guide/)
* GitHub plugin page: [https://github.com/Coinsnap/Coinsnap-for-Woocommerce](https://github.com/Coinsnap/Coinsnap-for-Woocommerce)
* WordPress plugin directory: [https://wordpress.org/plugins/coinsnap-for-woocommerce/](https://wordpress.org/plugins/coinsnap-for-woocommerce/)

= Documentation =

* [Coinsnap API documentation](https://docs.coinsnap.io/)
* [Coinsnap merchant FAQ](https://coinsnap.io/help/coinsnap-bitcoin-payment-faq/)
* [Terms and Conditions](https://coinsnap.io/info/general-terms-and-conditions/)
* [Privacy Policy](https://coinsnap.io/info/privacy-policy/)

== Installation ==

= 1. Install the Coinsnap for WooCommerce plugin from the WordPress plugin repository =

The Coinsnap for WooCommerce plugin can be searched and installed in the WordPress plugin directory.

You can easily find the Coinsnap for WooCommerce plugin under Plugins/Install new plugin if you enter Coinsnap WooCommerce in the search field. Simply click on Install now in the Coinsnap plugin and WordPress will install it for you.

Now WordPress will offer you to Activate the pugin – click the button and you set to go!

Next, you will connect the plugin with your Coinsnap account.

= 1.1. WooCommerce Coinsnap Settings =

After you have installed and activated the Coinsnap for WooCommerce plugin, you need to configure the Coinsnap settings. To do that, go to **WooCommerce / Settings**. Now open the tab on the far right named **Coinsnap Settings**.

You will see in a red message on the top of the tab, that there is a **Coinsnap serer is disconnected**. Ignore this for now, for we will now connect the plugin with your Coinsnap account.


To do that, you need to enter your Coinsnap **Store ID** and your Coinsnap **API key**.

These details are provided in your Coinsnap account in the **store settings** section. If you do not yet have a Coinsnap account, you can register under the following link: [Coinsnap registration](https://app.coinsnap.io/register).

You will be asked to name your store, and to enter your Lightning wallet adress. When this is done you are ready for the next step.

= 1.2. Enter Store ID and API Key in your WooCommerce Coinsnap Settings =

Go to the **Settings** menu item in your Coinsnap merchant admin backend. Then click on **Store** and you will see your Coinsnap **Store ID** and the Coinsnap **API Key** in the **Store** section.

**Copy** these two strings and **paste** them into the matching fields in the **Coinsnap settings in WooCommerce** in your shop’s backend. 

Click on the “**Save changes**” button at the bottom of the page to apply and save the settings. You will now see a green message on top of the tab saying **Coinsnap server is connected**. You are almost ready to start selling for Bitcoin now.

= 1.3. WooCommerce payment settings =

Now you need to **add the payment method** Bitcoin-Lightning payment to your **WooCommerce checkout**.

To do this, navigate to the **Payments** tab under the WooCommerce settings to see a list of all available payment methods. Coinsnap is shown at the bottom of the list.

(1) **Enable** Coinsnap by moving the slider to the right.

You must activate Coinsnap as a payment option!

**Additional configurations – customize your checkout**

To do this, click on the “**Finish setup**” button (or the “**Manage**” button after you activated the payment method). Here you can make some adjustments that impact the customer’s journey during the payment process.

(a) **Enabled/Disabled**: Make sure the gateway is enabled!
(b) **Title**: You can change the default text, i.e. to „Bitcoin/Lightning payment“.
(c) **Customer Message**: Here you should let your customer know what to expect next. 
(d) **Button Text**: you can also change the text on the button that initiates the actual payment and generatest he QR code for your customer’s payment, i.e. to „I want to pay with Bitcoin now!“  
(e) **Gateway symbol**: Here you can upload an icon of your choice that illustrates the Bitcoin-Lightning payment method in your checkout. This image serves as a visual indicator for a specific payment option or gateway that is displayed to the customer at the time of checkout.

= YOU ARE SET TO SELL FOR BITCOIN NOW! To be sure all works fine, you should now… =

= 1.4. Test the payment method in your WooCommerce store =

After all settings have been made, a test transaction should be carried out.

Place an order in your WooCommerce online store and choose the Bitcoin-Lightning Payment option. Click on Pay with Bitcoin.

You will now be redirected to the Bitcoin-Lightning payment page to complete the purchase. The Bitcoin payment page is now displayed and offers the payer the option of paying with Bitcoin or Lightning. Both methods are integrated in the displayed QR code. After successful payment, the invoice can be viewed in detail.


= 2. Install the Coinsnap WooCommerce plugin from our Github page =

If you don’t want to install Coinsnap WooCommerce plugin directly from your WordPress backend, download the Coinsnap for WooCommerce plugin from the [Coinsnap Github page here](https://github.com/Coinsnap/Coinsnap-for-Woocommerce).

Find the green button labeled **Code**. When you click on it, the menu opens and Download ZIP appears. Here you can download the latest version of the Coinsnap plugin to your computer.

Then use the “**Upload plugin**” function to install it. Click on “**Install now**” and the Coinsnap for WooCommerce plugin will be added to your WordPress website. It can then be connected to the Coinsnap payment gateway.

As soon as the Coinsnap for WooCommerce plugin is installed and activated, a message will appear asking you to configure the plugin settings.

From here on you can follow 1.1 to 1.4 and you will be set to sell for Bitcoin in no time at all!



== Upgrade Notice ==

Follow updates on the plugin's [GitHub page](https://github.com/Coinsnap/Coinsnap-for-Woocommerce).

== Frequently Asked Questions ==

= What are the requirements for accepting Bitcoin in my WooCommerce store? =

You need an active WooCommerce store and a Coinsnap merchant account. For Bitcoin settlement, you can use a compatible Bitcoin or Lightning wallet. You do not need to run your own Lightning node or Bitcoin payment server when using Coinsnap.

= Which Bitcoin or Lightning wallets can I use? =

You can use a compatible Bitcoin or Lightning wallet as your settlement wallet. One option is the [Coinsnap Wallet](https://coinsnap.io/wallet/), which combines a self-custodial Bitcoin wallet with merchant and point-of-sale functionality.

Other compatible wallets may include solutions such as Wallet of Satoshi, Blink and Zeus, depending on the settlement setup you choose.

= Can I customize my Bitcoin checkout? =

Yes. You can define the payment method name, upload your own icon or logo, customize checkout text, and configure the redirect after a successful payment.

= Do I need technical knowledge to install the plugin? =

No advanced technical knowledge is required. The plugin can be installed through WordPress, connected with your Coinsnap credentials, and enabled as a WooCommerce payment method.

= Can customers pay with both Lightning and Bitcoin on-chain? =

Yes. Coinsnap supports Bitcoin payments via both the Lightning Network and Bitcoin on-chain.

= How quickly are Bitcoin payments processed? =

Lightning payments are typically confirmed within seconds. On-chain Bitcoin payments are detected when the transaction appears on the Bitcoin network, while final blockchain confirmation takes longer depending on network conditions.

= Does Coinsnap hold my Bitcoin? =

No. Coinsnap is non-custodial and does not hold or manage merchant funds. Payments are sent to the merchant's configured wallet, or converted and settled through a connected off-ramp if the merchant chooses fiat settlement.

= What fees does Coinsnap charge? =

Coinsnap charges a 1% transaction fee. The fee is charged separately against the merchant's Coinsnap credit and is not deducted from the Bitcoin payment.

= Can I use my own BTCPay Server instead of Coinsnap? =

Yes. The WooCommerce plugin also supports BTCPay Server for merchants who prefer to operate their own self-hosted Bitcoin payment infrastructure.

= Can I receive Bitcoin sales in my bank account? =

Yes. Coinsnap supports integrations with off-ramp partners that can convert Bitcoin payments and settle the proceeds to a bank account, subject to the selected provider's availability and conditions.

= Can customers charge back a Bitcoin payment? =

Bitcoin payments do not support chargebacks in the same way as card payments. Merchants should still handle refunds according to their own store policies.

= Is the plugin available worldwide? =

Yes. Coinsnap is designed for merchants worldwide. Bitcoin and Lightning payments can be accepted from customers globally, subject to any legal or service restrictions that may apply in the merchant's jurisdiction.

= Are there regular updates for the Coinsnap for WooCommerce plugin? =

Yes. The plugin is actively maintained and updated for compatibility, improvements and new functionality. Available updates are shown in WordPress and can be installed through the normal plugin update process.

= Where can I get support? =

Use the support area in your Coinsnap merchant account or visit the [Coinsnap merchant FAQ](https://coinsnap.io/help/coinsnap-bitcoin-payment-faq/).

== Screenshots ==

1. Install the Coinsnap for WooCommerce Plugin in WordPress
2. Activate plugin!
3. Go to the Coinsnap Settings tab in WooCommerce settings
4. Now you need to fill the fields „Coinsnap Store ID“ and „Coinsnap API Key“
5. You find these data in your Coinsnap backend
6. Add these strings in fields 2 and 3, respectively
7. Go to „Payments“ tab in the WooCommerce settings to activate Bitcoin payment (1)
8. Customize the checkout process according to your needs
9. Bitcoin payment activated
10. Coinsnap plugin connected to Coinsnap payment gateway
11. Coinsnap plugin connected to BTCPay server
12. Download Coinsnap for WooCommerce on github
13. QR code on the Bitcoin payment page
14. Invoice is paid

== Changelog ==
= 1.0.0 :: 2023-08-03 =
* First public release for testing.

= 1.0.5 :: 2023-12-12 =
* Payment button text setting is added.

= 1.1.0 :: 2024-02-28 =
* Basic Wordpress Blocks support is added.

= 1.1.1 :: 2024-03-22 =
* Restructured loader.

= 1.1.2 :: 2024-05-08 =
* Payment status display update.
* Fix: Payment status changing.

= 1.1.3 :: 2024-07-03 =
* Order number and order ID separation.
* Full order metadata array transmission.

= 1.1.4 :: 2024-10-03 =
* Wordpress library update.
* Data output security update.
* Compatibility with WooCommerce 9.3.x. is tested
* Fix: order ID variable type is ?string.

= 1.1.5 :: 2024-10-11 =
* Wordpress library update.
* Fixed error on checkout.

= 1.1.6 :: 2024-11-06 =
* Coinsnap connection errors exception update.
* Update of Coinsnap gateway icon upload functionality.
* Update of payment method name by default (Bitcoin-Lightning)

= 1.1.7 :: 2024-12-10 =
* Fixed bug in ConnectException handler.

= 1.1.8 :: 2024-12-24 =
* Fixed mismatch variables types bug in ConnectException function call.

= 1.1.9 :: 2025-01-18 =
* Fixed webhook creation bug in processWebhook function call.
* Update of webhook payload response statuses.

= 1.1.10 :: 2025-01-27 =
* Added Coinsnap server connection check indication in backend.

= 1.1.11 :: 2025-02-09 =
* Update: Coinsnap server connection check indication in backend marks as "WooCommerce".
* Default WooCommerce status for "Settled" is "Completed".
* Compatibility with WooCommerce 9.6.x. is tested.

= 1.1.12 :: 2025-03-09 =
* Fixed Wordpress warning about non-used wp_get_attachment_image() method for Coinsnap payment gateway icon in WooCommerce payment settings.
* Compatibility with WooCommerce 9.7.x. is tested.

= 1.2.0 :: 2025-04-04 =
* Update: Woocommerce currencies list is limited by currencies, supported by Coinsnap.
* Update: After plugin activation or update non-supported currency switches to USD.
* Update: Added option "Redirect after payment automatically".
* Update: Prevented redirect to payment gateway if payment amount is less than 1 SAT or currency in not supported by Coinsnap.
* Update: Error message changed to "Coinsnap server is disconnected" if Coinsnap server is not connected or there is webhook error.

= 1.3.0 :: 2025-04-16 =
* Update: BTCPay server added as a Bitcoin payment gateway
* Update: Added discount for payment with Coinsnap functionality
* Compatibility with WooCommerce 9.8.x. is tested.
* Compatibility with Wordpress 6.8 is tested.

= 1.3.1 :: 2025-05-01 =
* Update: If BTCPay server is chosen as a Bitcoin payment gateway, we don't limit WooCommerce currencies list.
* Compatibility with Wordpress 6.8.1 is tested.
* Compatibility with WooCommerce 9.8.3. is tested.

= 1.3.5 :: 2025-05-05 =
* Update: Prevented redirect to BTCPay server if payment amount is less than 0.000005869 BTC (0.50 EUR) for onchain payments, 0.000001 BTC (1 SAT) for Lightning payment or currency is not supported.
* Update: BTCPay setup wizard is added in BTCPay server settings.

= 1.3.6 :: 2025-05-06 =
* Update: Minimum order amount is added to connection status notice.
* Update: Woocommerce store admin can choose where to show connection status notice and hide it from all the Admin pages.
* Compatibility with WooCommerce 9.8.4. is tested.

= 1.3.7 :: 2025-05-13 =
* Update: Coinsnap Wordpress library.
* Update: Permissions check is added to BTCPay server request handler on BTCPay server connection setup process.
* Compatibility with WooCommerce 9.8.5. is tested.

= 1.3.8 :: 2025-05-18 =
* Fixed bug in payment process when discount is disabled.
* Fixed bug with discount fields turning on on payment settings page.

= 1.3.9 :: 2025-05-19 =
* Updated connection check errors handler

= 1.3.10 :: 2025-05-27 =
* Fixed bug in payment process when payment provider wasn't chosen.
* Updated gateway connection status displaying conditions.

= 1.4.0 :: 2025-06-10 =
* Fixed bug in webhook payload process when payment provider wasn't chosen.
* Update: Checking and deleting all the inactive webhooks for the store.
* Compatibility with WooCommerce 9.9.3. is tested.

= 1.4.1 :: 2025-06-20 =
* Update: Unique name for BTCPay server setup Wizard return URL is set.

= 1.4.2 :: 2025-07-19 =
* Update: Added nonce check for BTCPay server request in Wizard setup flow.
* Updated Generate API Key button behavior.
* Compatibility with WooCommerce 10.0. is tested.
* Compatibility with Wordpress 6.8.2 is tested.

= 1.5.0 :: 2025-08-03 =
* Updated BTCPay server minimum payment amount calculation system
* Updated BTCPay server events and webhook payload handler
* Compatibility with WooCommerce 10.0.4 is tested.

= 1.5.1 :: 2025-08-16 =
* Added action ins in plugins list
* Compatibility with WooCommerce 10.1.0 is tested.

= 1.5.2 :: 2025-08-30 =
* Fixed error with webhook registration on BTCPay server
* Compatibility with WooCommerce 10.1.2 is tested.

= 1.5.3 :: 2025-09-12 =
* Updated webhook payload handler

= 1.5.4 :: 2025-09-18 =
* Update: Coinsnap is active payment method on checkout if no payment method is set.

= 1.6.0 :: 2025-09-23 =
* Update: Bitcoin discount is indicated in payment methods list on Checkout page.

= 1.6.1 :: 2025-10-10 =
* Update: Added non-supported by BTCPay server currencies handler on invoice request flow.
* Update: Added custom redirect after payment page in module settings.
* Compatibility with Wordpress 6.8.3 and WooCommerce 10.2.2 is tested.

= 1.6.2 :: 2025-10-17 =
* Fixed bug with order amount check method

= 1.6.3 :: 2025-10-24 =
* Fixed PHP 8.2+ warnings
* Compatibility with WooCommerce 10.3.0 is tested.

= 1.6.4 :: 2025-11-16 =
* Updated stored webhooks filtration
* Fixed redirection warnings
* Compatibility with WooCommerce 10.3.5 is tested.

= 1.6.5 :: 2025-11-18 =
* Fixed AbstractGateway::getDiscount() method return value

= 1.7.0 :: 2025-11-23 =
* Updated \Coinsnap\Client\Store class
* Updated \Coinsnap\Client\Store::getStore() method data usage
* Updated orders list page and order details page in backend - link to payment details page on the gateway's side is added
* Fixed backend warnings on PHP 8.3+

= 1.7.1 :: 2025-12-04 =
* Compatibility with WooCommerce 10.3.6 is tested.
* Compatibility with Wordpress 6.9 is tested.



