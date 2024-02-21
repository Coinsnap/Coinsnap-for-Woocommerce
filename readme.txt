=== Coinsnap Bitcoin + Lightning payment plug-in 1.0 ===
Contributors: coinsnap
Tags: Lightning, SATS, bitcoin, WooCommerce, payment gateway
Requires at least: 5.2
Tested up to: 6.2
Requires PHP: 7.4
Stable tag: 1.0
License: MIT
License URI: https://github.com/Coinsnap/coinsnap-woocommerce/blob/master/license.txt


Bitcoin and Lightning payment processing with the Coinsnap plugin for online store based on WooCommerce.

== Description ==

Coinsnap is a Lightning payment provider and offers a payment gateway for processing Bitcoin and Lightning payments. A merchant only needs a Lightning wallet with a lightning address to accept Bitcoin and Lightning payments on their website.

== Installation ==

### 1. Install the Coinsnap WooCommerce plugin from the WordPress directory. ###

The Coinsnap WooCommerce plugin can be searched and installed in the WordPress plugin directory.

In your WordPress instance, go to the Plugins > Add New section.
In the search you enter Coinsnap and get as a result the Coinsnap WooCommerce plugin displayed.


Then click Install.

After successful installation, click Activate and then you can start setting up the plugin.

### 1.1. Add plugin ###

If you don’t want to install Coinsnap WooCommerce plugin directly via plugin, you can download Coinsnap WooCommerce plugin from Coinsnap Github page or from WordPress directory and install it via “Upload Plugin” function.

<img src="https://github.com/Coinsnap/coinsnap-woocommerce/blob/master/assets/images/01-Add-Coinsnap-Woocommerce-plugin.png" alt="Add Coinsnap Woocommerce plugin" />

Click “Install now” and Coinsnap WooCommerce plugin will be installed in WordPress.

After you have successfully installed the plugin, you can proceed with the connection to Coinsnap payment gateway.

### 1.2. Configure Coinsnap WooCommerce Plugin ###

After the Coinsnap WooCommerce plugin is installed and activated, a notice appears that the plugin still needs to be configured.

### 1.3. Deposit Coinsnap data ###

After clicking on the displayed link or via the Coinsnap Settings tab, you will get to an input mask where you have to enter the Coinsnap Store ID and the Coinsnap API Key. You will receive these two data via your Coinsnap account.

If you don’t have a Coinsnap account yet, you can do so via the link shown: Coinsnap Registration

### 2. Create Coinsnap account ####

### 2.1. Create a Coinsnap Account ####

<img src="https://github.com/Coinsnap/coinsnap-woocommerce/blob/master/assets/images/02-coinsnap-account.png" alt="Coinsnap account" />

Now go to the Coinsnap website at: https://app.coinsnap.io/register and open an account by entering your email address and a password of your choice.

If you are using a Lightning Wallet with Lightning Login, then you can also open a Coinsnap account with it.

<img src="https://github.com/Coinsnap/coinsnap-woocommerce/blob/master/assets/images/03-Coinsnap-register.png" alt="Coinsnap register" />


### 2.2. Confirm email address ####

You will receive an email to the given email address with a confirmation link, which you have to confirm. If you do not find the email, please check your spam folder.

<img src="https://github.com/Coinsnap/coinsnap-woocommerce/blob/master/assets/images/04-Email-Adresse-bestaetigen.png" alt="Email address confirmation" />


Then please log in to the Coinsnap backend with the appropriate credentials.

### 2.3. Set up website at Coinsnap ###

After you sign up, you will be asked to provide two pieces of information.

In the Website Name field, enter the name of your online store that you want customers to see when they check out.

<img src="https://github.com/Coinsnap/coinsnap-woocommerce/blob/master/assets/images/05-connect-website-with-coinsnap.png" alt="Connect website with Coinsnap" />

In the Lightning Address field, enter the Lightning address to which the Bitcoin and Lightning transactions should be forwarded.

