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

  // IMPORTANT CHANGE: Fetch trips data here
  // Make sure fetch-trips.php functions are available
  require_once ALL_TRIPS_PLUGIN_DIR . 'includes/fetch-trips.php';

  // Get trips data using the function from fetch-trips.php
  $trips = get_wetravel_trips_data($api_url, $env);

  // If no trips found, set empty array
  if (empty($trips)) {
    $trips = array();
  }

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

  // Only add dynamic CSS that depends on block attributes
  $custom_css = "
    /* Set dynamic CSS variables for this block instance */
    #trips-container-{$block_id} {
      --button-color: {$buttonColor};
    }
  ";

  // Add design-specific custom CSS if available
  if (!empty($custom_css_design)) {
    $custom_css .= "\n/* Design-specific custom CSS */\n{$custom_css_design}";
  }

  // Output custom styles
  wp_add_inline_style('all-trips-styles', $custom_css);

  ob_start();
  ?>
  <div class="wp-block-all-trips-block">
    <div class="all-trips-container <?php echo esc_attr($displayType); ?>-view"
         id="trips-container-<?php echo esc_attr($block_id); ?>"
         data-items-per-page="<?php echo esc_attr($itemsPerPage); ?>"
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
                <?php
                    $button_url = $env . '/trips/' . $trip['uuid'];
                ?>
                <div class="swiper-slide">
                  <a class="trip-item" style="display: block;" href="<?php echo esc_url($button_url); ?>" target="_blank">
                    <?php if (!empty($trip['default_image'])): ?>
                      <img src="<?php echo esc_url($trip['default_image']); ?>" alt="<?php echo esc_attr($trip['title']); ?>">
                    <?php else: ?>
                      <div class="no-image-placeholder">
                        <span>No Image Available</span>
                      </div>
                    <?php endif; ?>
                    <div class="trip-content">
                      <h3><?php echo esc_html($trip['title']); ?></h3>
                      <?php if (!$trip['all_year']): ?>
                        <div class="trip-date"><?php echo esc_html($trip['start_end_dates']); ?></div>
                      <?php elseif (!empty($trip['custom_duration'])): ?>
                        <div class="trip-duration"><?php echo esc_html($trip['custom_duration']); ?> days</div>
                      <?php endif; ?>

                      <?php if (!empty($trip['price'])): ?>
                        <div class="trip-price">from <span><?php echo esc_html($trip['price']['currencySymbol'] . $trip['price']['amount']); ?></span></div>
                      <?php endif; ?>
                    </div>
                  </a>
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
            $visibility_class = ($index < $itemsPerPage) ? 'visible-item' : 'hidden-item';
          ?>
            <?php
              $button_url = $env . '/trips/' . $trip['uuid'];
            ?>

              <?php if ($displayType === 'vertical'): ?>
                <!-- Vertical layout -->
                <div class="trip-item <?php echo $visibility_class; ?>">
                  <?php if (!empty($trip['default_image'])): ?>
                    <img src="<?php echo esc_url($trip['default_image']); ?>" alt="<?php echo esc_attr($trip['title']); ?>">
                  <?php else: ?>
                    <div class="no-image-placeholder">
                      <span>No Image Available</span>
                    </div>
                  <?php endif; ?>

                  <div class="trip-content">
                    <h3><?php echo esc_html($trip['title']); ?></h3>

                    <?php if (!empty($trip['full_description'])): ?>
                      <div class="trip-description"><?php echo wp_trim_words(esc_html(strip_tags($trip['full_description'])), 20, '...'); ?></div>
                    <?php endif; ?>

                    <?php if (!$trip['all_year']): ?>
                      <div class="trip-date"><?php echo esc_html($trip['start_end_dates']); ?></div>
                    <?php elseif (!empty($trip['custom_duration'])): ?>
                      <div class="trip-duration"><?php echo esc_html($trip['custom_duration']); ?> days</div>
                    <?php endif; ?>
                  </div>

                  <div class="trip-price-button">
                    <?php if (!empty($trip['price'])): ?>
                      <div class="trip-price">from <br><span><?php echo esc_html($trip['price']['currencySymbol'] . $trip['price']['amount']); ?></span></div>
                    <?php endif; ?>

                    <?php
                      // Button URL logic
                      $button_url = '';
                      if ($buttonType === 'book_now') {
                        $button_url = $env . '/checkout_embed?uuid=' . $trip['uuid'];
                      } else {
                        $button_url = $env . '/trips/' . $trip['uuid'];
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
              <?php else: ?>
                <!-- Grid layout -->
                <a class="trip-item <?php echo $visibility_class; ?>" href="<?php echo esc_url($button_url); ?>" target="_blank" style="display: block;">
                  <?php if (!empty($trip['default_image'])): ?>
                    <img src="<?php echo esc_url($trip['default_image']); ?>" alt="<?php echo esc_attr($trip['title']); ?>">
                  <?php else: ?>
                    <div class="no-image-placeholder">
                      <span>No Image Available</span>
                    </div>
                  <?php endif; ?>

                  <div class="trip-content">
                    <h3><?php echo esc_html($trip['title']); ?></h3>
                    <?php if (!$trip['all_year']): ?>
                      <div class="trip-date"><?php echo esc_html($trip['start_end_dates']); ?></div>
                    <?php elseif (!empty($trip['custom_duration'])): ?>
                      <div class="trip-duration"><?php echo esc_html($trip['custom_duration']); ?> days</div>
                    <?php endif; ?>

                    <?php if (!empty($trip['price'])): ?>
                      <div class="trip-price">from <span><?php echo esc_html($trip['price']['currencySymbol'] . $trip['price']['amount']); ?></span></div>
                    <?php endif; ?>
                  </div>
                    </a>
              <?php endif; ?>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="no-trips">No trips found</div>
        <?php endif; ?>
      <?php endif; ?>
    </div>

    <?php if ($displayType !== 'carousel' && !empty($trips)): ?>
      <!-- Numbered pagination container - will be populated by JavaScript -->
      <div id="pagination-<?php echo esc_attr($block_id); ?>" class="all-trips-pagination"></div>
    <?php endif; ?>
  </div>
  <?php
  return ob_get_clean();
}
