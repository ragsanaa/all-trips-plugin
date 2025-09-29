# WeTravel Widgets Consent System

This document explains how the consent system works in the WeTravel Widgets plugin.

## Overview

The consent system is designed to ask users for permission to:

- Track user information (state) for usage analytics
- Track plugin state and events (such as widget loads)
- Gather statistics about plugin usage to identify areas for improvement
- Make the plugin more compatible with user sites and improve functionality

## How It Works

### 1. Plugin Activation

When a user activates the plugin:

- A transient `wetravel_activation_consent_notice` is set for 7 days
- The user is redirected to the consent page when they try to access any plugin page

### 2. Plugin Listing Integration

The plugin listing page shows different action links based on consent status:

- **No Consent Given**: Shows "Opt-in" button that directs to consent page
- **Always Available**: "Settings" and "Widget Library" links for easy access

### 3. Consent Page

The consent page (`admin/consent-page.php`) displays:

- A modern, responsive design with WeTravel branding
- Two options: "Allow & Continue" or "Skip"
- Clear information about what tracking data is collected and why
- Links to WeTravel's Privacy Policy and Terms of Service

### 4. Consent Handling

- **Allow**: Sets `wetravel_consent_given` to `true`, `wetravel_consent_type` to `allowed`
- **Skip**: Sets `wetravel_consent_given` to `false`, `wetravel_consent_type` to `skipped`
- Both actions set a timestamp and remove the activation notice

### 5. Consent Management

After initial consent:

- Users can manage their consent preferences through the Instructions/Privacy page
- Opt-in and opt-out options are available in the privacy section
- Users can change their consent status anytime
- Clean, minimal interface integrated with existing admin pages

### 6. Plugin Uninstallation

When the plugin is uninstalled:

- All consent-related options are automatically removed
- No trace of user consent remains

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

## Data Collection

When users consent, the plugin tracks:

- **User State**: Information about the user's plugin configuration and usage patterns
- **Plugin State**: Current plugin settings, active features, and configuration status
- **Plugin Events**: Widget loads, user interactions, and feature usage

This data is used to:

- Generate usage statistics
- Identify areas for plugin improvement
- Enhance compatibility with different WordPress environments
- Improve user experience and functionality

## Privacy

- Privacy Policy: https://www.wetravel.com/privacy
- Terms of Service: https://www.wetravel.com/terms
- Users can opt-out at any time through the plugin settings
- No personal user data is collected beyond WordPress environment information

## Future Enhancements

Potential improvements:

- Granular consent options for different types of tracking
- Enhanced analytics dashboard
- GDPR compliance features
- Multi-language support
