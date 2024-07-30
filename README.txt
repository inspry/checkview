=== CheckView Automated Testing ===
Contributors: checkview, inspry
Donate link: https://checkview.io/
Tags: testing, monitoring, uptime, tests, woocommerce
Requires at least: 5.0.1
Tested up to: 6.6.1
Requires PHP: 7.0.0
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html
Stable tag: 1.1.14

[CheckView](https://checkview.io/) is the friendly WordPress automated testing platform for everyone, from developers, shop owners to agencies.  

== Description ==

[CheckView](https://checkview.io/) is the leading fully automated testing platform  to swiftly identify and resolve any issues with your WordPress forms and WooCommerce checkout. Enjoy the peace of mind that comes with knowing your WordPress site is working, ensuring you never lose sales due to site issues.

== Important: == 

This plugin is for users with a Checkview.io account.  The plugin will still activate, but requires the [CheckView.io](https://checkview.io/) service to function properly.

= Comprehensive Testing: =

   * Utilize real browser testing on your site's forms and Woo checkout processes, ensuring you do not lose sales from broken forms or checkouts.  WooCommerce testing can use our automated test products or your selected real products.  Forms are verified from submission to email notifications and checkout covers product pages, adding products to the cart along with cart and checkout functionality.

= Automated Scheduling: =

   * Customize test schedules to fit your needs and stay informed with notifications through various channels, including detailed video recordings of tests and pinpointing where and why failures occur.

= No Code, One-Click Integration: =

   * Effortlessly connect CheckView to your WordPress or WooCommerce with a single click with no Chrome extensions to install, Github repos to setup, or coding skills required.  Save time and money by replacing tedious manual testing of your WordPress forms or WooCommerce checkout with an automated, comprehensive process that runs in the background.

= Customizable Test Flows: =

   * Tailor your testing to match the specifics of your WordPress form plugin or WooCommerce setup, including what URLs to test and custom theme adjustments using our built in test flow step editor.

= Perfect for Multiple Websites or Agencies: =


   * CheckView can be ran across multiple websites and their associated forms with ease.  Manage all of your site's statuses and notifications within one easy to use dashboard.  Provide additional value to your clients and complement your existing uptime monitoring.

= Privacy-Focused: =

   * After each test, CheckView ensures any collected data is immediately  purged, preserving the integrity of your form submissions and order data.

= Account and Pricing: =

   * Currently in beta, CheckView invites agencies and website owners to join for free with detailed pricing to be announced soon.

    Requires a separate account at [CheckView.io](https://checkview.io/) for platform access.

= Data Sharing Commitment: = 

   *  Upon integrating your account, CheckView is dedicated to handling the  following data with the highest standards of privacy and security:

       -  General WordPress installation details and plugin inventory.

       -  Metadata for orders, carts, and forms, ensuring no personal data is compromised.

       -  Product names and images, used solely for enhancing checkout testing accuracy.

Embrace a new standard of WordPress testing with CheckView, where cutting-edge technology meets user-friendly WordPress integration.

== Installation ==

1. Upload the checkview plugin into the /wp-content/plugins/ folder on your site.

2. Use the 'Plugins' section in WordPress to enable the plugin.

3. That's all for setup! Return to CheckView.io to proceed with adding your website to the platform.

== Frequently Asked Questions ==

= Is a CheckView account required to utilize this plugin? =

Yes, creating a [CheckView.io](https://checkview.io/) account is required - for further details, [please visit this link](https://checkview.io/) to join our beta. Although the plugin can be installed without the service, automated testing functionalities will not be accessible without linking a [CheckView.io](https://checkview.io/) account.

== Screenshots ==

1. CheckView test flow.

2. CheckView test flow results.

3. CheckView general settings.

== Changelog ==

= 1.0.0 =
* Initial release.

= 1.1.0 =
* Added WooCommerce Automated Testing feature.
* Shifted WooCommerce Automated Testing functions from function.php to class.
* Updated stock prevention feature.
* Updated default testing product name.

= 1.1.1 =
* Added conditions so WooCommerce Automated Testing feature does not load in the absence of WooCommerce.

= 1.1.2 =
* Modified deleteformresults api method to incorporate all Request.

= 1.1.3 =
* Removed Cache from getformresults endpoint.
* Updated testing product name.
* Added auto cache refresh on plugin's update.

= 1.1.4 =
* Re-introduced registerformtest endpoint.
* Updated default emails sending address for [CheckView](https://checkview.io/).

= 1.1.5 =
* Resolved WooCommerce admin emails disabling issues.
* Updated dependencies area.
* Updated default emails sending address for WooCommerce emails to [CheckView](https://checkview.io/).
* Added POST call support to delete order endpoint.
* Added order id support to delete order endpoint.

= 1.1.6 =
* Declared compatibility with WooCommerce HPOS.
* Updated plugin's name.
* Updated payment gateway name.

= 1.1.7 =
* Added unit tests.
* Resolved php memory limit issue while retrieving orders.

= 1.1.8 =
* Resolved token validation issue.

= 1.1.9 =
* Added dimensions for Test Product.

= 1.1.10 =
* Resolved Cloudflare Turnstile bypass error with WPForms.
* Resolved Google Recaptcha bypass error with FluentForms.

= 1.1.11 =
* Added compatibility with FluentForms 5.1.19.
* Updated email filter hook for admin email notification of FluentForms.
* Updated email action hook for form submission of FluentForms.

= 1.1.12 =
* Added Google Recaptcha, hCaptcha and Cloudflare Turnstile bypass in Gravity Forms.
* Added Google Recaptcha V3 bypass in FluentForms.
* Updated CheckView email address to divert admin notifications accross all forms.
* Added Cloudflare Turnstile bypass in WooCommerce checkout.

= 1.1.13 =
* Added spam check bypass in all forms and WooCommerce.

= 1.1.14 =
* Added hCaptcha spam bypass in all forms.
* Added Google Recaptcha V3 bypass in Gravity Forms.
* Added hCaptcha spam bypass in WooCommerce checkout.
* Added Google Recaptcha V3 bypass in Ninja forms ( addon based ).

== Upgrade Notice ==

= 1.0.0 =
* Initial release.

= 1.1.0 =
* Added WooCommerce Automated Testing feature.
* Shifted WooCommerce Automated Testing functions from function.php to class.
* Updated stock prevention feature.
* Updated default testing product name.

= 1.1.1 =
* Added conditions so WooCommerce Automated Testing feature does not load in the absence of WooCommerce.

= 1.1.2 =
* Added conditions so WooCommerce Automated Testing feature does not load in the absence of WooCommerce.

= 1.1.3 =
* Removed Cache from getformresults endpoint.
* Updated testing product name.
* Added auto cache refresh on plugin's update.

= 1.1.4 =
* Re-introduced registerformtest endpoint.
* Updated default emails sending address for [CheckView](https://checkview.io/).

= 1.1.5 =
* Resolved WooCommerce admin emails disabling issues.
* Updated dependencies area.
* Updated default emails sending address for WooCommerce emails to [CheckView](https://checkview.io/).
* Added POST call support to delete order endpoint.
* Added order id support to delete order endpoint.

= 1.1.6 =
* Declared compatibility with WooCommerce HPOS.
* Updated plugin's name.
* Updated payment gateway name.

= 1.1.7 =
* Added unit tests.
* Resolved php memory limit issue while retrieving orders.
* Updated single order endpoint to accept all requests.

= 1.1.8 =
* Resolved token validation issue.

= 1.1.9 =
* Added dimensions for Test Product.

= 1.1.10 =
* Resolved Cloudflare Turnstile bypass error with WPForms.
* Resolved Google Recaptcha bypass error with FluentForms.

= 1.1.11 =
* Added compatibility with FluentForms 5.1.19.
* Updated email filter hook for admin email notification of FluentForms.
* Updated email action hook for form submission of FluentForms.

= 1.1.12 =
* Added Google Recaptcha, hCaptcha and Cloudflare Turnstile bypass in Gravity Forms.
* Added Google Recaptcha V3 bypass in FluentForms.
* Updated CheckView email address to divert admin notifications accross all forms.
* Added Cloudflare Turnstile bypass in WooCommerce checkout.

= 1.1.13 =
* Added spam check bypass in all forms and WooCommerce.

= 1.1.14 =
* Added hCaptcha spam bypass in all forms.
* Added Google Recaptcha V3 bypass in Gravity Forms.
* Added hCaptcha spam bypass in WooCommerce checkout.
* Added Google Recaptcha V3 bypass in Ninja forms ( addon based ).