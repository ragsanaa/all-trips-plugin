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

  // Add custom CSS for the display type
  $custom_css = "
    :root {
      --button-color: {$buttonColor};
    }

    /* General container styles */
    .wp-block-all-trips-block {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;
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
      transition: background-color 0.3s ease;
      font-weight: 500;
    }

    .trip-item .trip-button:hover {
      opacity: 0.9;
    }

    #load-more-button-{$block_id} {
      background-color: {$buttonColor};
      color: white;
      padding: 10px 20px;
      border-radius: 4px;
      cursor: pointer;
      border: none;
      text-align: center;
      display: block;
      width: 200px;
      margin: 20px auto;
      font-weight: 500;
      transition: background-color 0.3s ease;
    }

    #load-more-button-{$block_id}:hover {
      opacity: 0.9;
    }

    /* Grid view styles */
    .all-trips-container.grid-view {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
      gap: 15px;
    }

    .all-trips-container.grid-view .trip-item {
      display: flex;
      flex-direction: column;
    }

    .all-trips-container.grid-view img {
      height: 160px;
      object-fit: cover;
      border-radius: 4px 4px 0 0;
      width: 100%;
    }

    /* Vertical view styles */
    .all-trips-container.vertical-view .trip-item {
      margin-bottom: 15px;
      display: grid;
      grid-template-columns: 3fr 4fr 2fr;
      gap: 15px;
      align-items: center;
    }

    .all-trips-container.vertical-view img {
      height: 100%;
      width: 100%;
      object-fit: cover;
      border-radius: 4px;
      max-height: 180px;
    }

    .all-trips-container.vertical-view .trip-content {
      padding: 0;
    }

    .all-trips-container.vertical-view .trip-price-button {
      display: flex;
      flex-direction: column;
      align-items: flex-start;
    }

    /* Carousel view styles */
    .all-trips-container.carousel-view .swiper {
      padding: 10px 5px 40px;
    }

    .all-trips-container.carousel-view .swiper-slide {
      height: auto;
    }

    .all-trips-container.carousel-view .swiper-pagination {
      bottom: 0;
    }

    .all-trips-container.carousel-view .swiper-button-next,
    .all-trips-container.carousel-view .swiper-button-prev {
      color: {$buttonColor};
    }

    /* Trip item shared styles */
    .trip-item {
      box-sizing: border-box;
      border: 1px solid #ddd;
      border-radius: 4px;
      background-color: #fff;
      box-shadow: 0 2px 4px rgba(0,0,0,0.05);
      overflow: hidden;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .trip-item:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    .trip-item h3 {
      margin-top: 0;
      margin-bottom: 8px;
      font-size: 18px;
      color: #333;
    }

    .trip-content {
      padding: 15px;
      flex-grow: 1;
      display: flex;
      flex-direction: column;
    }

    .trip-description {
      color: #666;
      font-size: 14px;
      margin-bottom: 8px;
      flex-grow: 1;
    }

    .trip-date, .trip-duration {
      font-weight: 500;
      font-size: 14px;
      color: #555;
      margin-bottom: 8px;
    }

    .trip-price {
      font-weight: bold;
      font-size: 16px;
      color: #333;
      margin-bottom: 10px;
    }

    .no-image-placeholder {
      height: 180px;
      background-color: #f0f0f0;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #888;
      border-radius: 4px;
      font-size: 14px;
    }

    .trip-item.vertical-view {
      display: grid;
      gap: 15px;
      grid-template-columns: 3fr 4fr 2fr;
    }

    /* Handle responsive layout */
    @media screen and (max-width: 768px) {
      .all-trips-container.vertical-view .trip-item {
        grid-template-columns: 1fr;
        gap: 10px;
      }

      .all-trips-container.grid-view {
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
      }

      .trip-item h3 {
        font-size: 16px;
      }
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
                    <div class="no-image-placeholder">
                      <span>No Image Available</span>
                    </div>
                  <?php endif; ?>
                    <div class="trip-content">
                      <h3><?php echo esc_html($trip['title']); ?></h3>

                      <?php if (!empty($trip['full_description'])): ?>
                        <div class="trip-description"><?php echo wp_trim_words(esc_html($trip['full_description']), 15, '...'); ?></div>
                      <?php endif; ?>

                      <?php if (!empty($trip['startDate'])): ?>
                        <div class="trip-date"><?php echo esc_html(date('M j, Y', strtotime($trip['startDate']))); ?></div>
                      <?php elseif (!empty($trip['duration'])): ?>
                        <div class="trip-duration"><?php echo esc_html($trip['duration']); ?> days</div>
                      <?php endif; ?>

                      <?php if (!empty($trip['price'])): ?>
                        <div class="trip-price">From <?php echo esc_html($trip['price']['currencySymbol'] . $trip['price']['amount']); ?></div>
                      <?php endif; ?>

                      <div class="trip-button-container">
                        <?php
                          // Update the button URL logic
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
            <div class="trip-item <?php echo esc_attr($displayType); ?>-view" style="display: <?php echo $display_style; ?>">
              <?php if ($displayType === 'vertical'): ?>
                <!-- Vertical layout -->
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
                    <div class="trip-price">From <?php echo esc_html($trip['price']['currencySymbol'] . $trip['price']['amount']); ?></div>
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

              <?php else: ?>
                <!-- Grid layout -->
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
                    <div class="trip-description"><?php echo wp_trim_words(esc_html($trip['full_description']), 15, '...'); ?></div>
                  <?php endif; ?>

                  <?php if (!empty($trip['startDate'])): ?>
                    <div class="trip-date"><?php echo esc_html(date('M j, Y', strtotime($trip['startDate']))); ?></div>
                  <?php elseif (!empty($trip['duration'])): ?>
                    <div class="trip-duration"><?php echo esc_html($trip['duration']); ?> days</div>
                  <?php endif; ?>

                  <?php if (!empty($trip['price'])): ?>
                    <div class="trip-price">From <?php echo esc_html($trip['price']['currencySymbol'] . $trip['price']['amount']); ?></div>
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
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="no-trips">No trips found</div>
        <?php endif; ?>
      <?php endif; ?>
    </div>

    <?php if ($displayType !== 'carousel' && !empty($trips) && count($trips) > $itemsPerPage): ?>
      <button id="load-more-button-<?php echo esc_attr($block_id); ?>" class="load-more-button">
        <?php echo esc_html($loadMoreText); ?>
      </button>
    <?php endif; ?>
  </div>
  <?php
  return ob_get_clean();
}
