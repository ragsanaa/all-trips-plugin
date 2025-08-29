<?php
/**
 * Consent page for WeTravel Widgets Plugin
 *
 * @package WordPress
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Handle consent actions
 */
function wetravel_handle_consent() {
    if ( ! isset( $_POST['wetravel_consent_action'] ) ) {
        return;
    }

    if ( ! isset( $_POST['wetravel_consent_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wetravel_consent_nonce'] ) ), 'wetravel_consent_action' ) ) {
        wp_die( 'Security check failed' );
    }

    $action = isset( $_POST['wetravel_consent_action'] ) ? sanitize_text_field( wp_unslash( $_POST['wetravel_consent_action'] ) ) : '';

    if ( $action === 'allow' ) {
        // User allowed consent
        update_option( 'wetravel_consent_given', true );
        update_option( 'wetravel_consent_timestamp', current_time( 'timestamp' ) );
        update_option( 'wetravel_consent_type', 'allowed' );

        // Remove the activation consent notice
        delete_transient( 'wetravel_activation_consent_notice' );

        // Track user state with original data since consent was allowed
        if ( function_exists( 'wetravel_track_user_state' ) ) {
            // Track user state - bypass consent check for initial consent decision
            wetravel_track_plugin_state( 'allowed_consent' );
        }

        // Redirect to settings page with success message
        wp_safe_redirect( admin_url( 'admin.php?page=wetravel-trips-setup&consent=allowed' ) );
        exit;

    } elseif ( $action === 'skip' ) {
        // User skipped consent
        update_option( 'wetravel_consent_given', 0 );
        update_option( 'wetravel_consent_timestamp', current_time( 'timestamp' ) );
        update_option( 'wetravel_consent_type', 'skipped' );

        // Remove the activation consent notice
        delete_transient( 'wetravel_activation_consent_notice' );

        // Track user state with anonymous data since consent was skipped
        if ( function_exists( 'wetravel_track_user_state' ) ) {
            // Track user state - bypass consent check for initial consent decision
            wetravel_track_plugin_state( 'skipped_consent' );
        }

        // Redirect to settings page with skip message
        wp_safe_redirect( admin_url( 'admin.php?page=wetravel-trips-setup&consent=skipped' ) );
        exit;
    }
}
add_action( 'admin_init', 'wetravel_handle_consent' );

/**
 * Render consent page
 */
function wetravel_consent_page() {
    // Check if user has already given consent
    if ( get_option( 'wetravel_consent_given' ) !== false ) {
        wp_safe_redirect( admin_url( 'admin.php?page=wetravel-trips-setup' ) );
        exit;
    }

    // Check if this is the activation consent notice
    if ( ! get_transient( 'wetravel_activation_consent_notice' ) ) {
        wp_safe_redirect( admin_url( 'admin.php?page=wetravel-trips-setup' ) );
        exit;
    }

    ?>
    <div class="wrap">
        <div class="wetravel-consent-page-container">
            <div class="consent-container">
                <div class="consent-logo">
                    <img src="<?php echo esc_url( plugins_url( 'assets/icon.svg', dirname( __FILE__ ) ) ); ?>" alt="WeTravel Logo" style="width:96px;height:96px;" />
                    <h1>Never miss an important update</h1>
                </div>

                <div class="consent-content">
                    <p class="consent-description">
                        Opt in to get email notifications for security & feature updates, educational content, and occasional offers, and to share some basic WordPress environment info. This will help us make the plugin more compatible with your site and better at doing what you need it to.
                    </p>

                    <form method="post" action="">
                        <?php wp_nonce_field( 'wetravel_consent_action', 'wetravel_consent_nonce' ); ?>
                        <div class="consent-buttons">
                            <button type="submit" name="wetravel_consent_action" value="allow" class="consent-btn allow">
                                Allow & Continue
                                <span class="arrow">→</span>
                            </button>

                            <button type="submit" name="wetravel_consent_action" value="skip" class="consent-btn skip">
                                Skip
                            </button>
                        </div>
                    </form>

                    <div class="consent-link">
                        This will allow <a href="https://wetravel.com" target="_blank">WeTravel Widgets</a> →
                    </div>
                </div>

                <div class="global-footer">
                    Powered by <a href="https://freemius.com" target="_blank">Freemius</a> -
                    <a href="#" target="_blank">Privacy Policy</a> -
                    <a href="#" target="_blank">Terms of Service</a>
                </div>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Redirect to consent page after activation
 */
function wetravel_redirect_to_consent() {
    // Only redirect if user hasn't given consent and activation notice is active
    if ( get_option( 'wetravel_consent_given' ) === false && get_transient( 'wetravel_activation_consent_notice' ) ) {
        // Only process for users with admin capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Check if we're not already on the consent page
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This is a safe redirect on admin_init, only for users with manage_options, and does not process sensitive data.
        if ( ! isset( $_GET['page'] ) || sanitize_text_field( wp_unslash( $_GET['page'] ) ) !== 'wetravel-consent' ) {
            // Check if we're on the main plugin page
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This is a safe redirect on admin_init, only for users with manage_options, and does not process sensitive data.
            if ( isset( $_GET['page'] ) && strpos( sanitize_text_field( wp_unslash( $_GET['page'] ) ), 'wetravel-trips' ) === 0 ) {
                wp_safe_redirect( admin_url( 'admin.php?page=wetravel-consent' ) );
                exit;
            }
        }
    }
}
add_action( 'admin_init', 'wetravel_redirect_to_consent' );

/**
 * TODO: REMOVE this before push the changes
 * Force redirect to consent page for testing
 */
function wetravel_force_consent_redirect() {
    // Only for testing - remove in production
    if ( isset( $_GET['force_consent'], $_GET['_wpnonce'] ) &&
         current_user_can( 'manage_options' ) &&
         wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'wetravel_force_consent_nonce' ) ) {
        set_transient( 'wetravel_activation_consent_notice', true, 60 * 60 * 24 * 7 );
        delete_option( 'wetravel_consent_given' );
        wp_safe_redirect( admin_url( 'admin.php?page=wetravel-consent' ) );
        exit;
    }
}
add_action( 'admin_init', 'wetravel_force_consent_redirect' );

