# Phone Number Verification WordPress Plugin

A comprehensive WordPress plugin for phone number verification using the TMT Velocity API. This plugin provides both admin interface and frontend shortcode functionality for verifying phone numbers with network prefix validation and batch processing capabilities.

## Features

### Admin Features
- **Phone Number Verification**: Single and batch phone number verification
- **Network Prefix Management**: Database of network prefixes with live coverage information
- **API Integration**: TMT Velocity API integration with configurable settings
- **Caching System**: WordPress transient caching to reduce API costs
- **Export Functionality**: Export verification results to CSV/Excel
- **Statistics Dashboard**: Real-time statistics and performance metrics
- **Batch Processing**: Upload Excel/CSV files for bulk verification

### Frontend Features
- **Shortcode Support**: `[phone_verification]` shortcode for frontend display
- **User-Friendly Interface**: Clean, responsive design for public use
- **Real-time Validation**: Network prefix validation before API calls
- **Results Display**: Table showing recent verification results

## Installation

1. **Upload Plugin**: Upload the `phone-verification` folder to `/wp-content/plugins/`
2. **Activate Plugin**: Activate the plugin through the 'Plugins' menu in WordPress
3. **Configure Settings**: Go to **Phone Verification > Settings** to configure your TMT API credentials

## Configuration

### API Settings
1. Navigate to **Phone Verification > Settings**
2. Enter your TMT API credentials:
   - **API Key**: Your TMT Velocity API key
   - **API Secret**: Your TMT Velocity API secret
   - **API URL**: TMT API endpoint (default: `https://api.tmtvelocity.com/live`)

### Cache Settings
- **Enable Caching**: Toggle result caching (recommended)
- **Cache Duration**: Set how long to cache results (1 hour recommended)

## Usage

### Admin Interface

#### Single Phone Verification
1. Go to **Phone Verification** in the WordPress admin
2. Click **"Verify Single Number"**
3. Enter the phone number (including country code)
4. Select data freshness preference
5. Click **"Verify"**

#### Batch Verification
1. Click **"Batch Upload"**
2. Upload an Excel (.xlsx, .xls) or CSV file with phone numbers in the first column
3. Select data freshness preference
4. Click **"Verify All"**

#### Network Prefix Management
1. Go to **Phone Verification > Network Prefixes**
2. View, search, add, edit, or delete network prefixes
3. Toggle live coverage status for each prefix
4. Manage country and network information

### Frontend Usage

#### Basic Shortcode
```php
[phone_verification]
```

#### Shortcode with Options
```php
[phone_verification show_table="true" show_buttons="true" theme="default"]
```

**Options:**
- `show_table`: Display results table (default: "true")
- `show_buttons`: Show verification buttons (default: "true")
- `theme`: Theme variation (default: "default")

## File Structure

```
phone-verification/
├── phone-verification.php          # Main plugin file
├── includes/
│   ├── class-verification-service.php   # Core verification logic
│   ├── class-ajax-handlers.php          # AJAX request handlers
│   ├── class-network-prefix.php         # Network prefix management
│   ├── class-verification.php           # Verification data model
│   ├── class-admin.php                  # Admin functionality
│   └── class-export.php                 # Export functionality
├── templates/
│   ├── admin/
│   │   ├── verification-page.php        # Main admin page
│   │   ├── settings-page.php            # Settings page
│   │   └── prefixes-page.php            # Network prefixes page
│   └── frontend/
│       └── verification-form.php        # Frontend shortcode template
├── assets/
│   ├── css/
│   │   ├── admin.css                    # Admin styles
│   │   └── frontend.css                 # Frontend styles
│   └── js/
│       ├── admin.js                     # Admin JavaScript
│       └── frontend.js                  # Frontend JavaScript
└── README.md                           # Documentation
```

## Database Tables

The plugin creates two database tables:

### `wp_phone_network_prefixes`
Stores network prefix information:
- `prefix`: Phone number prefix
- `country_name`: Country name
- `network_name`: Network operator name
- `min_length`/`max_length`: Valid phone number lengths
- `mcc`/`mnc`: Mobile Country Code / Mobile Network Code
- `live_coverage`: Whether API verification is available

### `wp_phone_verifications`
Stores verification results:
- `number`: Phone number verified
- `status`: Verification status (0 = success)
- `network`: Network operator
- `ported`: Whether number is ported
- `present`: Subscriber presence
- `trxid`: Transaction ID
- Plus additional TMT API response fields

## API Integration

### Network Prefix Pre-checking
The plugin includes intelligent network prefix validation that:
- Checks phone numbers against local database first
- Only makes API calls for numbers with live coverage
- Reduces API costs by skipping numbers without coverage
- Provides real-time validation feedback

### Caching Strategy
- **WordPress Transients**: Primary caching layer
- **Database Storage**: Persistent storage for all results
- **Cache Duration**: Configurable cache expiration
- **Smart Invalidation**: Automatic cache refresh based on data age

### Data Freshness Options
- **Use cached data**: Default, fastest response
- **Force refresh if older than X days**: Configurable refresh intervals
- **Always get fresh data**: Bypass all caching

## Security Features

- **Nonce Verification**: All AJAX requests use WordPress nonces
- **Capability Checks**: Admin functions require proper user capabilities
- **Input Sanitization**: All user inputs are sanitized
- **SQL Injection Protection**: Prepared statements for all database queries

## Performance Optimization

- **Batch Processing**: Efficient handling of multiple phone numbers
- **Progressive Loading**: Real-time UI updates during batch operations
- **Memory Management**: Optimized for large datasets
- **Database Indexing**: Proper indexes for fast queries

## Troubleshooting

### Common Issues

1. **API Connection Failed**
   - Check API credentials in settings
   - Verify network connectivity
   - Test with API test function

2. **Database Errors**
   - Ensure proper WordPress database permissions
   - Check for plugin conflicts
   - Verify table creation during activation

3. **Cache Issues**
   - Clear WordPress transients
   - Disable caching temporarily for testing
   - Check cache duration settings

### Support

For technical support:
1. Check the WordPress debug log for error messages
2. Verify all plugin requirements are met
3. Test with default WordPress theme
4. Contact plugin developer with specific error details

## Requirements

- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher
- **MySQL**: 5.6 or higher
- **TMT Velocity API**: Valid API credentials required

## License

This plugin is licensed under the GPL v2 or later.

## Changelog

### Version 1.0.0
- Initial release
- TMT Velocity API integration
- Network prefix management
- Batch verification support
- Frontend shortcode functionality
- Export capabilities
- Comprehensive admin interface