<?php
// Admin Design Library page for All Trips Plugin

if (!defined('ABSPATH')) {
    exit;
}

function all_trips_design_library_page() {
    // Handle design deletion
    if (isset($_GET['delete_design']) && !empty($_GET['delete_design'])) {
        $design_id = sanitize_text_field($_GET['delete_design']);
        $designs = get_option('all_trips_designs', array());

        if (isset($designs[$design_id])) {
            unset($designs[$design_id]);
            update_option('all_trips_designs', $designs);
            $delete_message = 'Design deleted successfully.';
        }
    }

    // Get all saved designs
    $designs = get_option('all_trips_designs', array());
    ?>
    <div class="wrap">
        <h1>All Trips Plugin - Design Library</h1>

        <div class="nav-tab-wrapper">
            <a href="?page=all-trips-settings" class="nav-tab">Settings</a>
            <a href="?page=all-trips-design-library" class="nav-tab nav-tab-active">Design Library</a>
            <a href="?page=all-trips-create-design" class="nav-tab">Create Design</a>
        </div>

        <?php if (isset($delete_message)): ?>
            <div class="notice notice-success is-dismissible">
                <p><?php echo esc_html($delete_message); ?></p>
            </div>
        <?php endif; ?>

        <div class="all-trips-design-library-container">
            <div class="all-trips-library-header">
                <h2>Your Saved Designs</h2>
                <a href="?page=all-trips-create-design" class="button button-primary">Create New Design</a>
            </div>

            <?php if (empty($designs)): ?>
                <div class="all-trips-no-designs">
                    <p>You don't have any saved designs yet. <a href="?page=all-trips-create-design">Create your first design</a> to get started.</p>
                </div>
            <?php else: ?>
                <div class="all-trips-designs-grid">
                    <?php foreach ($designs as $design_id => $design): ?>
                        <div class="all-trips-design-card">
                            <div class="all-trips-design-preview" style="background-color: <?php echo esc_attr($design['buttonColor']); ?>">
                                <div class="all-trips-design-display-type"><?php echo ucfirst(esc_html($design['displayType'])); ?> Layout</div>
                            </div>
                            <div class="all-trips-design-info">
                                <h3><?php echo esc_html($design['name']); ?></h3>
                                <div class="all-trips-design-meta">
                                    <span>Created: <?php echo date_i18n(get_option('date_format'), $design['created']); ?></span>
                                </div>
                                <div class="all-trips-design-actions">
                                    <a href="?page=all-trips-create-design&edit=<?php echo esc_attr($design_id); ?>" class="button button-small">Edit</a>
                                    <a href="?page=all-trips-design-library&delete_design=<?php echo esc_attr($design_id); ?>" class="button button-small" onclick="return confirm('Are you sure you want to delete this design?')">Delete</a>
                                    <button class="button button-small all-trips-copy-shortcode" data-shortcode='[all_trips design="<?php echo esc_attr($design['keyword'] ?? $design_id); ?>"]'>Copy Shortcode</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <style>
        .all-trips-design-library-container {
            margin-top: 20px;
        }
        .all-trips-library-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .all-trips-no-designs {
            background: #f9f9f9;
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 4px;
            text-align: center;
        }
        .all-trips-designs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }
        .all-trips-design-card {
            border: 1px solid #ddd;
            border-radius: 4px;
            overflow: hidden;
            background: #fff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .all-trips-design-preview {
            height: 150px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            text-shadow: 0 1px 2px rgba(0,0,0,0.3);
        }
        .all-trips-design-info {
            padding: 15px;
        }
        .all-trips-design-info h3 {
            margin: 0 0 10px 0;
        }
        .all-trips-design-meta {
            color: #757575;
            font-size: 12px;
            margin-bottom: 15px;
        }
        .all-trips-design-actions {
            display: flex;
            gap: 5px;
        }
    </style>
    <?php
}