/**
 * Add consent page to admin menu as submenu
 */
function wetravel_add_consent_page() {
    $consent_given = get_option( 'wetravel_consent_given', 'unknown' );

    if ( $consent_given === 'unknown' ) {
        // Show as visible submenu item when consent not given
        add_submenu_page(
            'wetravel-trips-main', // Parent menu slug
            'Setup Consent',
            'Setup Consent',
            'manage_options',
            'wetravel-consent',
            'wetravel_consent_page'
        );
    }
}
add_action( 'admin_menu', 'wetravel_add_consent_page' );

/**
 * Handle consent actions from settings page links
 */
function wetravel_handle_consent_actions() {
    if ( ! isset( $_GET['page'] ) || sanitize_text_field( wp_unslash( $_GET['page'] ) ) !== 'wetravel-consent' ) {
        return;
    }

    if ( ! isset( $_GET['action'] ) || ! current_user_can( 'manage_options' ) ) {
        return;
    }

    // Verify nonce for consent actions
    if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'wetravel_consent_action_nonce' ) ) {
        return;
    }

    $action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';

    // Get return page and tab information
    $return_page = isset( $_GET['return_page'] ) ? sanitize_text_field( wp_unslash( $_GET['return_page'] ) ) : 'wetravel-trips-setup';
    $return_tab = isset( $_GET['return_tab'] ) ? sanitize_text_field( wp_unslash( $_GET['return_tab'] ) ) : '';

    if ( $action === 'opt_in' ) {
        // User wants to opt in
        update_option( 'wetravel_consent_given', 1 );
        update_option( 'wetravel_consent_timestamp', current_time( 'timestamp' ) );
        update_option( 'wetravel_consent_type', 'allowed' );

        // Track user state with full data since consent was given
        if ( function_exists( 'wetravel_track_user_state' ) ) {
            $wt_user_id = get_option( 'wetravel_trips_user_id', '' );
            $wt_user_slug = get_option( 'wetravel_trips_slug', '' );

            // Track user state - bypass consent check for consent update
            wetravel_track_user_state( $wt_user_id, $wt_user_slug, true, false, array(), true );
            wetravel_track_plugin_state( 'allowed_consent' );
        }

        // Build redirect URL with return page and tab
        $redirect_url = admin_url( 'admin.php?page=' . $return_page . '&consent=updated&status=allowed' );
        if ( ! empty( $return_tab ) ) {
            $redirect_url .= '&tab=' . $return_tab;
        }

        wp_safe_redirect( $redirect_url );
        exit;

    } elseif ( $action === 'opt_out' ) {
        // User wants to opt out
        update_option( 'wetravel_consent_given', 0 );
        update_option( 'wetravel_consent_timestamp', current_time( 'timestamp' ) );
        update_option( 'wetravel_consent_type', 'opted_out' );

        // Track user state with anonymous data since consent was revoked
        if ( function_exists( 'wetravel_track_user_state' ) ) {
            $wt_user_id = get_option( 'wetravel_trips_user_id', '' );
            $wt_user_slug = get_option( 'wetravel_trips_slug', '' );

            // Track user state - bypass consent check for consent update
            wetravel_track_user_state( $wt_user_id, $wt_user_slug, true, true, array(), true );
            wetravel_track_plugin_state( 'skipped_consent' );
        }

        // Build redirect URL with return page and tab
        $redirect_url = admin_url( 'admin.php?page=' . $return_page . '&consent=updated&status=opted_out' );
        if ( ! empty( $return_tab ) ) {
            $redirect_url .= '&tab=' . $return_tab;
        }

        wp_safe_redirect( $redirect_url );
        exit;
    }
}
add_action( 'admin_init', 'wetravel_handle_consent_actions' );

/**
 * Add Opt-in button to plugin listing page
 */
function wetravel_add_plugin_action_links( $links, $file ) {
    // Only modify links for our plugin
    if ( plugin_basename( WETRAVEL_WIDGETS_PLUGIN_FILE ) === $file ) {
        $consent_given = get_option( 'wetravel_consent_given' );

        if ( $consent_given === false ) {
            // User hasn't given consent - show Opt-in link
            $opt_in_link = '<a href="' . admin_url( 'admin.php?page=wetravel-consent' ) . '">Opt-in</a>';
            array_unshift( $links, $opt_in_link );
        }
    }

    return $links;
}
add_filter( 'plugin_action_links', 'wetravel_add_plugin_action_links', 10, 2 );

/**
 * Handle dismissing the consent notice
 */
function wetravel_handle_dismiss_consent_notice() {
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wetravel_dismiss_notice' ) ) {
		wp_die( 'Security check failed' );
	}

	delete_transient( 'wetravel_activation_consent_notice' );
	wp_send_json_success();
}
add_action( 'wp_ajax_wetravel_dismiss_consent_notice', 'wetravel_handle_dismiss_consent_notice' );
