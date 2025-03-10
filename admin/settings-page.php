<?php
// Admin settings page for All Trips Plugin

if (!defined('ABSPATH')) {
    exit;
}

function all_trips_register_settings() {
    register_setting('all_trips_options', 'all_trips_embed_code');
    register_setting('all_trips_options', 'all_trips_display_type');
    register_setting('all_trips_options', 'all_trips_button_type');
    register_setting('all_trips_options', 'all_trips_button_color');
    register_setting('all_trips_options', 'all_trips_items_per_page');
    register_setting('all_trips_options', 'all_trips_load_more_text');
}
add_action('admin_init', 'all_trips_register_settings');

function all_trips_settings_page() {
    ?>
    <div class="wrap">
        <h1>All Trips Plugin Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('all_trips_options');
            do_settings_sections('all_trips_options');
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th><label for="all_trips_embed_code">Embed Code</label></th>
                    <td>
                        <textarea id="all_trips_embed_code" name="all_trips_embed_code" class="large-text code" rows="5"><?php echo esc_textarea(get_option('all_trips_embed_code', '')); ?></textarea>
                        <p class="description">Paste your WeTravel embed script here. The plugin will extract the necessary details automatically.</p>
                    </td>
                </tr>
                <tr valign="top"><th><h2 style="margin:0">Default Settings</h2></th></tr>
                <tr valign="top">
                    <th><label for="all_trips_display_type">Trip Display Type</label></th>
                    <td>
                        <select id="all_trips_display_type" name="all_trips_display_type">
                            <option value="vertical" <?php selected(get_option('all_trips_display_type', 'vertical'), 'vertical'); ?>>Vertical</option>
                            <option value="carousel" <?php selected(get_option('all_trips_display_type', 'vertical'), 'carousel'); ?>>Carousel</option>
                            <option value="grid" <?php selected(get_option('all_trips_display_type', 'vertical'), 'grid'); ?>>Grid</option>
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th><label for="all_trips_button_type">Button Type</label></th>
                    <td>
                        <select id="all_trips_button_type" name="all_trips_button_type">
                            <option value="book_now" <?php selected(get_option('all_trips_button_type', 'book_now'), 'book_now'); ?>>Book Now</option>
                            <option value="trip_link" <?php selected(get_option('all_trips_button_type', 'book_now'), 'trip_link'); ?>>Trip Link</option>
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th><label for="all_trips_button_color">Button Color</label></th>
                    <td>
                        <input type="color" id="all_trips_button_color" name="all_trips_button_color" value="<?php echo esc_attr(get_option('all_trips_button_color', '#33ae3f')); ?>">
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Items Per Page:</th>
                    <td>
                        <input type="number" name="all_trips_items_per_page" value="<?php echo esc_attr(get_option('all_trips_items_per_page', 10)); ?>" min="1" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Load More Button Text:</th>
                    <td>
                        <input type="text" name="all_trips_load_more_text" value="<?php echo esc_attr(get_option('all_trips_load_more_text', 'Load More')); ?>" />
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

function all_trips_add_admin_menu() {
    add_menu_page(
        'All Trips Plugin Settings',
        'All Trips',
        'manage_options',
        'all-trips-settings',
        'all_trips_settings_page'
    );
}
add_action('admin_menu', 'all_trips_add_admin_menu');


/// Enqueue scripts for pagination
function all_trips_enqueue_pagination_scripts() {
    wp_enqueue_script(
        'all-trips-pagination',
        plugins_url('assets/js/pagination.js', __FILE__),
        array('jquery'),
        null,
        true
    );
    wp_localize_script('all-trips-pagination', 'allTripsSettings', array(
        'itemsPerPage' => get_option('all_trips_items_per_page', 10),
        'loadMoreText' => get_option('all_trips_load_more_text', 'Load More')
    ));
}
add_action('wp_enqueue_scripts', 'all_trips_enqueue_pagination_scripts');
