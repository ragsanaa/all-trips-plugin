=== WeTravel Trips Widget ===
Contributors: developer60006
Donate link: https://wetravel.com
Tags: travel, widget, trips, booking, wetravel
Requires at least: 5.0
Tested up to: 6.7.2
Stable tag: 1.0
Requires PHP: 7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Display your WeTravel trips beautifully on your WordPress site with a customizable widget that helps convert visitors into travelers.

== Description ==

The WeTravel Trips Widget allows travel operators, tour companies, and trip organizers to seamlessly showcase their WeTravel trips directly on their WordPress website.

**Key Features:**

* **Display Trips** - Automatically fetch and display all your active trips from your WeTravel account
* **Customizable Layout** - Choose between grid, list, or carousel display options
* **Filtering Options** - Let visitors filter trips by date, price, destination, or category
* **Responsive Design** - Looks great on all devices from mobile phones to desktop computers
* **Easy Setup** - Simple configuration with your WeTravel API key
* **Shortcode Support** - Place your trips anywhere with shortcodes
* **Custom CSS Option** - Style the widget to match your website's design
* **SEO Friendly** - Structured data for better search engine visibility

The plugin connects directly to the WeTravel API to ensure your trip information is always up-to-date. When you update trip details, prices, or availability on WeTravel, changes will automatically reflect on your website.

Perfect for travel agencies, tour operators, adventure companies, retreat organizers, and any business that uses WeTravel for bookings and payments.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/wetravel-wetravel-trips-widget` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to Settings > WeTravel Widget to configure the plugin
4. Enter your WeTravel API key (found in your WeTravel account settings)
5. Configure display options as desired
6. Use the widget in your sidebar, or place the shortcode `[wetravel_trips]` in any page or post

== Frequently Asked Questions ==

= Where do I find my WeTravel API key? =

Log into your WeTravel account, go to Account Settings > API Integration. If you don't see an API key, you may need to contact WeTravel support to enable API access for your account.

= Can I customize how the trips are displayed? =

Yes! The plugin offers three layout options (grid, list, carousel), and you can adjust the number of trips shown, sorting order, and filter options. You can also add custom CSS in the settings panel.

= Do I need to manually update trip information? =

No, the plugin automatically syncs with your WeTravel account. Any changes you make to trips in WeTravel will be reflected on your website.

= Can I show only specific trips? =

Yes, you can use the shortcode with parameters to filter trips. For example:
`[wetravel_trips category="hiking" limit="4" featured="true"]`

= Is the widget mobile-friendly? =

Absolutely! The WeTravel Trips Widget is fully responsive and will look great on all devices.

= Does this plugin slow down my website? =

The plugin is designed to be lightweight. It caches trip data to minimize API calls and includes only the necessary scripts and styles to function properly.

== Screenshots ==

1. Grid layout displaying multiple trips with featured images
2. Admin settings panel for easy configuration
3. Filter and search functionality for visitors
4. Mobile view of the trips widget
5. Shortcode parameters and examples

== Changelog ==

= 1.0 =
* Initial release with core functionality
* Three layout options: grid, list, and carousel
* Filtering and sorting capabilities
* Responsive design for all devices
* Shortcode support with parameters
* Custom CSS option

== Upgrade Notice ==

= 1.0 =
Initial release of the WeTravel Trips Widget. Showcase your WeTravel trips beautifully on your WordPress site.

== Custom Shortcode Parameters ==

The `[wetravel_trips]` shortcode accepts the following parameters:

* `layout` - Choose display style: "grid", "list", or "carousel" (default: "grid")
* `limit` - Number of trips to display (default: 9)
* `category` - Filter by trip category (e.g., "hiking", "cultural")
* `destination` - Filter by destination (e.g., "bali", "costa-rica")
* `featured` - Show only featured trips: "true" or "false"
* `sort` - Sort order: "date", "price-low", "price-high", "popularity" (default: "date")
* `columns` - Number of columns in grid layout: 1-4 (default: 3)

Example:
`[wetravel_trips layout="carousel" limit="6" featured="true" sort="popularity"]`
