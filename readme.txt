=== Techsoul Project Calculator (TSPC) ===
Contributors: partho800
Donate link: https://github.com/Partho800/project-calculator-Pluging
Tags: cost calculator, price calculator, service calculator, project estimate, quote calculator, cost estimate
Requires at least: 5.6
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.26
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A premium, interactive service price calculator featuring dynamic checklists, tier discounts, modern quote request form, and AJAX lead management.

== Description ==

**Techsoul Project Calculator (TSPC)** is a modern, highly interactive WordPress service price calculator plugin designed for digital agencies, software companies, freelancers, and service providers.

It allows your clients to choose services and add-ons dynamically, calculates total costs with optional tiered volume discounts and package discounts in real-time, and captures leads directly through an integrated AJAX quote request form.

### Key Features

* **Interactive Frontend Calculator**: Smooth selection of parent services, sub-services, and nested child modules with tree branch indicators.
* **Dynamic Package & Tier Discounts**: Reward clients with automatic percentage discounts when all sub-services or a certain number of services are chosen.
* **Modern Quote Request Form**: Clean, responsive inquiry form with name, email, phone, and project message fields.
* **Custom Color System**: Seamlessly customize your Primary Accent Color from WordPress admin to match your site's branding.
* **Lead Inquiries Dashboard**: View, filter, and manage client estimate submissions directly from your WordPress admin dashboard.
* **Email Notifications**: Automatically send instant quote summary emails to both admin and client upon form submission.
* **Fully Responsive**: Optimized for desktop, tablets, and smartphones.
* **Easy Embed**: Simply use the shortcode `[tspc_calculator]` on any page or post.

== Installation ==

1. Upload the `tspc` folder to the `/wp-content/plugins/` directory, or install through the WordPress Plugins screen directly.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. Navigate to **TSPC Calculator** in the WordPress admin menu to configure your currency, primary accent color, services, and discount tiers.
4. Add the `[tspc_calculator]` shortcode to any WordPress page, post, or widget area.

== Frequently Asked Questions ==

= How do I display the calculator on my site? =
Place the `[tspc_calculator]` shortcode on any page or post where you want the calculator to appear.

= Can I add sub-services and nested child items? =
Yes! In the admin dashboard, each service supports unlimited sub-services with custom prices and optional nested child add-ons.

= How do package discounts work? =
You can set a discount percentage for a service. When a user selects all available sub-services for that service, the package discount is automatically applied to the total.

= Can I change the currency symbol? =
Yes, you can configure your currency symbol (e.g. $, €, £, ৳) in the plugin settings.

== Screenshots ==

1. Frontend interactive service price calculator with modern tree hierarchy.
2. Live cost summary sidebar with subtotal, discount savings, and total amount.
3. WordPress admin dashboard for managing services, sub-services, and settings.
4. Lead inquiries management table with detailed client submissions.

== Changelog ==

= 1.0.26 =
* Optimized selected services summary to display 2-3 items with a '+X more' indicator for cleaner readability.
* Preserved complete sub-services selection in item hover tooltips and admin lead inquiry records.

= 1.0.25 =
* Added modern tree hierarchy layout for nested child sub-services with dynamic branch connector lines.
* Enhanced Primary Accent Color integration across sub-services, checkboxes, badges, and total card.
* Redesigned cost summary sidebar with sleek Total card and streamlined "Request a Quote" form.
* Improved form field validation with smooth focus handling.
* Performance optimizations and WordPress coding standard enhancements.

= 1.0.0 =
* Initial public release of Techsoul Project Calculator.

== Upgrade Notice ==

= 1.0.25 =
This release includes modern UI enhancements, dynamic color theming, and improved responsiveness.
