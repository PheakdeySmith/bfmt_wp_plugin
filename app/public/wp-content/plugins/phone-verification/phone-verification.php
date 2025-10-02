<?php
/**
 * Plugin Name: Phone Number Verification
 * Plugin URI: https://your-website.com/
 * Description: Advanced phone number verification system with TMT API integration, network prefix validation, and batch processing capabilities.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: phone-verification
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('PHONE_VERIFICATION_VERSION', '1.0.0');
define('PHONE_VERIFICATION_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PHONE_VERIFICATION_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('PHONE_VERIFICATION_PLUGIN_FILE', __FILE__);

// Main plugin class
class PhoneVerificationPlugin {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('plugins_loaded', array($this, 'load_textdomain'));

        // Activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        // Include required files
        $this->include_files();

        // Initialize admin interface
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_admin_menu'));
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        }

        // Initialize AJAX handlers
        $this->init_ajax_handlers();

        // Initialize shortcodes
        add_shortcode('phone_verification', array($this, 'phone_verification_shortcode'));
    }

    public function init() {
        // Initialize plugin functionality
        $this->ensure_tables_exist();
    }

    public function load_textdomain() {
        load_plugin_textdomain('phone-verification', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function activate() {
        // Create database tables
        $this->create_tables();

        // Set default options
        $this->set_default_options();

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    public function deactivate() {
        // Clean up temporary data
        $this->cleanup_temp_data();
    }

    private function include_files() {
        // Include core classes
        require_once PHONE_VERIFICATION_PLUGIN_PATH . 'includes/class-verification-service.php';
        require_once PHONE_VERIFICATION_PLUGIN_PATH . 'includes/class-network-prefix.php';
        require_once PHONE_VERIFICATION_PLUGIN_PATH . 'includes/class-verification.php';
        require_once PHONE_VERIFICATION_PLUGIN_PATH . 'includes/class-admin.php';
        require_once PHONE_VERIFICATION_PLUGIN_PATH . 'includes/class-ajax-handlers.php';
        require_once PHONE_VERIFICATION_PLUGIN_PATH . 'includes/class-export.php';
    }

    public function add_admin_menu() {
        // Add main menu
        add_menu_page(
            __('Phone Verification', 'phone-verification'),
            __('Phone Verification', 'phone-verification'),
            'manage_options',
            'phone-verification',
            array($this, 'admin_page'),
            'dashicons-phone',
            30
        );

        // Add submenu pages
        add_submenu_page(
            'phone-verification',
            __('Verification Results', 'phone-verification'),
            __('Results', 'phone-verification'),
            'manage_options',
            'phone-verification',
            array($this, 'admin_page')
        );

        add_submenu_page(
            'phone-verification',
            __('Settings', 'phone-verification'),
            __('Settings', 'phone-verification'),
            'manage_options',
            'phone-verification-settings',
            array($this, 'settings_page')
        );

        add_submenu_page(
            'phone-verification',
            __('Network Prefixes', 'phone-verification'),
            __('Network Prefixes', 'phone-verification'),
            'manage_options',
            'phone-verification-prefixes',
            array($this, 'prefixes_page')
        );
    }

    public function enqueue_admin_scripts($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'phone-verification') === false) {
            return;
        }

        // Enqueue WordPress media uploader
        wp_enqueue_media();

        // Enqueue required scripts
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-datepicker');

        // Enqueue DataTables
        wp_enqueue_script(
            'datatables',
            'https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js',
            array('jquery'),
            '1.11.5',
            true
        );

        wp_enqueue_style(
            'datatables',
            'https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css',
            array(),
            '1.11.5'
        );

        // Enqueue SheetJS for Excel processing
        wp_enqueue_script(
            'sheetjs',
            'https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js',
            array(),
            '0.18.5',
            true
        );

        // Enqueue plugin styles
        wp_enqueue_style(
            'phone-verification-admin',
            PHONE_VERIFICATION_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            PHONE_VERIFICATION_VERSION
        );

        // Enqueue plugin scripts
        wp_enqueue_script(
            'phone-verification-admin',
            PHONE_VERIFICATION_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'datatables', 'sheetjs'),
            PHONE_VERIFICATION_VERSION,
            true
        );

        // Localize script for AJAX
        wp_localize_script('phone-verification-admin', 'phoneVerificationAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('phone_verification_nonce'),
            'strings' => array(
                'verifying' => __('Verifying...', 'phone-verification'),
                'error' => __('Error', 'phone-verification'),
                'success' => __('Success', 'phone-verification'),
                'cached_result' => __('Cached Result', 'phone-verification'),
                'batch_complete' => __('Batch Verification Complete', 'phone-verification'),
            )
        ));
    }

    private function init_ajax_handlers() {
        new Phone_Verification_Ajax_Handlers();

        // Add additional AJAX handlers for admin functionality
        add_action('wp_ajax_add_network_prefix', array($this, 'ajax_add_network_prefix'));
        add_action('wp_ajax_update_network_prefix', array($this, 'ajax_update_network_prefix'));
        add_action('wp_ajax_delete_network_prefix', array($this, 'ajax_delete_network_prefix'));
        add_action('wp_ajax_update_prefix_coverage', array($this, 'ajax_update_prefix_coverage'));

        // Add export handler
        add_action('admin_post_export_phone_verifications', array($this, 'export_verifications'));
    }

    public function admin_page() {
        include PHONE_VERIFICATION_PLUGIN_PATH . 'templates/admin/verification-page.php';
    }

    public function settings_page() {
        include PHONE_VERIFICATION_PLUGIN_PATH . 'templates/admin/settings-page.php';
    }

    public function prefixes_page() {
        include PHONE_VERIFICATION_PLUGIN_PATH . 'templates/admin/prefixes-page.php';
    }

    public function phone_verification_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show_table' => 'true',
            'show_buttons' => 'true',
            'theme' => 'default'
        ), $atts);

        ob_start();
        include PHONE_VERIFICATION_PLUGIN_PATH . 'templates/frontend/verification-form.php';
        return ob_get_clean();
    }

    private function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Create network_prefixes table
        $table_prefixes = $wpdb->prefix . 'phone_network_prefixes';
        $sql_prefixes = "CREATE TABLE $table_prefixes (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            prefix varchar(20) NOT NULL,
            min_length int(11) NOT NULL,
            max_length int(11) NOT NULL,
            country_name varchar(100) NOT NULL,
            network_name varchar(100) NOT NULL,
            mcc varchar(10) DEFAULT NULL,
            mnc varchar(10) DEFAULT NULL,
            live_coverage tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY prefix (prefix),
            KEY country_name (country_name),
            KEY live_coverage (live_coverage)
        ) $charset_collate;";

        // Create verifications table
        $table_verifications = $wpdb->prefix . 'phone_verifications';
        $sql_verifications = "CREATE TABLE $table_verifications (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            number varchar(20) NOT NULL,
            prefix varchar(20) DEFAULT NULL,
            cic varchar(50) DEFAULT NULL,
            error int(11) DEFAULT 0,
            imsi varchar(50) DEFAULT NULL,
            mcc varchar(10) DEFAULT NULL,
            mnc varchar(10) DEFAULT NULL,
            network varchar(100) DEFAULT NULL,
            ocn varchar(50) DEFAULT NULL,
            ported tinyint(1) DEFAULT 0,
            present varchar(10) DEFAULT NULL,
            status int(11) DEFAULT 0,
            status_message varchar(255) DEFAULT NULL,
            type varchar(20) DEFAULT NULL,
            trxid varchar(100) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY number (number),
            KEY created_at (created_at),
            KEY status (status),
            KEY error (error),
            KEY prefix (prefix)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_prefixes);
        dbDelta($sql_verifications);

        // Seed network prefixes
        $this->seed_network_prefixes();
    }

    private function ensure_tables_exist() {
        global $wpdb;

        $table_prefixes = $wpdb->prefix . 'phone_network_prefixes';
        $table_verifications = $wpdb->prefix . 'phone_verifications';

        // Check if tables exist
        $prefixes_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_prefixes'") == $table_prefixes;
        $verifications_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_verifications'") == $table_verifications;

        // If either table doesn't exist, create both
        if (!$prefixes_exists || !$verifications_exists) {
            $this->create_tables();
        }
    }

    private function seed_network_prefixes() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'phone_network_prefixes';

        // Check if data already exists
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        if ($count > 0) {
            return;
        }

        // Sample Cambodia network prefixes data
        $prefixes = array(
            array('85592', 11, 11, 'Cambodia', 'KH Cellcard Mobile', '456', '01', 1),
            array('85589', 11, 11, 'Cambodia', 'KH Cellcard Mobile', '456', '01', 1),
            array('85585', 11, 11, 'Cambodia', 'KH Cellcard Mobile', '456', '01', 1),
            array('85517', 11, 11, 'Cambodia', 'KH Cellcard Mobile', '456', '01', 1),
            array('85514', 11, 11, 'Cambodia', 'KH Cellcard Mobile', '456', '01', 1),
            array('85512', 11, 12, 'Cambodia', 'KH Cellcard Mobile', '456', '01', 1),
            array('85578', 11, 11, 'Cambodia', 'KH Cellcard Mobile', '456', '01', 1),
            array('85561', 11, 11, 'Cambodia', 'KH Cellcard Mobile', '456', '01', 1),
            array('85577', 11, 11, 'Cambodia', 'KH Cellcard Mobile', '456', '01', 1),
            array('85595', 11, 11, 'Cambodia', 'KH Cellcard Mobile', '456', '01', 1),
            array('85599', 11, 11, 'Cambodia', 'KH Cellcard Mobile', '456', '01', 1),
            array('85576', 11, 11, 'Cambodia', 'KH Cellcard Mobile', '456', '01', 1),
            array('85510', 11, 11, 'Cambodia', 'KH Smart Mobile', '456', '02', 0),
            array('85516', 11, 11, 'Cambodia', 'KH Smart Mobile', '456', '02', 0),
            array('85569', 11, 11, 'Cambodia', 'KH Smart Mobile', '456', '02', 0),
            array('85515', 11, 11, 'Cambodia', 'KH Smart Mobile', '456', '02', 0),
            array('85570', 11, 11, 'Cambodia', 'KH Smart Mobile', '456', '02', 0),
            array('85581', 11, 11, 'Cambodia', 'KH Smart Mobile', '456', '02', 0),
            array('85586', 11, 11, 'Cambodia', 'KH Smart Mobile', '456', '02', 0),
            array('85587', 11, 11, 'Cambodia', 'KH Smart Mobile', '456', '02', 0),
            array('85593', 11, 11, 'Cambodia', 'KH Smart Mobile', '456', '02', 0),
            array('85598', 11, 11, 'Cambodia', 'KH Smart Mobile', '456', '02', 0),
            array('85596', 11, 11, 'Cambodia', 'KH Smart Mobile', '456', '02', 0),
            array('85597', 11, 11, 'Cambodia', 'KH Metfone', '456', '08', 0),
            array('85566', 11, 11, 'Cambodia', 'KH Metfone', '456', '08', 0),
            array('85560', 11, 11, 'Cambodia', 'KH Metfone', '456', '08', 0),
            array('85567', 11, 11, 'Cambodia', 'KH Metfone', '456', '08', 0),
            array('85568', 11, 11, 'Cambodia', 'KH Metfone', '456', '08', 0),
            array('85590', 11, 11, 'Cambodia', 'KH Metfone', '456', '08', 0),
            array('85518', 11, 11, 'Cambodia', 'KH yes018 Seatel', '456', '18', 0),
        );

        foreach ($prefixes as $prefix_data) {
            $wpdb->insert(
                $table_name,
                array(
                    'prefix' => $prefix_data[0],
                    'min_length' => $prefix_data[1],
                    'max_length' => $prefix_data[2],
                    'country_name' => $prefix_data[3],
                    'network_name' => $prefix_data[4],
                    'mcc' => $prefix_data[5],
                    'mnc' => $prefix_data[6],
                    'live_coverage' => $prefix_data[7],
                )
            );
        }
    }

    private function set_default_options() {
        add_option('phone_verification_api_key', '');
        add_option('phone_verification_api_secret', '');
        add_option('phone_verification_api_url', 'https://api.tmtvelocity.com/live');
        add_option('phone_verification_cache_duration', 3600); // 1 hour
        add_option('phone_verification_enable_cache', 1);
    }

    private function cleanup_temp_data() {
        // Clean up any temporary data on deactivation
        delete_transient('phone_verification_stats');
    }

    // AJAX Handlers for network prefix management
    public function ajax_add_network_prefix() {
        if (!wp_verify_nonce($_POST['nonce'], 'phone_verification_nonce')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $result = Phone_Verification_Network_Prefix::add_prefix($_POST);

        if ($result) {
            wp_send_json_success(array('message' => 'Network prefix added successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to add network prefix'));
        }
    }

    public function ajax_update_network_prefix() {
        if (!wp_verify_nonce($_POST['nonce'], 'phone_verification_nonce')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'phone_network_prefixes';

        $result = $wpdb->update(
            $table_name,
            array(
                'prefix' => sanitize_text_field($_POST['prefix']),
                'country_name' => sanitize_text_field($_POST['country_name']),
                'network_name' => sanitize_text_field($_POST['network_name']),
                'min_length' => intval($_POST['min_length']),
                'max_length' => intval($_POST['max_length']),
                'mcc' => sanitize_text_field($_POST['mcc']),
                'mnc' => sanitize_text_field($_POST['mnc']),
                'live_coverage' => isset($_POST['live_coverage']) ? 1 : 0,
                'updated_at' => current_time('mysql')
            ),
            array('prefix' => sanitize_text_field($_POST['original_prefix']))
        );

        if ($result !== false) {
            wp_send_json_success(array('message' => 'Network prefix updated successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to update network prefix'));
        }
    }

    public function ajax_delete_network_prefix() {
        if (!wp_verify_nonce($_POST['nonce'], 'phone_verification_nonce')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $result = Phone_Verification_Network_Prefix::delete_prefix(sanitize_text_field($_POST['prefix']));

        if ($result) {
            wp_send_json_success(array('message' => 'Network prefix deleted successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to delete network prefix'));
        }
    }

    public function ajax_update_prefix_coverage() {
        if (!wp_verify_nonce($_POST['nonce'], 'phone_verification_nonce')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $result = Phone_Verification_Network_Prefix::update_live_coverage(
            sanitize_text_field($_POST['prefix']),
            intval($_POST['live_coverage'])
        );

        if ($result !== false) {
            wp_send_json_success(array('message' => 'Coverage status updated successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to update coverage status'));
        }
    }

    public function export_verifications() {
        if (!wp_verify_nonce($_GET['nonce'], 'phone_verification_nonce')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        Phone_Verification_Export::export_csv();
    }
}

// Initialize the plugin
function phone_verification_init() {
    return PhoneVerificationPlugin::get_instance();
}

// Start the plugin
add_action('plugins_loaded', 'phone_verification_init');

// Helper function to get plugin instance
function phone_verification() {
    return PhoneVerificationPlugin::get_instance();
}