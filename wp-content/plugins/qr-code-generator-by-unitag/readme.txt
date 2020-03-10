=== QR Code generator by Unitag ===
Contributors: Unitag
Tags: qr code, generator, shortcode, design, qrcode, qrcodes, flashcode, qr code generator, visual qr code, scan, images, mobile, mobile marketing 
Requires at least: 3.0.1 / Requires Wordpress: 3.0.1 and up 
Tested up to: 3.6
Stable tag: 4.5
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Easily create design QR Codes in Wordpress with shortcodes.

== Description ==
QR Code generator Wordpress plugin allows you to create custom QR Codes very easily using shortcodes.

You can use one of our ready-to-use templates or design your own on [Unitag website](https://www.unitag.io/). All types of QR Code are available : URL, text, geolocation, SMS, Wi-Fi, vCard, e-mail, calendar or phone.

How does it work? Just use shortcode in your content to add the QR Code. For example:

* `[qr type="url" url="http://www.wordpress.com"]`
* `[qr type="geo" geo_lat="43.6075329" geo_long="1.4512750"]`
* `[qr type="card" card_name="Matt Mullenweg" card_firm="Wordpress" card_url="http://www.wordpress.org/"]`

Please check the complete parameter list available on the Settings page. To avoid adding basic parameters (e.g. QR Code template or size) in your shortcode each time you create a QR Code, you also have the possibility to set default values on top of your Settings page.  

== Installation ==
1. Upload `unitag` folder to the `/wp-content/plugins/` directory
1. Activate the plugin via the 'Plugins' menu in WordPress
1. Configure your settings on the plugin page
1. ... you are now ready to create design QR Codes on your Wordpress :-) 

== Frequently Asked Questions ==
= Do I have to subscribe to Unitag website to design my QR Codes ? =
Not at all. You can find a lot of default templates directly available without any subscription. Subscribing to Unitag will give you access to the advanced QR Code Generator to create your very own QR Code templates - with a wide range of colors and shapes, as well as logo overlay functionnality. To do so, you'll just have to [create a free account](https://www.unitag.io/get-started) and copy/paste the appropriate template ID in your Wordpress plugin.

= Which parameters are required for the shortcode ? =
You only need the type of QR Code and your code content in you shortcode. Just check out your Settings page for more details: we listed there all content parameters for you, depending on the type of QR Code. For instance, to add a QR Code connecting to a specific URL, you'll just have to set `type="url"` and `url="http://www.myurl.com"`.

== Screenshots ==
1. Settings page with QR Code templates
2. The result in an article

== Changelog ==

= 0.1c =
* Small fix: size was uneffective, now add style="width:'.$size.'px;" in HTML markup

= 0.1b =
* Important fix: can now use several shortcodes in the same page

= 0.1 =
* Initial release.

== Upgrade Notice ==

= 0.1 =
* Initial release.