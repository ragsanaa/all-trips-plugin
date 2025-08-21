# WeTravel Widgets Consent System

This document explains how the consent system works in the WeTravel Widgets plugin.

## Overview

The consent system is designed to ask users for permission to:

- Send email notifications for security & feature updates
- Share basic WordPress environment information
- Receive educational content and occasional offers

## How It Works

### 1. Plugin Activation

When a user activates the plugin:

- A transient `wetravel_activation_consent_notice` is set for 7 days
- The user is redirected to the consent page when they try to access any plugin page

### 2. Plugin Listing Integration

The plugin listing page shows different action links based on consent status:

- **No Consent Given**: Shows "Opt-in" button that directs to plugin settings
- **Consent Given**: Shows "Manage Consent" button for updating preferences
- **Always Available**: "Settings" and "Widget Library" links for easy access

### 3. Consent Page

The consent page (`admin/consent-page.php`) displays:

- A modern, responsive design matching the provided image
- Two options: "Allow & Continue" or "Skip"
- Information about what the consent allows

### 4. Consent Handling

- **Allow**: Sets `wetravel_consent_given` to `true`, `wetravel_consent_type` to `allowed`
- **Skip**: Sets `wetravel_consent_given` to `false`, `wetravel_consent_type` to `skipped`
- Both actions set a timestamp and remove the activation notice

### 5. Settings Page Integration

After consent:

- A dismissible message appears on the settings page
- Simple consent management links appear below the main settings
- Users can opt in/out anytime with direct links
- Clean, minimal interface without complex forms

### 6. Plugin Uninstallation

When the plugin is uninstalled:

- All consent-related options are automatically removed
- No trace of user consent remains

## Files Modified/Created

### New Files

- `admin/consent-page.php` - Main consent page and logic

### Modified Files

- `wetravel-widgets.php` - Added consent page include
- `admin/settings-page.php` - Added consent messages and management section
- `admin/css/admin-styles.css` - Added consent styling
- `uninstall.php` - Added consent options cleanup

## Testing the Consent System

### Test Consent Page

To test the consent page without activating the plugin:

1. Add `?force_consent=1` to any admin URL
2. This will simulate the activation state and show the consent page

### Test Consent Flow

1. Activate the plugin (or use force_consent)
2. You'll be redirected to the consent page
3. Choose Allow or Skip
4. You'll be redirected to settings with a message
5. Check the Consent Management section

### Test Plugin Listing Links

1. Go to WordPress Admin → Plugins
2. Find "WeTravel Widgets" in the list
3. **Before consent**: You should see "Opt-in", "Settings", "Widget Library" links
4. **After consent**: You should see "Manage Consent", "Settings", "Widget Library" links
5. Click the links to verify they direct to the correct pages

### Reset Consent for Testing

To reset consent and test again:

```php
delete_option('wetravel_consent_given');
delete_option('wetravel_consent_type');
delete_option('wetravel_consent_timestamp');
set_transient('wetravel_activation_consent_notice', true, 60 * 60 * 24 * 7);
```

## Database Options

The system creates these options:

- `wetravel_consent_given` - Boolean: true/false
- `wetravel_consent_timestamp` - Unix timestamp
- `wetravel_consent_type` - String: 'allowed', 'skipped', or 'opted_out'

## Security Features

- Nonce verification for all consent actions
- Proper sanitization of user inputs
- Capability checks for admin functions
- Secure redirects using `wp_safe_redirect`

## Styling

The consent page uses:

- WordPress admin color scheme
- Responsive design for mobile devices
- Modern UI elements matching the design image
- Consistent styling with the rest of the plugin

## Future Enhancements

Potential improvements:

- Email preference management
- Granular consent options
- Consent analytics tracking
- GDPR compliance features
- Multi-language support
