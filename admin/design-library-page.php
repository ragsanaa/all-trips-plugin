<?php
/**
 * Admin Widget Library page for WeTravel Widgets Plugin
 *
 * @package WordPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Render Widget Library Page */
function wetravel_trips_design_library_page() {
	// Handle design deletion.
	if ( isset( $_GET['delete_design'] ) && ! empty( $_GET['delete_design'] ) ) {
		// Only check nonce when deleting.
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'wetravel_trips_delete_nonce' ) ) {
			wp_die( 'Security check failed' );
		}

		$design_id = sanitize_text_field( wp_unslash( $_GET['delete_design'] ) );
		$designs   = get_option( 'wetravel_trips_designs', array() );

		if ( isset( $designs[ $design_id ] ) ) {
			unset( $designs[ $design_id ] );
			update_option( 'wetravel_trips_designs', $designs );
			$delete_message = 'Widget deleted successfully.';
		}
	}

	// Get all saved designs.
	$designs = get_option( 'wetravel_trips_designs', array() );
	?>
	<div class="wrap">
		<h1>WeTravel Widgets Plugin - Widget Library</h1>

		<div class="nav-tab-wrapper">
			<a href="?page=wetravel-trips-settings" class="nav-tab">Settings</a>
			<a href="?page=wetravel-trips-design-library" class="nav-tab nav-tab-active">Widget Library</a>
			<a href="?page=wetravel-trips-create-design" class="nav-tab">Create Widget</a>
		</div>

		<?php if ( isset( $delete_message ) ) : ?>
			<div class="notice notice-success is-dismissible">
				<p><?php echo esc_html( $delete_message ); ?></p>
			</div>
		<?php endif; ?>

		<div class="wetravel-trips-design-library-container">
			<div class="wetravel-trips-library-header">
				<h2>Your Saved Widgets</h2>
				<a href="?page=wetravel-trips-create-design" class="button button-primary">Create New Widget</a>
			</div>

			<?php if ( empty( $designs ) ) : ?>
				<div class="wetravel-trips-no-designs">
					<p>You don't have any saved designs yet. <a href="?page=wetravel-trips-create-design">Create your first design</a> to get started.</p>
				</div>
			<?php else : ?>
				<div class="wetravel-trips-designs-grid">
					<?php foreach ( $designs as $design_id => $design ) : ?>
						<div class="wetravel-trips-design-card">
							<div class="wetravel-trips-design-preview" style="background-color: <?php echo esc_attr( $design['buttonColor'] ); ?>">
								<div class="wetravel-trips-design-display-type"><?php echo esc_attr( ucfirst( esc_html( $design['displayType'] ) ) ); ?> Layout</div>
							</div>
							<div class="wetravel-trips-design-info">
								<h3><?php echo esc_html( $design['name'] ); ?></h3>
								<div class="wetravel-trips-design-meta">
									<span>Created: <?php echo esc_attr( date_i18n( get_option( 'date_format' ), $design['created'] ) ); ?></span>
									<br>
									<span>Updated: <?php echo esc_attr( date_i18n( get_option( 'date_format' ), $design['modified'] ) ); ?></span>
								</div>
								<div class="wetravel-trips-design-actions">
									<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=wetravel-trips-create-design&edit=' . esc_attr( $design_id ) ), 'wetravel_trips_edit_nonce' ) ); ?>" class="button button-small">Edit</a>
									<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=wetravel-trips-design-library&delete_design=' . esc_attr( $design_id ) ), 'wetravel_trips_delete_nonce' ) ); ?>" class="button button-small" onclick="return confirm('Are you sure you want to delete this design?')">Delete</a>
									<button class="button button-small wetravel-trips-copy-shortcode" data-shortcode='[wetravel_trips widget="<?php echo esc_attr( empty( $design['keyword'] ) ? $design_id : $design['keyword'] ); ?>"]'>Copy Shortcode</button>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
	<?php
}
