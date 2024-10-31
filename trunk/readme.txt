=== Paid Pages with WooCommerce ===
Contributors: xsid
Donate link: https://money.yandex.ru/to/410011748700866
Tags: paid page access, woocommerce, subscriptions
Requires at least: 5.0
Tested up to: 5.4.0
Stable tag: trunk
Requires PHP: 5.6.0
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

The plugin allows you to create paid subscriptions and organize paid access to pages.

== Description ==

The plugin creates WooCommerce products which gives access to the specified WordPress pages.

* The plugin works on the basis of **WooCommerce** products, therefore the installation of **WooCommerce** is required.
* There is no paid version of the plugin, it is completely FREE.
* **April 2020 Update** Now you can customize the layout in any way you want. Each element has its own class:
`<div class="ppwc">`
`  <h1 class="ppwc-msg-header">This is the message header</h1>`
`  <h3 class="ppwc-msg-header">This is the message body</h3>`
`  <ul class="ppwc-plan-list">`
`    <li class="ppwc-plan-item"><a href="#">This is the plan item</a></li>
`  </ul>`
`</div>`

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/plugin-name` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Use the Subscription Plans menu to add new plan
1. Apply plan on any page or article which have the_content() function inside.

== Frequently Asked Questions ==

= Will this plugin work on all pages? =

Not exactly. Only on pages which have the_content() function in it's template.

== Screenshots ==

1. Create your plan.
2. Plugin will create WooCommerce products based on the previously entered data.
3. Apply your plan on the desired page.
4. Check access through new private browser window (admin always has access to paid pages).

== Changelog ==

= 0.2.4 =
* added classes to wrapper, message header, message body, plans list, plan item to give possibility to customize them with css rules

= 0.2.3 =
* fix detecting botstrap 4 wrapper setting

= 0.2.2 =
* no changes

= 0.2.1 =
* bug fixes

== Upgrade Notice ==

= 0.2.4 =
Added classes to wrapper, message header, message body, plans list and plan item to give possibility to customize them with css rules

= 0.2.3 =
Fix detecting BS4 wrapper setting

= 0.2.2 =
Latest WP and WC compatibility tested

= 0.2.1 =
This version fixes a security related bug.
