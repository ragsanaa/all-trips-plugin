<?php
// Admin Create Design page for All Trips Plugin

if (!defined('ABSPATH')) {
    exit;
}

function all_trips_create_design_page() {
    // Default values
    $design = array(
        'name' => '',
        'displayType' => 'vertical',
        'buttonType' => 'book_now',
        'buttonText' => 'Book Now',
        'buttonColor' => '#33ae3f',
        'keyword' => '',
        'tripType' => 'all',
        'dateRangeStart' => '',
        'dateRangeEnd' => '',
        'created' => time()
    );

    $editing = false;
    $design_id = '';

    // Check if we're editing an existing design
    if (isset($_GET['edit']) && !empty($_GET['edit'])) {
        $design_id = sanitize_text_field($_GET['edit']);
        $designs = get_option('all_trips_designs', array());

        if (isset($designs[$design_id])) {
            $design = $designs[$design_id];
            $editing = true;
        }
    }

    // Handle form submission
    if (isset($_POST['save_design'])) {
        // Validate keyword uniqueness if provided
        $keyword = sanitize_text_field($_POST['design_keyword']);
        $keyword_error = false;

        if (!empty($keyword)) {
            $designs = get_option('all_trips_designs', array());
            foreach ($designs as $id => $existing_design) {
                if (isset($existing_design['keyword']) && $existing_design['keyword'] === $keyword && $id !== $design_id) {
                    $keyword_error = true;
                    break;
                }
            }
        }

        if ($keyword_error) {
            $error_message = 'This keyword is already in use. Please choose a unique keyword.';
        } else {
            // Get date range values if trip type is one-time
            $date_range_start = '';
            $date_range_end = '';
            if ($_POST['trip_type'] === 'one-time') {
                $date_range_start = sanitize_text_field($_POST['date_range_start']);
                $date_range_end = sanitize_text_field($_POST['date_range_end']);
            }

            $new_design = array(
                'name' => sanitize_text_field($_POST['design_name']),
                'displayType' => sanitize_text_field($_POST['display_type']),
                'buttonType' => sanitize_text_field($_POST['button_type']),
                'buttonText' => sanitize_text_field($_POST['button_text']),
                'buttonColor' => sanitize_hex_color($_POST['button_color']),
                'keyword' => $keyword,
                'tripType' => sanitize_text_field($_POST['trip_type']),
                'dateRangeStart' => $date_range_start,
                'dateRangeEnd' => $date_range_end,
                'created' => $design['created'],
                'modified' => time()
            );

            $designs = get_option('all_trips_designs', array());

            // Generate a new ID if we're not editing
            if (!$editing) {
                $design_id = 'design_' . time() . '_' . mt_rand(1000, 9999);
            }

            $designs[$design_id] = $new_design;
            update_option('all_trips_designs', $designs);

            $success_message = $editing ? 'Design updated successfully.' : 'Design created successfully.';

            // Generate shortcode for user
            $shortcode = '[all_trips design="' . ($keyword ? $keyword : $design_id) . '"]';
        }
    }

    ?>
    <div class="wrap">
        <h1><?php echo $editing ? 'Edit Design' : 'Create New Design'; ?></h1>

        <div class="nav-tab-wrapper">
            <a href="?page=all-trips-settings" class="nav-tab">Settings</a>
            <a href="?page=all-trips-design-library" class="nav-tab">Design Library</a>
            <a href="?page=all-trips-create-design" class="nav-tab nav-tab-active">Create Design</a>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="notice notice-success is-dismissible">
                <p><?php echo esc_html($success_message); ?></p>
                <?php if (isset($shortcode)): ?>
                    <p>Use this shortcode to display your design: <code><?php echo esc_html($shortcode); ?></code></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="notice notice-error is-dismissible">
                <p><?php echo esc_html($error_message); ?></p>
            </div>
        <?php endif; ?>

        <div class="all-trips-create-design-container">
            <div class="all-trips-design-form-and-preview">
                <div class="all-trips-design-form">
                    <form method="post" action="">
                        <div class="all-trips-form-field">
                            <label for="design_name">Design Name <span style="color:red;">*</span></label>
                            <input type="text" id="design_name" name="design_name" value="<?php echo esc_attr($design['name']); ?>" required>
                            <p class="description">Give your design a name to help you identify it later.</p>
                        </div>

                        <div class="all-trips-form-field">
                            <label for="design_keyword">Design Keyword</label>
                            <input type="text" id="design_keyword" name="design_keyword" value="<?php echo isset($design['keyword']) ? esc_attr($design['keyword']) : ''; ?>">
                            <p class="description">Optional. Set a unique keyword to use in shortcode. If not provided, the design ID will be used.</p>
                        </div>

                        <div class="all-trips-form-field">
                            <label for="display_type">Trip Display Type</label>
                            <select id="display_type" name="display_type">
                                <option value="vertical" <?php selected($design['displayType'], 'vertical'); ?>>Vertical</option>
                                <option value="carousel" <?php selected($design['displayType'], 'carousel'); ?>>Carousel</option>
                                <option value="grid" <?php selected($design['displayType'], 'grid'); ?>>Grid</option>
                            </select>
                        </div>

                        <div class="all-trips-form-field">
                            <label for="trip_type">Trip Type</label>
                            <select id="trip_type" name="trip_type">
                                <option value="all" <?php selected(isset($design['tripType']) ? $design['tripType'] : 'all', 'all'); ?>>All Trips</option>
                                <option value="recurring" <?php selected(isset($design['tripType']) ? $design['tripType'] : '', 'recurring'); ?>>Recurring Trips</option>
                                <option value="one-time" <?php selected(isset($design['tripType']) ? $design['tripType'] : '', 'one-time'); ?>>One-Time Trips</option>
                            </select>
                        </div>

                        <div id="date-range-container" class="all-trips-form-field" style="display: none;">
                            <label>Date Range</label>
                            <div class="date-range-inputs">
                                <div>
                                    <label for="date_range_start">Start Date</label>
                                    <input type="date" id="date_range_start" name="date_range_start" value="<?php echo isset($design['dateRangeStart']) ? esc_attr($design['dateRangeStart']) : ''; ?>">
                                </div>
                                <div>
                                    <label for="date_range_end">End Date</label>
                                    <input type="date" id="date_range_end" name="date_range_end" value="<?php echo isset($design['dateRangeEnd']) ? esc_attr($design['dateRangeEnd']) : ''; ?>">
                                </div>
                            </div>
                        </div>

                        <div class="all-trips-form-field">
                            <label for="button_type">Button Type</label>
                            <select id="button_type" name="button_type">
                                <option value="book_now" <?php selected($design['buttonType'], 'book_now'); ?>>Book Now</option>
                                <option value="trip_link" <?php selected($design['buttonType'], 'trip_link'); ?>>Trip Link</option>
                            </select>
                        </div>

                        <div class="all-trips-form-field">
                            <label for="button_text">Button Text</label>
                            <input type="text" id="button_text" name="button_text" value="<?php echo esc_attr($design['buttonText']); ?>">
                        </div>

                        <div class="all-trips-form-field">
                            <label for="button_color">Button Color</label>
                            <input type="text" id="button_color" name="button_color" class="color-picker" value="<?php echo esc_attr($design['buttonColor']); ?>">
                        </div>

                        <div class="all-trips-form-actions">
                            <input type="submit" name="save_design" class="button button-primary" value="Save Design">
                            <a href="?page=all-trips-design-library" class="button button-secondary">Cancel</a>
                            <?php if ($editing): ?>
                                <input type="hidden" name="design_id" value="<?php echo esc_attr($design_id); ?>">
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <div class="all-trips-design-preview-container">
                    <h3>Live Preview</h3>
                    <div id="design-preview" class="all-trips-preview">
                        <!-- Preview will be updated by JavaScript -->
                        <div class="all-trips-preview-layout">
                            <div class="preview-display-type">Loading preview...</div>
                        </div>
                    </div>

                    <div class="all-trips-shortcode-generator">
                        <h4>Generated Shortcode</h4>
                        <div class="shortcode-preview">
                            <?php if ($editing): ?>
                                <code>[all_trips design="<?php echo !empty($design['keyword']) ? esc_attr($design['keyword']) : esc_attr($design_id); ?>"]</code>
                                <button class="button button-small all-trips-copy-shortcode"
                                        data-shortcode='[all_trips design="<?php echo !empty($design['keyword']) ? esc_attr($design['keyword']) : esc_attr($design_id); ?>"]'>Copy</button>
                            <?php else: ?>
                                <p>Shortcode will be generated after saving.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .all-trips-create-design-container {
            margin-top: 20px;
        }
        .all-trips-design-form-and-preview {
            display: flex;
            gap: 30px;
        }
        .all-trips-design-form {
            flex: 1;
            max-width: 500px;
        }
        .all-trips-design-preview-container {
            flex: 1;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 20px;
        }
        .all-trips-form-field {
            margin-bottom: 20px;
        }
        .all-trips-form-field label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        .all-trips-form-field input[type="text"],
        .all-trips-form-field select,
        .all-trips-form-field input[type="date"] {
            width: 100%;
        }
        .date-range-inputs {
            display: flex;
            gap: 10px;
        }
        .date-range-inputs > div {
            flex: 1;
        }
        .all-trips-form-actions {
            margin-top: 30px;
        }
        .all-trips-preview {
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            min-height: 300px;
            margin-bottom: 20px;
        }
        .preview-display-type {
            text-align: center;
            padding: 40px 0;
            color: #757575;
        }
        .all-trips-shortcode-generator {
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
        }
        .shortcode-preview {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f1f1f1;
            padding: 10px;
            border-radius: 3px;
        }
    </style>

    <script>
    jQuery(document).ready(function($) {
        // Create a nonce field in the form
        var nonceField = $('<input>').attr({
            type: 'hidden',
            name: 'all_trips_nonce',
            value: '<?php echo wp_create_nonce("all_trips_nonce"); ?>'
        });
        $('form').append(nonceField);

        // Keyword uniqueness checker
        var checkKeywordTimeout;
        $('#design_keyword').on('keyup blur', function() {
            var keyword = $(this).val();
            clearTimeout(checkKeywordTimeout);

            // Clear any existing validation messages
            $('#keyword-validation-message').remove();

            // Only check if keyword has content
            if (keyword.length > 0) {
                // Add a small delay to prevent too many requests
                checkKeywordTimeout = setTimeout(function() {
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'check_keyword_unique',
                            keyword: keyword,
                            design_id: '<?php echo esc_js($design_id); ?>',
                            nonce: '<?php echo wp_create_nonce("all_trips_nonce"); ?>'
                        },
                        success: function(response) {
                            if (!response.unique) {
                                // Display validation message
                                $('<p id="keyword-validation-message" class="validation-error" style="color:red;">This keyword is already in use. Please choose a unique keyword.</p>')
                                    .insertAfter('#design_keyword');
                            } else {
                                // Show success message
                                $('<p id="keyword-validation-message" class="validation-success" style="color:green;">Keyword is available!</p>')
                                    .insertAfter('#design_keyword');
                            }
                        }
                    });
                }, 500);
            }
        });
    });
    </script>
    <?php
}
