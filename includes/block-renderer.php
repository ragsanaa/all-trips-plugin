<?php
// Render callback for dynamic block
function all_trips_block_render($attributes) {
  // Generate a unique ID for this block instance
  $block_id = wp_unique_id('wetravel-');

  // Check if there's a selected design and apply its settings
$designs = get_option('all_trips_designs', array());
$selected_design_id = isset($attributes['selectedDesignID']) ? $attributes['selectedDesignID'] : '';

// Start with block attributes
$src = $attributes['src'] ?? get_option('all_trips_src', '');
$slug = $attributes['slug'] ?? get_option('all_trips_slug', '');
$env = $attributes['env'] ?? get_option('all_trips_env', 'https://pre.wetravel.to');
$displayType = $attributes['displayType'] ?? get_option('all_trips_display_type', 'vertical');
$buttonType = $attributes['buttonType'] ?? get_option('all_trips_button_type', 'book_now');
$buttonColor = $attributes['buttonColor'] ?? get_option('all_trips_button_color', '#33ae3f');
$itemsPerPage = intval($attributes['itemsPerPage'] ?? get_option('all_trips_items_per_page', 10));
$loadMoreText = $attributes['loadMoreText'] ?? get_option('all_trips_load_more_text', 'Load More');

// Override with design settings if a design is selected
if (!empty($selected_design_id)) {
  // Handle both array and object format for designs
  $design = null;

  if (isset($designs[$selected_design_id])) {
    // Object format
    $design = $designs[$selected_design_id];
  } else {
    // Array format - find by ID
    foreach ($designs as $d) {
      if (isset($d['id']) && $d['id'] === $selected_design_id) {
        $design = $d;
        break;
      }
    }
  }

  // Apply design settings, keeping block attributes as fallbacks
  if ($design) {
    $displayType = isset($design['displayType']) ? $design['displayType'] : $displayType;
    $buttonType = isset($design['buttonType']) ? $design['buttonType'] : $buttonType;
    $buttonColor = isset($design['buttonColor']) ? $design['buttonColor'] : $buttonColor;

    // If the design has custom CSS, we'll add it later
    $custom_css_design = isset($design['customCSS']) ? $design['customCSS'] : '';

    // Check for buttonText in design
    if (!empty($design['buttonText'])) {
      $buttonText = $design['buttonText'];
    }
  }
}

  // Set default buttonText based on buttonType if not provided
  $default_button_text = $buttonType === 'book_now' ? 'Book Now' : 'View Trip';
  $buttonText = !empty($attributes['buttonText']) ? $attributes['buttonText'] : $default_button_text;

  // If design has buttonText, override the default
  if (!empty($selected_design_id) && isset($designs[$selected_design_id]['buttonText'])) {
    $buttonText = $designs[$selected_design_id]['buttonText'];
  }

  // Clean up the environment URL if needed
  $env = rtrim($env, '/');

  // Create API URL
  $api_url = "{$env}/api/v2/embeds/all_trips?slug={$slug}";

  // Enqueue necessary assets based on display type
  if ($displayType === 'carousel') {
    wp_enqueue_style(
      'swiper-css',
      'https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css',
      array(),
      null
    );
    wp_enqueue_script(
      'swiper-js',
      'https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js',
      array(),
      null,
      true
    );
    wp_enqueue_script(
      'all-trips-carousel',
      plugins_url('assets/js/carousel.js', dirname(__FILE__)),
      array('jquery', 'swiper-js'),
      filemtime(ALL_TRIPS_PLUGIN_DIR . 'assets/js/carousel.js'),
      true
    );
  } else {
    // Always enqueue pagination script for grid and vertical views
    wp_enqueue_script(
      'all-trips-pagination',
      plugins_url('assets/js/pagination.js', dirname(__FILE__)),
      array('jquery'),
      filemtime(ALL_TRIPS_PLUGIN_DIR . 'assets/js/pagination.js'),
      true
    );
  }

  // Add custom CSS for the display type
  $custom_css = "
    :root {
      --button-color: {$buttonColor};
    }
    /* Button styling */
    .trip-item .trip-button {
      background-color: {$buttonColor};
      color: white;
      padding: 8px 16px;
      border-radius: 4px;
      text-decoration: none;
      display: inline-block;
      margin-top: 10px;
      text-align: center;
      width: calc(100% - 20px);
      box-sizing: border-box;
    }

    #load-more-button-{$block_id} {
      background-color: {$buttonColor};
      color: white;
      padding: 8px 16px;
      border-radius: 4px;
      cursor: pointer;
      border: none;
      text-align: center;
      display: block;
      width: 200px;
      margin: 20px auto;
    }

    /* Display type specific styles */
    .all-trips-container.grid-view {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
      gap: 15px;
    }

    .all-trips-container.vertical-view .trip-item {
      margin-bottom: 15px;
    }

    /* Equal height cards */
    .trip-item {
      display: flex;
      flex-direction: column;
      height: 100%;
      box-sizing: border-box;
      border: 1px solid #ddd;
      border-radius: 4px;
      padding: 15px;
      background-color: #fff;
    }

    .trip-item h3 {
      flex-grow: 0;
      margin-top: 10px;
    }

    .trip-item .trip-content {
      flex-grow: 1;
      display: flex;
      flex-direction: column;
    }

    .trip-item .trip-button-container {
      margin-top: auto;
      width: 100%;
    }

    .trip-item img {
      width: 100%;
      border-radius: 4px;
      height: 180px;
      object-fit: cover;
    }
  ";

  // Add design-specific custom CSS if available
  if (!empty($custom_css_design)) {
    $custom_css .= "\n/* Design-specific custom CSS */\n{$custom_css_design}";
  }

  // Output custom styles
  wp_add_inline_style('all-trips-styles', $custom_css);

  // Get trips data
  // $trips = all_trips_get_trips_data($api_url);

  ob_start();
  ?>
  <div class="wp-block-all-trips-block">
    <div class="all-trips-container <?php echo esc_attr($displayType); ?>-view"
         id="trips-container-<?php echo esc_attr($block_id); ?>"
         data-items-per-page="<?php echo esc_attr($itemsPerPage); ?>"
         data-load-more-text="<?php echo esc_attr($loadMoreText); ?>"
         data-display-type="<?php echo esc_attr($displayType); ?>"
         data-button-color="<?php echo esc_attr($buttonColor); ?>"
         <?php if (!empty($selected_design_id)) : ?>
         data-design="<?php echo esc_attr($selected_design_id); ?>"
         <?php endif; ?>>
      <?php if ($displayType === 'carousel'): ?>
        <div class="swiper">
          <div class="swiper-wrapper">
            <?php if (!empty($trips)): ?>
              <?php foreach ($trips as $trip): ?>
                <div class="swiper-slide">
                  <div class="trip-item">
                  <?php if (!empty($trip['default_image'])): ?>
                    <img src="<?php echo esc_url($trip['default_image']); ?>" alt="<?php echo esc_attr($trip['title']); ?>">
                  <?php else: ?>
                    <div style="height: 180px; background-color: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #888; border-radius: 4px;">
                      <span>No Image Available</span>
                    </div>
                  <?php endif; ?>
                    <div class="trip-content">
                      <h3><?php echo esc_html($trip['title']); ?></h3>
                      <?php if (!empty($trip['startDate'])): ?>
                        <div class="trip-date"><?php echo esc_html(date('M j, Y', strtotime($trip['startDate']))); ?></div>
                      <?php endif; ?>
                      <?php if (!empty($trip['price'])): ?>
                        <div class="trip-price"><?php echo esc_html($trip['price']['currencySymbol'] . $trip['price']['amount']); ?></div>
                      <?php endif; ?>
                      <div class="trip-button-container">
                        <?php
                          // Update the button URL logic within the block-renderer.php file
                          $button_url = '';
                          if ($buttonType === 'book_now') {
                            $button_url = $env . '/checkout_embed?uuid=' . $trip['uuid'];
                          } else {
                            // For trip_link, we should link to the trip page directly
                            $button_url = $env . '/trips/' . $trip['uuid'];

                            // If we have a trip slug in the data, use it for better SEO
                            if (!empty($trip['href'])) {
                              $button_url = $trip['href'];
                            }
                          }
                        ?>
                        <a href="<?php echo esc_url($button_url); ?>" class="trip-button" target="_blank">
                          <?php echo esc_html($buttonText); ?>
                        </a>
                      </div>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="no-trips">No trips found</div>
            <?php endif; ?>
          </div>
          <div class="swiper-pagination"></div>
          <div class="swiper-button-next"></div>
          <div class="swiper-button-prev"></div>
        </div>
      <?php else: ?>
        <?php if (!empty($trips)): ?>
          <?php
          foreach ($trips as $index => $trip):
            // Set display style - all items visible initially but controlled by JS
            $display_style = ($index < $itemsPerPage) ? 'block' : 'none';
          ?>
            <div class="trip-item" style="display: <?php echo $display_style; ?>">
            <?php if (!empty($trip['default_image'])): ?>
              <img src="<?php echo esc_url($trip['default_image']); ?>" alt="<?php echo esc_attr($trip['title']); ?>">
            <?php else: ?>
              <div style="height: 180px; background-color: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #888; border-radius: 4px;">
                <span>No Image Available</span>
              </div>
            <?php endif; ?>
              <div class="trip-content">
                <h3><?php echo esc_html($trip['title']); ?></h3>
                <?php if (!empty($trip['startDate'])): ?>
                  <div class="trip-date"><?php echo esc_html(date('M j, Y', strtotime($trip['startDate']))); ?></div>
                <?php endif; ?>
                <?php if (!empty($trip['price'])): ?>
                  <div class="trip-price"><?php echo esc_html($trip['price']['currencySymbol'] . $trip['price']['amount']); ?></div>
                <?php endif; ?>
                <div class="trip-button-container">
                  <?php
                    // Update the button URL logic within the block-renderer.php file
                    $button_url = '';
                    if ($buttonType === 'book_now') {
                      $button_url = $env . '/checkout_embed?uuid=' . $trip['uuid'];
                    } else {
                      // For trip_link, we should link to the trip page directly
                      $button_url = $env . '/trips/' . $trip['uuid'];

                      // If we have a trip slug in the data, use it for better SEO
                      if (!empty($trip['href'])) {
                        $button_url = $trip['href'];
                      }
                    }
                  ?>
                  <a href="<?php echo esc_url($button_url); ?>" class="trip-button" target="_blank">
                    <?php echo esc_html($buttonText); ?>
                  </a>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="no-trips">No trips found</div>
        <?php endif; ?>
      <?php endif; ?>
    </div>

    <?php if ($displayType !== 'carousel' && !empty($trips) && count($trips) > $itemsPerPage): ?>
      <button id="load-more-button-<?php echo esc_attr($block_id); ?>">
        <?php echo esc_html($loadMoreText); ?>
      </button>
    <?php endif; ?>
  </div>
  <?php
  return ob_get_clean();
}

/**
 * Get trips data from WeTravel API
 */
// function all_trips_get_trips_data($api_url) {
//   // Try to get cached data first
//   $cache_key = 'wetravel_trips_' . md5($api_url);
//   $cached_data = get_transient($cache_key);

//   if (false !== $cached_data) {
//     return $cached_data;
//   }

//   // No cache, fetch from API
//   $response = wp_remote_get($api_url, array(
//     'timeout' => 15,
//     'headers' => array(
//       'Accept' => 'application/json'
//     )
//   ));

//   if (is_wp_error($response)) {
//     return array(); // Return empty array on error
//   }

//   $body = wp_remote_retrieve_body($response);
//   $data = json_decode($body, true);

//   // Check if we have valid data
//   if (!isset($data['trips']) || !is_array($data['trips'])) {
//     return array();
//   }

//   // Cache for 1 hour
//   set_transient($cache_key, $data['trips'], HOUR_IN_SECONDS);

//   return $data['trips'];
// }
