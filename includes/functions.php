<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Function to extract slug and env from embed script
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

// Save settings function - Using the settings API to handle saving
function all_trips_save_settings($option, $old_value, $new_value) {
    if ($option === 'all_trips_embed_code') {
        $extracted_values = all_trips_extract_settings($new_value);
        // Save the extracted values into options
        update_option('all_trips_slug', $extracted_values['slug']);
        update_option('all_trips_env', $extracted_values['env']);
        update_option('all_trips_src', $extracted_values['src']);
    }
}
add_action('updated_option', 'all_trips_save_settings', 10, 3);
