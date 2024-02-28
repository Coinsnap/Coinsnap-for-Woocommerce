![Image of Coinsnap for WooCommerce](https://coinsnap.io/wp-content/uploads/2023/11/Coinsnap-for-Woocommerce-2.png)

# Coinsnap for WooCommerce Payment Plugin


=== Coinsnap for WooCommerce 1.1.0 ===
Contributors: coinsnap
Tags: Lightning, Lightning Payment, SATS, Satoshi sats, bitcoin, Wordpress, WooCommerce, payment gateway, accept bitcoin, bitcoin plugin, bitcoin payment processor, bitcoin e-commerce, Lightning Network, cryptocurrency, lightning payment processor
Requires at least: 5.2
Tested up to: 6.4.3
Requires PHP: 7.4
Stable tag: 1.1.0
License: MIT
License URI: https://github.com/Coinsnap/coinsnap-woocommerce/blob/master/license.txt

Coinsnap is a Lightning payment provider and offers a payment gateway for processing Bitcoin and Lightning payments. A merchant only needs a Lightning wallet with a lightning address to accept Bitcoin and Lightning payments on their website.

== Description ==

If you run an online store based on WooCommerce or a WordPress plugin that accesses WooCommerce for payment processing, then you can easily integrate payment processing via Bitcoin and Lightning with the Coinsnap WooCommerce plugin.

Just install the Coinsnap WooCommerce plugin, connect it to your Coinsnap account and your customers will be able to pay you with Bitcoin and Lightning.

Incoming Bitcoin payments are directly forwarded and credited to your Lightning Wallet.


== Installation ==

### 1.1 Install the Coinsnap WooCommerce plugin from the WordPress directory ###

The Coinsnap WooCommerce plugin can be searched and installed in the WordPress plugin directory.

![](https://coinsnap.io/wp-content/uploads/2023/09/Photo1.png)

In your WordPress instance, go to the Plugins > Add New section. In the search you enter Coinsnap and get as a result the Coinsnap WooCommerce plugin displayed.
After successful installation, click Activate and then you can start setting up the plugin.

### 1.2. Install the Coinsnap WooCommerce plugin from Github page ###

If you don’t want to install Coinsnap WooCommerce plugin directly via plugin, you can download Coinsnap WooCommerce plugin from Coinsnap Github page here.
Find the green button labeled Code. When you click on it, the menu opens and Download ZIP appears. Here you can download the latest version of the Coinsnap plugin to your computer.

![](https://coinsnap.io/wp-content/uploads/2023/11/github-coinsnap.jpg)

Then use the “Upload plugin” function to install it. Click on “Install now” and the Coinsnap for WooCommerce plugin will be added to your WordPress website. It can then be connected to the Coinsnap payment gateway.

![](https://coinsnap.io/wp-content/uploads/2023/08/Add-Coinsnap-Woocommerce-plugin.png)

As soon as the Coinsnap for WooCommerce plugin is installed and activated, a message will appear asking you to configure the plugin settings.

== Connect Coinsnap account with WooCommerce plugin ==

### 2.1. WooCommerce Coinsnap Settings ###

After you have installed and activated the Coinsnap for WooCommerce plugin, you need to make the Coinsnap settings. You can access this area via WooCommerce and Settings. On the far right you will find Coinsnap Settings.
![](https://coinsnap.io/wp-content/uploads/2023/09/Photo2-1.png)

After clicking on the link provided or going to the Coinsnap settings tab, a form will appear asking you for your Coinsnap Store ID and your Coinsnap API key.

![](https://coinsnap.io/wp-content/uploads/2023/09/Screenshot-2023-09-09-at-10.16.23.png)

These details are provided via your Coinsnap account in the store settings section. If you do not yet have a Coinsnap account, you can register under the following link: [Coinsnap registration](https://app.coinsnap.io/register).

### 2.1.1. Coinsnap Store Settings needed for configuration ###

![](https://coinsnap.io/wp-content/uploads/2023/09/Screenshot-2023-09-12-at-08.40.13-1-2.png)

Go to the Settings menu item in the Coinsnap backend. There you will find the Coinsnap Store ID and the Coinsnap API Key in the Store Settings section.

Click on the “Save changes” button at the bottom of the page to apply and save the settings.

== WooCommerce payment settings ==

### 3. WooCommerce payment settings ###

Navigate to the Payment tab under the WooCommerce settings to see a list of all available payment methods. Coinsnap is shown at the end of the list.

![](https://coinsnap.io/wp-content/uploads/2023/09/Photo4-1.png)

##### (1) Activate Coinsnap #####
You must activate Coinsnap as a payment option.

##### (2) Additional configurations #####
To do this, click on the “End setup” or “Manage” button.

--------------------------------------------------------------------------------------------------
Adjustments can be made here, which are displayed to the customer during the payment process.

![](https://coinsnap.io/wp-content/uploads/2023/11/Photo5-2-1.png)

##### (1) Title field #####
In the Title field, for example, you can specify that you accept “Bitcoin and Lightning”.

##### (2) Notes field #####
A note can also be entered in the Customer message field to inform the payer of the next steps.

![](https://coinsnap.io/wp-content/uploads/2023/11/Photo5-2-1-1.png)

##### (3) Gateway symbol #####
By selecting the “Upload or select icon” button, you have the option of adding a personalized icon or image that symbolizes a payment gateway or payment method for your online store. This image serves as a visual indicator for a specific payment option or gateway that is displayed to the customer at the time of checkout.

##### (4) Enforce payment coins #####
Enforce payment tokens refers to a system setting that ensures that only certain types of tokens are accepted for a specific payment method or gateway. This ensures that promotional tokens (which may represent discounts, special offers or other non-traditional forms of payment) are not mistakenly processed as regular payment tokens within this gateway.

== Test the payment in the WooCommerce store ==

### 4. Test the payment in the WooCommerce store ###

After all settings have been made, a test transaction should be carried out.

![](https://coinsnap.io/wp-content/uploads/2023/09/Photo7.png)

Place an order in your WooCommerce online store and search for Bitcoin and Lightning Payment among the available payment options. Choose this method and click on Pay with Bitcoin.
You will be redirected to the Bitcoin payment page to complete the purchase.

<p float="left">
  <img src="https://coinsnap.io/wp-content/uploads/2023/09/Photo9.59.png" width="350" height="500" />
  <img src="https://coinsnap.io/wp-content/uploads/2023/09/Photo9-1.png" width="380" height="500" /> 
</p>

The Bitcoin payment page is now displayed and offers the payer the option of paying with Bitcoin or Lightning. Both methods are integrated in the displayed QR code. After successful payment, the invoice can be viewed in detail.
