<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Retrieve stored values
$src = get_option('all_trips_src', '');
$slug = get_option('all_trips_slug', '');
$env = get_option('all_trips_env', 'https://pre.wetravel.to'); // Default value
?>
<?php
// Output the script only if a slug exists
if (!empty($slug)) :
?>
    <div>
        <script src="<?php echo esc_attr($src); ?>"
                id="wetravel_listing"
                data-env="<?php echo esc_attr($env); ?>"
                data-slug="<?php echo esc_attr($slug); ?>"
                data-color="33ae3f"
                data-text="Details"
                data-name="My Trips">
        </script>
    </div>
<?php
endif;
?>
