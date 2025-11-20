<?php
/**
 * Plugin Name: ENTSOE Energy Charts
 * Plugin URI: https://yoursite.com
 * Description: Display ENTSOE electricity market data with beautiful charts and Elementor integration
 * Version: 1.0.0
 * Author: Julius
 * Author URI: https://yoursite.com
 * License: GPL v2 or later
 * Text Domain: entsoe-charts
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('ENTSOE_CHARTS_VERSION', '1.0.0');
define('ENTSOE_CHARTS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ENTSOE_CHARTS_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once ENTSOE_CHARTS_PLUGIN_DIR . 'includes/class-entsoe-api.php';
require_once ENTSOE_CHARTS_PLUGIN_DIR . 'includes/class-admin-page.php';
require_once ENTSOE_CHARTS_PLUGIN_DIR . 'includes/class-elementor-widgets.php';

class ENTSOE_Charts_Plugin {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        // Initialize admin page
        ENTSOE_Admin_Page::get_instance();
        
        // Initialize Elementor widgets
        if (did_action('elementor/loaded')) {
            add_action('elementor/widgets/register', array($this, 'register_elementor_widgets'));
        }
        
        // Register AJAX endpoints
        add_action('wp_ajax_entsoe_fetch_data', array($this, 'ajax_fetch_data'));
        add_action('wp_ajax_nopriv_entsoe_fetch_data', array($this, 'ajax_fetch_data'));
    }
    
    public function register_elementor_widgets($widgets_manager) {
        ENTSOE_Elementor_Widgets::register_widgets($widgets_manager);
    }
    
    public function enqueue_admin_assets($hook) {
        if ('toplevel_page_entsoe-charts' !== $hook) {
            return;
        }
        
        wp_enqueue_style('entsoe-admin-css', ENTSOE_CHARTS_PLUGIN_URL . 'assets/css/admin.css', array(), ENTSOE_CHARTS_VERSION);
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js', array(), '4.4.0', true);
        wp_enqueue_script('entsoe-admin-js', ENTSOE_CHARTS_PLUGIN_URL . 'assets/js/admin.js', array('jquery', 'chart-js'), ENTSOE_CHARTS_VERSION, true);
        
        wp_localize_script('entsoe-admin-js', 'entsoeAjax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('entsoe-ajax-nonce')
        ));
    }
    
    public function enqueue_frontend_assets() {
        wp_enqueue_style('entsoe-frontend-css', ENTSOE_CHARTS_PLUGIN_URL . 'assets/css/frontend.css', array(), ENTSOE_CHARTS_VERSION);
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js', array(), '4.4.0', true);
        wp_enqueue_script('entsoe-frontend-js', ENTSOE_CHARTS_PLUGIN_URL . 'assets/js/frontend.js', array('jquery', 'chart-js'), ENTSOE_CHARTS_VERSION, true);
        
        wp_localize_script('entsoe-frontend-js', 'entsoeAjax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('entsoe-ajax-nonce')
        ));
    }
    
    public function ajax_fetch_data() {
        check_ajax_referer('entsoe-ajax-nonce', 'nonce');
        
        $data_type = isset($_POST['data_type']) ? sanitize_text_field($_POST['data_type']) : '';
        $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : '';
        $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : '';
        $area_code = isset($_POST['area_code']) ? sanitize_text_field($_POST['area_code']) : '';
        
        $api = new ENTSOE_API();
        $result = $api->fetch_data($data_type, $start_date, $end_date, $area_code);
        
        wp_send_json($result);
    }
    
    public function activate() {
        // Set default options
        add_option('entsoe_api_key', '');
        add_option('entsoe_default_area', '10YAT-APG------L');
        add_option('entsoe_cache_duration', 3600);
    }
    
    public function deactivate() {
        // Cleanup if needed
    }
}

// Initialize the plugin
ENTSOE_Charts_Plugin::get_instance();