A Lightning address is similar to an e-mail address. Lightning payments are forwarded to this Lightning address and paid out. If you don’t have a Lightning address yet, set up a Lightning wallet that will provide you with a Lightning address.

For more information on Lightning addresses and the corresponding Lightning wallet providers, click here:
https://coinsnap.io/lightning-wallet-mit-lightning-adresse/

### 2.4. Coinsnap settings ###

In the Settings section there is the Website Settings section. Here you will find the details for the Coinsnap Website ID and the Coinsnap API Key.

<img src="https://github.com/Coinsnap/coinsnap-woocommerce/blob/master/assets/images/06-website_settings.jpg" alt="Website settings" />

### 3. Connect Coinsnap account with WooCommerce plugin ###

### 3.1. WooCommerce Coinsnap Settings ###

Within WooCommerce there is the Coinsnap Settings section. In the Coinsnap Settings section, the information from the Coinsnap backend for the Coinsnap Website ID and the Coinsnap API Key can be stored accordingly.

<img src="https://github.com/Coinsnap/coinsnap-woocommerce/blob/master/assets/images/07-Coinsnap-Website-ID-und-den-Coinsnap-API-Key.jpg" alt="Coinsnap Website ID and APIKey" />

Don’t forget to click “Save changes” at the bottom of the page to save the settings.
Coinsnap Website ID und den Coinsnap API Key.

### 3.2. WooCommerce Payment Settings ###

In the WooCommerce settings in the tab Payment you get an overview of all payment methods stored in WooCommerce. At the very bottom is Coinsnap.

<img src="https://github.com/Coinsnap/coinsnap-woocommerce/blob/master/assets/images/08-Woocommerce-Payments-manage.png" alt="Woocommerce Payments manage" />

Here Coinsnap must be activated as a payment method and further settings can be made via the Finish set up or Manage button.

### 3.3. Payment settings ###

After clicking the Finish set up or Manage button, you will get to the detail settings.

Changes can be made here, which will be displayed to the payer during the payment process.

The Title field contains the entry for the payment methods. Here, for example, the settings for “Bitcoin and Lightning” can be made.

A note can be entered in the Customer Message field for the payer to know what to do next.

### 4. Test payment ###

### 4.1. Test payment in WooCommerce store ###

After all the settings have been made, a test payment should be made.

<img src="https://github.com/Coinsnap/coinsnap-woocommerce/blob/master/assets/images/09-coinsnap-woocommerce-payments.png" alt="Coinsnap woocommerce payments" />

We make a real order in our WooCommerce webshop and find Bitcoin and Lightning Payments as additional payment methods.

<img src="https://github.com/Coinsnap/coinsnap-woocommerce/blob/master/assets/images/10-Checkout.png" alt="Checkout" />

Select this payment method and click Pay with Bitcoin.

### 4.2. Bitcoin payment page ###

The Bitcoin payment page is now displayed, offering the payer the option to pay with Bitcoin or also with Lightning. Both methods are integrated in the displayed QR code.

<img src="https://github.com/Coinsnap/coinsnap-woocommerce/blob/master/assets/images/11-QR_code.png" alt="QR code on the Bitcoin payment page" />

== Upgrade Notice ==

Follow updates on plugin's GitHub page:
https://github.com/Coinsnap/coinsnap-woocommerce/

== Frequently Asked Questions ==

Plugin's page on Coinsnap website: https://coinsnap.io/en/coinsnap-woocommerce-plugin/

== Screenshots ==

1.  Add Coinsnap Woocommerce plugin
2.  Coinsnap account
3.  Coinsnap register
4.  Email address confirmation
5.  Connect website with Coinsnap
6.  Website settings
7.  Coinsnap Website ID and APIKey
8.  Woocommerce Payments manage
9.  Coinsnap Woocommerce payments
10. Checkout page
11. QR code on the Bitcoin payment page

== Changelog ==
= 1.0 :: 2023-08-03 =
* First public release for testing.