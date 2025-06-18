<?php
/**
 * Admin instructions page for WeTravel Widgets Plugin
 *
 * @package WordPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Render instructions Page */
function wetravel_trips_instructions_page() {
	?>
	<div class="wrap">
		<h1>WeTravel Widgets Plugin - Instructions</h1>

		<div class="nav-tab-wrapper" style="margin-bottom: 32px;">
			<a href="?page=wetravel-trips-instructions" class="nav-tab nav-tab-active">Instructions</a>
			<a href="?page=wetravel-trips-settings" class="nav-tab">Settings</a>
			<a href="?page=wetravel-trips-design-library" class="nav-tab">Widget Library</a>
			<a href="?page=wetravel-trips-create-design" class="nav-tab">Create Widget</a>
		</div>

		<div class="wetravel-trips-instructions-container">
			<!-- Tab Navigation -->
			<div class="wetravel-instructions-tabs">
				<button class="tab-button active" data-tab="getting-started">Getting Started</button>
				<button class="tab-button" data-tab="configuration">Configuration</button>
				<button class="tab-button" data-tab="creating-widgets">Creating Widgets</button>
				<button class="tab-button" data-tab="displaying-widgets">Displaying Widgets</button>
				<button class="tab-button" data-tab="managing-designs">Managing Designs</button>
				<button class="tab-button" data-tab="advanced-features">Advanced Features</button>
				<button class="tab-button" data-tab="troubleshooting">Troubleshooting</button>
				<button class="tab-button" data-tab="privacy-requirements">Privacy & Requirements</button>
			</div>

			<!-- Tab Content -->
			<div class="tab-content">
				<!-- Getting Started Tab -->
				<div id="getting-started" class="tab-pane active">
					<div class="wetravel-instructions-section">
						<h2>Getting Started</h2>
						<p>The WeTravel Widgets plugin allows you to customize WeTravel's embedded widgets to seamlessly match your WordPress website's design. Create multiple widget designs, save them for reuse, and easily embed them anywhere using shortcodes or Gutenberg blocks.</p>

						<h3>Quick Setup Guide</h3>
						<ol>
							<li><strong>Configure Settings:</strong> Go to <strong>WeTravel Widgets > Settings</strong> and paste your WeTravel "All Trips" embed code</li>
							<li><strong>Create Your First Widget:</strong> Navigate to <strong>WeTravel Widgets > Create Widget</strong> to design your custom widget</li>
							<li><strong>Display Your Widget:</strong> Use the generated shortcode or Gutenberg block to place your widget on any page</li>
						</ol>
					</div>
				</div>

				<!-- Configuration Tab -->
				<div id="configuration" class="tab-pane">
					<div class="wetravel-instructions-section">
						<h2>Configuration</h2>

						<h3>Setting Up Your WeTravel Integration</h3>
						<p>Before creating custom widgets, you need to configure your WeTravel embed code:</p>

						<ol>
							<li>Go to <strong>WeTravel Widgets > Settings</strong></li>
							<li>Paste your WeTravel "All Trips" embed script in the provided text area</li>
							<li>Click "Save Changes"</li>
							<li>The plugin will automatically extract the necessary details (slug, environment, user ID)</li>
						</ol>

						<div class="notice notice-info">
							<p><strong>Note:</strong> You can only reset your embed code when no WeTravel widgets are actively being used on your site. If you need to change the embed code, first remove all widgets from your content.</p>
						</div>
					</div>
				</div>

				<!-- Creating Widgets Tab -->
				<div id="creating-widgets" class="tab-pane">
					<div class="wetravel-instructions-section">
						<h2>Creating Custom Widget Designs</h2>

						<h3>Design Options Available</h3>
						<p>When creating a widget design, you can customize:</p>

						<ul>
							<li><strong>Layout Type:</strong> Choose between vertical, grid, or carousel layouts</li>
							<li><strong>Button Customization:</strong> Set button type (Book Now or View Trip), custom text, and colors</li>
							<li><strong>Display Settings:</strong> Configure items per page, row, or slide</li>
							<li><strong>Trip Filtering:</strong> Filter by trip type (all, one-time, or recurring)</li>
							<li><strong>Date Ranges:</strong> Set specific date ranges for one-time trips</li>
							<li><strong>Location Filtering:</strong> Focus on specific locations (version 1.1+)</li>
							<li><strong>Search Functionality:</strong> Enable/disable search bar for trip name and location filtering</li>
						</ul>

						<h3>Creating Your First Widget Design</h3>
						<ol>
							<li>Go to <strong>WeTravel Widgets > Create Widget</strong></li>
							<li>Enter a descriptive name for your widget design</li>
							<li>Choose your preferred layout type</li>
							<li>Customize button settings (type, text, color)</li>
							<li>Set display preferences (items per page/row/slide)</li>
							<li>Configure trip filtering options</li>
							<li>Add a unique keyword for easy reference (optional but recommended)</li>
							<li>Click "Save Design"</li>
						</ol>

						<div class="notice notice-success">
							<p><strong>Pro Tip:</strong> Use descriptive keywords for your designs to make them easier to find and reference later. For example: "homepage-carousel", "europe-trips", "summer-specials".</p>
						</div>
					</div>
				</div>

				<!-- Displaying Widgets Tab -->
				<div id="displaying-widgets" class="tab-pane">
					<div class="wetravel-instructions-section">
						<h2>Displaying Your Widgets</h2>

						<h3>Using Shortcodes</h3>
						<p>After creating a widget design, you'll receive a shortcode that you can use anywhere on your site:</p>

						<div class="code-example">
							<code>[wetravel_trips widget="your-design-keyword"]</code>
						</div>

						<h4>Shortcode Parameters</h4>
						<p>You can also use the shortcode with custom parameters:</p>

						<table class="widefat">
							<thead>
								<tr>
									<th>Parameter</th>
									<th>Description</th>
									<th>Default</th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td><code>widget</code></td>
									<td>ID or keyword of your saved widget design</td>
									<td>-</td>
								</tr>
								<tr>
									<td><code>display_type</code></td>
									<td>Layout style: "vertical", "grid", or "carousel"</td>
									<td>"vertical"</td>
								</tr>
								<tr>
									<td><code>button_type</code></td>
									<td>Type of button: "book_now" or "view_trip"</td>
									<td>"book_now"</td>
								</tr>
								<tr>
									<td><code>button_text</code></td>
									<td>Custom text for the button</td>
									<td>-</td>
								</tr>
								<tr>
									<td><code>button_color</code></td>
									<td>Color of the button (hex code)</td>
									<td>"#33ae3f"</td>
								</tr>
								<tr>
									<td><code>items_per_page</code></td>
									<td>Number of trips to display per page</td>
									<td>10</td>
								</tr>
								<tr>
									<td><code>items_per_row</code></td>
									<td>Number of trips to display per row in grid layout</td>
									<td>3</td>
								</tr>
								<tr>
									<td><code>items_per_slide</code></td>
									<td>Number of trips to display per slide in carousel layout</td>
									<td>3</td>
								</tr>
								<tr>
									<td><code>trip_type</code></td>
									<td>Filter trips by type: "all", "one-time", or "recurring"</td>
									<td>"all"</td>
								</tr>
								<tr>
									<td><code>date_start</code></td>
									<td>Start date for filtering trips (YYYY-MM-DD)</td>
									<td>-</td>
								</tr>
								<tr>
									<td><code>date_end</code></td>
									<td>End date for filtering trips (YYYY-MM-DD)</td>
									<td>-</td>
								</tr>
								<tr>
									<td><code>search_visibility</code></td>
									<td>Toggle search bar visibility: true (1) or false (0)</td>
									<td>false</td>
								</tr>
							</tbody>
						</table>

						<h4>Shortcode Examples</h4>
						<div class="code-example">
							<p><strong>Basic usage with saved design:</strong></p>
							<code>[wetravel_trips widget="my-custom-design"]</code>

							<p><strong>Custom parameters:</strong></p>
							<code>[wetravel_trips display_type="carousel" items_per_slide="3" button_color="#ff0000" button_text="Book Now"]</code>
						</div>

						<h3>Using Gutenberg Blocks</h3>
						<p>You can also add WeTravel widgets using the Gutenberg block editor:</p>

						<ol>
							<li>Edit any post or page using the block editor</li>
							<li>Click the "+" button to add a new block</li>
							<li>Search for "WeTravel Trips Block"</li>
							<li>Select your saved design from the dropdown</li>
							<li>Publish or update your content</li>
						</ol>

						<div class="notice notice-warning">
							<p><strong>Important:</strong> When using Gutenberg blocks, always add your embed code in the settings before making design customizations. If you customize first and add the embed code later, refresh the page and re-save your post/page to ensure proper functionality.</p>
						</div>
					</div>
				</div>

				<!-- Managing Designs Tab -->
				<div id="managing-designs" class="tab-pane">
					<div class="wetravel-instructions-section">
						<h2>Managing Your Widget Designs</h2>

						<h3>Widget Library</h3>
						<p>Access all your saved widget designs in the <strong>WeTravel Widgets > Widget Library</strong>:</p>

						<ul>
							<li>View all your saved designs in one place</li>
							<li>Edit existing designs</li>
							<li>Copy shortcodes for easy implementation</li>
							<li>Delete designs you no longer need</li>
						</ul>

						<h3>Editing Widget Designs</h3>
						<ol>
							<li>Go to <strong>WeTravel Widgets > Widget Library</strong></li>
							<li>Find the design you want to edit</li>
							<li>Click the "Edit" button</li>
							<li>Make your changes</li>
							<li>Click "Save Design"</li>
						</ol>

						<p>Changes to widget designs will automatically update everywhere the widget is used on your site.</p>
					</div>
				</div>

				<!-- Advanced Features Tab -->
				<div id="advanced-features" class="tab-pane">
					<div class="wetravel-instructions-section">
						<h2>Advanced Features</h2>

						<h3>Search Functionality (Version 1.1+)</h3>
						<p>The search bar allows visitors to filter trips by name and location:</p>

						<ul>
							<li>Enable/disable the search bar in your widget design settings</li>
							<li>Search by trip name and location</li>
							<li>Not available for carousel layouts</li>
						</ul>

						<h3>Location-Based Widget Designs</h3>
						<p>Create widget designs that focus on specific locations:</p>

						<ol>
							<li>Create a new widget design</li>
							<li>Use the location filter options</li>
							<li>Save the design with a location-specific keyword</li>
							<li>Perfect for creating region-specific trip displays</li>
						</ol>

						<h3>Responsive Design</h3>
						<p>All widgets are fully responsive and look great on:</p>

						<ul>
							<li>Desktop computers</li>
							<li>Tablets</li>
							<li>Mobile phones</li>
						</ul>
					</div>
				</div>

				<!-- Troubleshooting Tab -->
				<div id="troubleshooting" class="tab-pane">
					<div class="wetravel-instructions-section">
						<h2>Troubleshooting</h2>

						<h3>Common Issues and Solutions</h3>

						<h4>Widget Not Displaying</h4>
						<ul>
							<li>Ensure your WeTravel embed code is properly configured in Settings</li>
							<li>Check that your shortcode or block is correctly placed</li>
							<li>Verify that your widget design is saved and active</li>
						</ul>

						<h4>Gutenberg Block Issues</h4>
						<ul>
							<li>Always add embed code before customizing designs</li>
							<li>If changes aren't detected, refresh the page and re-save</li>
							<li>Ensure you're using the latest version of WordPress</li>
						</ul>

						<h4>Search Bar Not Working</h4>
						<ul>
							<li>Search functionality is not available for carousel layouts</li>
							<li>Ensure search visibility is enabled in your widget design</li>
							<li>Check that you're using version 1.1 or higher</li>
						</ul>

						<h4>Button Not Functioning</h4>
						<ul>
							<li>Verify your embed code is correctly configured</li>
							<li>Check that the WeTravel API is accessible</li>
							<li>Ensure your trip data is up-to-date on WeTravel</li>
						</ul>
					</div>
				</div>

				<!-- Privacy & Requirements Tab -->
				<div id="privacy-requirements" class="tab-pane">
					<div class="wetravel-instructions-section">
						<h2>External Services and Privacy</h2>

						<p>This plugin connects to WeTravel's services for:</p>

						<h3>Trip Information Retrieval</h3>
						<ul>
							<li>Fetches trip data from WeTravel's API (api.wetravel.com)</li>
							<li>Only uses identification data from your provided embed code</li>
							<li>No personal user data is sent during this process</li>
							<li>Data includes trip details, pricing, availability, and SEO configuration</li>
						</ul>

						<h3>Booking Widget Integration</h3>
						<ul>
							<li>Uses WeTravel's secure checkout platform</li>
							<li>User data collected during booking is handled by WeTravel</li>
							<li>Subject to WeTravel's privacy policy and terms of service</li>
						</ul>

						<p>For more information about data handling, please review:</p>
						<ul>
							<li><a href="https://www.wetravel.com/terms" target="_blank">WeTravel Terms of Service</a></li>
							<li><a href="https://www.wetravel.com/privacy" target="_blank">WeTravel Privacy Policy</a></li>
						</ul>

						<h2>System Requirements</h2>

						<ul>
							<li><strong>WordPress:</strong> Version 5.0 or higher</li>
							<li><strong>PHP:</strong> Version 7.0 or higher</li>
							<li><strong>Browser:</strong> Modern browsers with JavaScript enabled</li>
						</ul>

						<div class="notice notice-info">
							<p><strong>Need Help?</strong> If you encounter any issues or need assistance, please check the troubleshooting section above or refer to the plugin documentation.</p>
						</div>
					</div>
				</div>
			</div>
		</div>

		<script>
			document.addEventListener('DOMContentLoaded', function() {
				const tabButtons = document.querySelectorAll('.tab-button');
				const tabPanes = document.querySelectorAll('.tab-pane');

				tabButtons.forEach(button => {
					button.addEventListener('click', function() {
						const targetTab = this.getAttribute('data-tab');

						// Remove active class from all buttons and panes
						tabButtons.forEach(btn => btn.classList.remove('active'));
						tabPanes.forEach(pane => pane.classList.remove('active'));

						// Add active class to clicked button and corresponding pane
						this.classList.add('active');
						document.getElementById(targetTab).classList.add('active');
					});
				});
			});
		</script>
	</div>
	<?php
}

