=== WeTravel Widgets ===
Contributors: wtragsana
Tags: travel, widget, trips, booking, wetravel
Requires at least: 5.0
Tested up to: 6.8
Stable tag: 1.0
Requires PHP: 7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Customize WeTravel's embedded widgets with your own styles and layouts. Create beautiful, branded travel displays that match your website design.

== Description ==

The WeTravel Widgets plugin allows you to customize WeTravel's embedded widgets to seamlessly match your WordPress website's design. Create multiple widget designs, save them for reuse, and easily embed them anywhere using shortcodes or Gutenberg blocks.

**Key Features:**

* **Widget Customization** - Customize WeTravel's embedded widgets with your own styles and layouts
* **Multiple Designs** - Create and save multiple widget designs for different pages or sections
* **Layout Options** - Choose between vertical, grid, or carousel layouts
* **Button Customization** - Customize button colors, text, and behavior
* **Visual Design Library** - Manage all your widget designs in one place
* **Easy Implementation** - Use shortcodes or Gutenberg blocks to place widgets anywhere
* **Responsive Design** - Looks great on all devices from mobile phones to desktop computers
* **Live Preview** - See your customizations in real-time while editing
* **Design Reusability** - Save your designs with unique keywords for easy reference

The plugin connects directly to the WeTravel API to ensure your trip information is always up-to-date. When you update trip details, prices, or availability on WeTravel, changes will automatically reflect on your website.
Perfect for travel agencies, tour operators, and any business using WeTravel who wants to customize their widget appearance to match their brand.

== External Services ==

This plugin connects to WeTravel's services for two main purposes:

1. **Trip Information Retrieval**
   - The plugin fetches trip data from WeTravel's API (api.wetravel.com) to display your trips on your website
   - The only configuration needed is the identification information extracted from the WeTravel embed code that you provide in the plugin settings
   - Data retrieved includes trip details, pricing, availability, and SEO configuration
   - This data is fetched whenever a widget is loaded or refreshed on your site
   - No personal user data is sent during this process - only the necessary identification data parsed from your provided embed code

2. **Booking Widget Integration**
   - When users interact with booking buttons, the plugin loads WeTravel's embed checkout functionality
   - The integration uses only the necessary identification data extracted from the embed code you provided in the WordPress admin settings
   - During the booking process, users may provide personal information directly to WeTravel (not through your WordPress site)
   - The checkout process is handled entirely by WeTravel's secure platform
   - User data collected during booking is subject to WeTravel's privacy policy

For more information about how WeTravel handles data and provides services, please review:
- Terms of Service: https://www.wetravel.com/terms
- Privacy Policy: https://www.wetravel.com/privacy

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/wetravel-widgets` directory, or install the plugin through the WordPress plugins screen directly
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to WeTravel Widgets > Settings to configure your WeTravel embed code
4. Create your first widget design in WeTravel Widgets > Create Widget
5. Use the generated shortcode or Gutenberg block to display your customized widget

== Frequently Asked Questions ==

= How do I create a custom widget design? =

Go to WeTravel Widgets > Create Widget in your WordPress admin. You can customize the layout, button style, colors, and more. Save your design and use the generated shortcode to display it.

= Can I create multiple widget designs? =

Yes! You can create as many widget designs as you need. Each design can have its own layout, style, and settings. Access all your designs in the Widget Library.

= What customization options are available? =

You can customize:
* Layout type (vertical, grid, or carousel)
* Button style and color
* Button text
* Number of items per page/row/slide
* Trip types (all, one-time, or recurring)
* Date ranges for trips
* And more!

= How do I use my custom widget designs? =

You can use either:
1. Shortcode: Copy the generated shortcode and paste it in any post or page
2. Gutenberg Block: Add the "WeTravel Trips Block" and select your saved design

= Can I still use the default WeTravel widget? =

Yes! This plugin enhances WeTravel's embedded widgets with customization options. You can still use the default widget if you prefer.

== Screenshots ==

1. Widget Library showing multiple saved designs (/assets/screenshots/screenshot-1.png)
2. Widget customization interface with live preview (/assets/screenshots/screenshot-2.png)
3. Different layout options (vertical, grid, carousel) (/assets/screenshots/screenshot-3.png)
4. Gutenberg block interface (/assets/screenshots/screenshot-4.png)
5. Mobile view of customized widgets (/assets/screenshots/screenshot-5.png)

== Changelog ==

= 1.0 =
* Initial release with core functionality
* Widget customization options
* Design library
* Shortcode and Gutenberg block support
* Multiple layout options
* Button customization
* Live preview feature

== Upgrade Notice ==

= 1.0 =
Initial release of the WeTravel Widgets plugin. Customize your WeTravel widgets to match your website's design.

== Shortcode Parameters ==

The `[wetravel_trips]` shortcode accepts the following parameters:

* `widget` - The ID or keyword of your saved widget design
* `display_type` - Layout style: "vertical", "grid", or "carousel" (default: "vertical")
* `button_type` - Type of button: "book_now" or "view_trip"
* `button_text` - Custom text for the button
* `button_color` - Color of the button (hex code, e.g., "#33ae3f")
* `items_per_page` - Number of trips to display per page (default: 10)
* `items_per_row` - Number of trips to display per row in grid layout (default: 3)
* `items_per_slide` - Number of trips to display per slide in carousel layout (default: 3)
* `trip_type` - Filter trips by type: "all", "one-time", or "recurring"
* `date_start` - Start date for filtering trips (format: YYYY-MM-DD)
* `date_end` - End date for filtering trips (format: YYYY-MM-DD)

Example with a saved design:
`[wetravel_trips widget="my-custom-design"]`

Example with custom parameters:
`[wetravel_trips display_type="carousel" items_per_slide="3" button_color="#ff0000" button_text="Book Now"]`
