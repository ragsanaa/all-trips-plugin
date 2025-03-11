<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Function to extract slug, env, and src from the embed script
function all_trips_extract_settings($embed_code) {
    preg_match('/src="([^"]+)"/', $embed_code, $src_match);
    preg_match('/data-slug="([^"]+)"/', $embed_code, $slug_match);
    preg_match('/data-env="([^"]+)"/', $embed_code, $env_match);

    return [
        'src'  => isset($src_match[1]) ? $src_match[1] : '',
        'slug' => isset($slug_match[1]) ? $slug_match[1] : '',
        'env'  => isset($env_match[1]) ? $env_match[1] : ''
    ];
}

// Hook into 'admin_init' to process the settings update
function all_trips_save_embed_code() {
    if (isset($_POST['all_trips_embed_code'])) {
        check_admin_referer('all_trips_options-options'); // Verify nonce

        $new_embed_code = wp_unslash($_POST['all_trips_embed_code']);
        update_option('all_trips_embed_code', $new_embed_code);

        // Extract and save the details
        $extracted_values = all_trips_extract_settings($new_embed_code);
        update_option('all_trips_slug', $extracted_values['slug']);
        update_option('all_trips_env', $extracted_values['env']);
        update_option('all_trips_src', $extracted_values['src']);

        // Save the timestamp of the last update
        update_option('all_trips_last_saved', wp_date('F j, Y \a\t g:i a', current_time('timestamp')));

        // Redirect to prevent resubmission
        wp_redirect(add_query_arg('saved', 'true', admin_url('admin.php?page=all-trips-settings')));
        exit;
    }
}
add_action('admin_init', 'all_trips_save_embed_code');

