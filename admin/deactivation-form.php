<?php
/**
 * Plugin Deactivation Feedback Form
 *
 * @package WeTravel_Widgets
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class WeTravel_Deactivation_Form
 */
class WeTravel_Deactivation_Form {

    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'admin_footer', array( $this, 'render_deactivation_form' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_deactivation_scripts' ) );
        add_action( 'wp_ajax_wetravel_deactivation_feedback', array( $this, 'handle_deactivation_feedback' ) );
    }

    /**
     * Enqueue scripts and styles for deactivation form
     */
    public function enqueue_deactivation_scripts( $hook ) {
        // Only load on plugins page
        if ( 'plugins.php' !== $hook ) {
            return;
        }

        wp_enqueue_script(
            'wetravel-deactivation-form',
            WETRAVEL_WIDGETS_PLUGIN_URL . 'admin/js/deactivation-form.js',
            array( 'jquery' ),
            WETRAVEL_PLUGIN_VERSION,
            true
        );

        wp_enqueue_style(
            'wetravel-deactivation-form',
            WETRAVEL_WIDGETS_PLUGIN_URL . 'admin/css/deactivation-form.css',
            array( 'dashicons' ),
            WETRAVEL_PLUGIN_VERSION
        );

        wp_localize_script(
            'wetravel-deactivation-form',
            'wetravelDeactivation',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'wetravel_deactivation_nonce' ),
                'plugin_slug' => 'wetravel-widgets/wetravel-widgets.php'
            )
        );
    }

    /**
     * Render the deactivation feedback form
     */
    public function render_deactivation_form() {
        global $pagenow;

        // Only show on plugins page
        if ( 'plugins.php' !== $pagenow ) {
            return;
        }
        ?>

        <div id="wetravel-deactivation-modal" class="wetravel-modal" style="display: none;">
            <div class="wetravel-modal-content">
                <div class="wetravel-modal-header">
                    <h2><?php esc_html_e('We\'re sorry to see you go! If you have a moment, please let us know how we can improve.', 'wetravel-widgets' ); ?></h2>
                </div>

                <div class="wetravel-modal-body">
                    <form id="wetravel-deactivation-form">
                        <div class="wetravel-feedback-options">
                            <label class="wetravel-feedback-option">
                                <input type="radio" name="feedback_reason" value="couldnt_understand" />
                                <span class="wetravel-option-icon dashicons dashicons-editor-help"></span>
                                <span class="wetravel-option-text"><?php esc_html_e( "Couldn't understand", 'wetravel-widgets' ); ?></span>
                            </label>

                            <label class="wetravel-feedback-option">
                                <input type="radio" name="feedback_reason" value="missing_feature" />
                                <span class="wetravel-option-icon dashicons dashicons-admin-tools"></span>
                                <span class="wetravel-option-text"><?php esc_html_e( 'Missing a specific feature', 'wetravel-widgets' ); ?></span>
                            </label>

                            <label class="wetravel-feedback-option">
                                <input type="radio" name="feedback_reason" value="not_working" />
                                <span class="wetravel-option-icon dashicons dashicons-warning"></span>
                                <span class="wetravel-option-text"><?php esc_html_e( 'Not working', 'wetravel-widgets' ); ?></span>
                            </label>

                            <label class="wetravel-feedback-option">
                                <input type="radio" name="feedback_reason" value="not_what_looking_for" />
                                <span class="wetravel-option-icon dashicons dashicons-visibility"></span>
                                <span class="wetravel-option-text"><?php esc_html_e( 'Not what I was looking for', 'wetravel-widgets' ); ?></span>
                            </label>

                            <label class="wetravel-feedback-option">
                                <input type="radio" name="feedback_reason" value="not_work_expected" />
                                <span class="wetravel-option-icon dashicons dashicons-dismiss"></span>
                                <span class="wetravel-option-text"><?php esc_html_e( "Didn't work as expected", 'wetravel-widgets' ); ?></span>
                            </label>

                            <label class="wetravel-feedback-option">
                                <input type="radio" name="feedback_reason" value="other" />
                                <span class="wetravel-option-icon dashicons dashicons-ellipsis"></span>
                                <span class="wetravel-option-text"><?php esc_html_e( 'Others', 'wetravel-widgets' ); ?></span>
                            </label>
                        </div>

                        <div class="wetravel-feedback-text-container">
                            <textarea
                                id="wetravel-feedback-text"
                                name="feedback_text"
                                placeholder="<?php esc_attr_e( 'Would you like us to assist you?', 'wetravel-widgets' ); ?>"
                                rows="4"
                            ></textarea>
                        </div>
                    </form>
                </div>

                <div class="wetravel-modal-footer">
                    <div class="wetravel-btn-group-left">
                        <button type="button" id="wetravel-skip-deactivate" class="wetravel-btn wetravel-btn-secondary">
                            <?php esc_html_e( 'Skip & Deactivate', 'wetravel-widgets' ); ?>
                        </button>
                    </div>
                    <div class="wetravel-btn-group-right">
                        <button type="button" id="wetravel-cancel-deactivate" class="wetravel-btn wetravel-btn-secondary">
                            <?php esc_html_e( 'Cancel', 'wetravel-widgets' ); ?>
                        </button>
                        <button type="button" id="wetravel-submit-deactivate" class="wetravel-btn wetravel-btn-primary">
                            <?php esc_html_e( 'Submit & Deactivate', 'wetravel-widgets' ); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <?php
    }

    /**
     * Handle deactivation feedback submission
     */
    public function handle_deactivation_feedback() {
        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['nonce'], 'wetravel_deactivation_nonce' ) ) {
            wp_die( esc_html__( 'Security check failed', 'wetravel-widgets' ) );
        }

        $feedback_reason = sanitize_text_field( $_POST['feedback_reason'] ?? '' );
        $feedback_text = sanitize_textarea_field( $_POST['feedback_text'] ?? '' );

        // Store feedback data
        $feedback_data = array(
            'reason' => $feedback_reason,
            'text' => $feedback_text,
            'timestamp' => current_time( 'mysql' ),
            'site_url' => home_url(),
            'plugin_version' => WETRAVEL_PLUGIN_VERSION,
            'wp_version' => get_bloginfo( 'version' ),
            'php_version' => PHP_VERSION
        );

        // Save to options table for potential later use
        update_option( 'wetravel_deactivation_feedback', $feedback_data );

        // Store deactivation reason details in transients for the tracking system
        set_transient( 'wetravel_deactivation_reason', $feedback_reason, HOUR_IN_SECONDS );
        set_transient( 'wetravel_deactivation_reason_details', $feedback_text, HOUR_IN_SECONDS );

        // Try to send feedback to WeTravel if consent was given
        if ( get_option( 'wetravel_consent_given', false ) ) {
            // Track user state with deactivation details
            $this->track_deactivation_state( $feedback_reason, $feedback_text );
        }

        wp_send_json_success( array( 'message' => esc_html__( 'Thank you for your feedback!', 'wetravel-widgets' ) ) );
    }

    /**
     * Track user state with deactivation details
     */
    private function track_deactivation_state( $deactivation_reason, $deactivation_reason_details ) {
        // Check if tracking functions exist
        if ( ! function_exists( 'wetravel_track_user_state' ) ) {
            return;
        }

        // Get WeTravel user credentials
        $wt_user_id = get_option( 'wetravel_trips_user_id', '' );
        $wt_user_slug = get_option( 'wetravel_trips_slug', '' );

        // Check consent status to determine tracking approach
        $consent_given = get_option( 'wetravel_consent_given', false );

        // Prepare deactivation data
        $deactivation_data = array(
            'deactivation_reason' => $deactivation_reason,
            'deactivation_reason_details' => $deactivation_reason_details
        );

        if ( $consent_given ) {
			// User gave consent - track with full user data + deactivation details
			wetravel_track_user_state( $wt_user_id, $wt_user_slug, false, false, $deactivation_data, true );
		} else {
			// User skipped consent - track with anonymous data + deactivation details
			wetravel_track_user_state( $wt_user_id, $wt_user_slug, false, true, $deactivation_data, true );
		}
    }
}

// Initialize the deactivation form
new WeTravel_Deactivation_Form();
